#!/usr/bin/env bash
###############################################################################
# KGame 一键部署脚本  (Ubuntu 22.04 / 24.04)
# 目标：把本项目部署到 Linux 服务器，域名 4399521.xyz，HTTPS + 反向代理。
#
# 用法：
#   1) 先把整个项目上传到服务器（见下方 SRC_DIR），推荐 /opt/kgame
#   2) chmod +x deploy.sh
#   3) sudo ./deploy.sh
#
# 本脚本做了什么：
#   [1] 安装 nginx / php7.4-fpm(+扩展) / mysql / python3-venv / certbot
#   [2] 建数据库、建用户、导入 4 个 SQL
#   [3] 部署 nginx 站点配置（HTTPS 反代 wss + api + 静态前端）
#   [4] 用 certbot 申请 4399521.xyz 证书
#   [5] 配置 systemd 守护 Workerman 游戏服 与 kgame-api
#   [6] 改写前端 ServerConfig 到域名
#   [7] 启动全部服务
#
# 幂等：可重复运行；已完成的步骤会跳过。
###############################################################################
set -euo pipefail

# ======================= 可修改的参数 =======================
DOMAIN="4399521.xyz"                 # 你的域名（已解析到本服务器公网 IP）
CERT_EMAIL="you@example.com"         # Let's Encrypt 通知邮箱，改成你的
SRC_DIR="/opt/kgame"                 # 项目根目录（上传后的位置）
PHP_VER="7.4"                        # PHP 版本，代码原本用 7.3，7.4 兼容更好

# 数据库（与代码里写死的一致，不要随意改，否则要同步改 PHP/Python 配置）
DB_ROOT_PASS="86e201113604c41f"      # MySQL root 密码（tp_63 / 游戏服 / kgame-api 用）
DB_APP_USER="63"                     # 系统后台 1_8788 专用用户
DB_APP_PASS="t3W3PJGPyHKWwmfC"       # 系统后台专用用户密码
# ============================================================

# 项目内关键子路径
WEB_ROOT="${SRC_DIR}/wwwroot/wwwroot"          # nginx 静态/PHP 根
GAME_DIR="${WEB_ROOT}/game/63JHLM"             # Workerman 游戏服
FRONT_DIR="${WEB_ROOT}/h5/63"                  # 捕鱼前端
KGAPI_DIR="${SRC_DIR}/kgame-api"               # FastAPI 登录服务
DB_DIR="${SRC_DIR}/db"                         # SQL 文件目录

log(){ echo -e "\n\033[1;36m==== $* ====\033[0m"; }
warn(){ echo -e "\033[1;33m[!] $*\033[0m"; }

[ "$(id -u)" -eq 0 ] || { echo "请用 sudo 运行"; exit 1; }
[ -d "$SRC_DIR" ] || { echo "找不到项目目录 $SRC_DIR，请先上传项目并修改 SRC_DIR"; exit 1; }

###############################################################################
log "[1/7] 安装系统软件包 (Ubuntu $(lsb_release -rs))"
###############################################################################
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y software-properties-common curl unzip ca-certificates gnupg2 lsb-release

# Ubuntu 24.04 官方源没有 php7.4，必须加 ondrej/php PPA
if ! grep -Rq "ondrej/php" /etc/apt/sources.list /etc/apt/sources.list.d/ 2>/dev/null; then
  add-apt-repository -y ppa:ondrej/php || true
fi
apt-get update -y

apt-get install -y nginx mysql-server \
  php${PHP_VER}-fpm php${PHP_VER}-mysql php${PHP_VER}-mbstring php${PHP_VER}-curl \
  php${PHP_VER}-gd php${PHP_VER}-bcmath php${PHP_VER}-xml php${PHP_VER}-zip \
  php${PHP_VER}-cli php${PHP_VER}-opcache php${PHP_VER}-redis \
  python3 python3-venv python3-pip \
  certbot python3-certbot-nginx || {
    echo "软件包安装失败。如果是 Ubuntu 24.04，请确认 ppa:ondrej/php 已正确添加。"
    exit 1
  }

# php-cli 需要 pcntl / posix 给 Workerman（php-cli 默认已带 pcntl/posix，确认一下）
php${PHP_VER} -m | grep -qi pcntl || warn "php-cli 缺少 pcntl，Workerman 可能无法多进程，请检查"

# 内存提示
MEM_KB=$(grep MemTotal /proc/meminfo | awk '{print $2}')
MEM_GB=$((MEM_KB / 1024 / 1024))
if [ "$MEM_GB" -lt 2 ]; then
  warn "服务器内存仅 ${MEM_GB} GB。建议至少 2 GB；1 GB 跑 32 个 GAME worker 会 OOM，建议换 t3.small/c7i-flex.large 或更多内存。"
  warn "若你坚持 1 GB 部署，需要手动减少 game/63JHLM/Applications/GAME_*/ 的 worker 数量。"
elif [ "$MEM_GB" -lt 4 ]; then
  warn "服务器内存 ${MEM_GB} GB，能跑但并发高时请留意 OOM。建议 4 GB 起步。"
else
  log "服务器内存 ${MEM_GB} GB，配置充裕。"
fi

###############################################################################
log "[2/7] 初始化数据库并导入数据"
###############################################################################
systemctl enable --now mysql

# 设置 root 密码（首次）。若已设置会失败，忽略。
mysql -u root <<SQL 2>/dev/null || true
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASS}';
# 应用(kgame-api)通过 TCP 127.0.0.1 连接，必须单独授权该 host 账户
# （MySQL 中 'root'@'localhost' 与 'root'@'127.0.0.1' 是两个不同账户，否则 pymysql 报 Access denied -> 500）
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASS}';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL

MYSQL="mysql -u root -p${DB_ROOT_PASS}"

# 建库（63 为主库；其余为各平台库，全套服务全部导入）
for db in 63 728 jili lhj; do
  $MYSQL -e "CREATE DATABASE IF NOT EXISTS \`${db}\` DEFAULT CHARACTER SET utf8mb4;" || true
done

# 建系统后台专用用户
$MYSQL <<SQL || true
CREATE USER IF NOT EXISTS '${DB_APP_USER}'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_APP_PASS}';
GRANT ALL PRIVILEGES ON \`63\`.* TO '${DB_APP_USER}'@'localhost';
# ThinkAdmin(1_8788) 通过 TCP 127.0.0.1 连接，必须单独授权该 host 账户
# （同 root 的坑：'localhost'(socket) 与 '127.0.0.1'(TCP) 是两个不同账户，否则 Access denied -> 500）
CREATE USER IF NOT EXISTS '${DB_APP_USER}'@'127.0.0.1' IDENTIFIED WITH mysql_native_password BY '${DB_APP_PASS}';
GRANT ALL PRIVILEGES ON \`63\`.* TO '${DB_APP_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# 导入 SQL（用 marker 防重复）
MARK="${SRC_DIR}/.db-imported"
if [ ! -f "$MARK" ]; then
  set +e
  for f in 63 728 jili lhj; do
    sf="${DB_DIR}/${f}.sql"
    [ -f "$sf" ] || { echo "跳过（不存在）${f}.sql"; continue; }
    tf="${DB_DIR}/${f}.sql.filtered"
    # MySQL 8.0 已移除 NO_AUTO_CREATE_USER，导出 SQL 里该值会导致导入报错退出，先剔除
    sed -e 's/,NO_AUTO_CREATE_USER//g' -e 's/NO_AUTO_CREATE_USER,//g' -e 's/NO_AUTO_CREATE_USER//g' "$sf" > "$tf"
    echo "导入 ${f}.sql..."
    # 宽松 sql_mode：容忍老数据里的 0000-00-00 等零值日期，避免导入中断
    { echo "SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION';"; cat "$tf"; } | $MYSQL "$f"
    [ $? -eq 0 ] && echo "  -> ${f}.sql 导入完成" || warn "  -> ${f}.sql 导入出现错误，请检查"
  done
  set -e
  date > "$MARK"
  # 关键修复：清空维护时间窗口，否则整天被判维护、账号登录退化成游客
  $MYSQL 63 -e "UPDATE jh_sysconfig SET val='' WHERE \`key\` IN ('SERVER_WHSTART','SERVER_WHEND');" || true
  # 测试账号 13800138000 落在机器人种子段(uid 66670276, type=1)，翻为真人，否则大厅显示机器人
  $MYSQL 63 -e "UPDATE jh_user SET type=0 WHERE uid=66670276;" || true
  # 种子测试账号: 13800138000 / 123456 -> uid 66670276 (jh_user 已存在)，供登录接口校验手机号
  # 注意 jh_register 在导出 SQL 中无数据，必须此处补齐，否则 kgame-api 账号登录永远 401
  # 注意 jh_register.password 是 varchar(20)，存不下 32 位 MD5，必须存明文（≤20字符）；verify_password 优先明文比对
  $MYSQL 63 -e "INSERT IGNORE INTO jh_register (uid, telephone, password, created, status) VALUES (66670276, '13800138000', '123456', NOW(), 0);" || true
else
  warn "数据库已导入过（存在 $MARK），跳过。如需重导请删除该文件。"
fi

###############################################################################
log "[3/7] 部署 kgame-api 的 Python 虚拟环境"
###############################################################################
if [ -d "$KGAPI_DIR" ]; then
  python3 -m venv "${KGAPI_DIR}/venv-linux"
  "${KGAPI_DIR}/venv-linux/bin/pip" install --upgrade pip
  "${KGAPI_DIR}/venv-linux/bin/pip" install -r "${KGAPI_DIR}/requirements.txt"
else
  warn "找不到 kgame-api 目录，跳过 Python 服务。"
fi

###############################################################################
log "[4/7] 安装 systemd 守护（Workerman 游戏服 + kgame-api）"
###############################################################################
PHP_CLI="$(command -v php${PHP_VER} || command -v php)"

cp "${SRC_DIR}/server-deploy/systemd/kgame-game.service" /etc/systemd/system/kgame-game.service
cp "${SRC_DIR}/server-deploy/systemd/kgame-api.service"  /etc/systemd/system/kgame-api.service
# 用实际路径替换占位符
sed -i "s#__GAME_DIR__#${GAME_DIR}#g; s#__PHP_CLI__#${PHP_CLI}#g" /etc/systemd/system/kgame-game.service
sed -i "s#__KGAPI_DIR__#${KGAPI_DIR}#g" /etc/systemd/system/kgame-api.service

systemctl daemon-reload
systemctl enable kgame-game kgame-api

###############################################################################
log "[5/7] 部署 nginx 站点配置"
###############################################################################
NGX_CONF="/etc/nginx/sites-available/${DOMAIN}.conf"
cp "${SRC_DIR}/server-deploy/nginx-4399521.conf" "$NGX_CONF"
# 替换域名、根目录、php-fpm socket
FPM_SOCK="/run/php/php${PHP_VER}-fpm.sock"
sed -i "s#__DOMAIN__#${DOMAIN}#g; s#__WEB_ROOT__#${WEB_ROOT}#g; s#__FPM_SOCK__#${FPM_SOCK}#g" "$NGX_CONF"
ln -sf "$NGX_CONF" "/etc/nginx/sites-enabled/${DOMAIN}.conf"
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

###############################################################################
log "[6/7] 申请 HTTPS 证书 (Let's Encrypt)"
###############################################################################
warn "确保 ${DOMAIN} 的 A 记录已解析到本服务器公网 IP，且 80/443 已放行，否则会失败。"
certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos --register-unsafely-without-email --redirect || \
  warn "certbot 失败：请检查域名解析/防火墙后重试： certbot --nginx -d ${DOMAIN}"

###############################################################################
log "[7/7] 改写前端 ServerConfig 到域名，并启动服务"
###############################################################################
bash "${SRC_DIR}/server-deploy/patch-frontend.sh" "${DOMAIN}" "${FRONT_DIR}"

# 前端目录属主给 nginx（www-data），PHP 目录可写权限
chown -R www-data:www-data "${WEB_ROOT}"
find "${WEB_ROOT}" -type d -exec chmod 755 {} \;
find "${WEB_ROOT}" -type f -exec chmod 644 {} \;
# ThinkPHP runtime 需可写
for d in tp/tp_63 tp/tp_728 tp/tp_jili tp/tp_mjhl 1_8788 api; do
  [ -d "${WEB_ROOT}/${d}/runtime" ] && chmod -R 777 "${WEB_ROOT}/${d}/runtime" || true
done

systemctl restart php${PHP_VER}-fpm
systemctl restart kgame-game kgame-api
systemctl reload nginx

log "部署完成"
echo "游戏地址:   https://${DOMAIN}/"
echo "测试账号:   13800138000 / 123456"
echo ""
echo "查看服务状态:"
echo "  systemctl status kgame-game   # Workerman 游戏服 (32 worker)"
echo "  systemctl status kgame-api    # FastAPI 登录服务 :9493"
echo "  systemctl status php${PHP_VER}-fpm nginx mysql"
echo ""
echo "看日志:"
echo "  journalctl -u kgame-game -f"
echo "  journalctl -u kgame-api  -f"
