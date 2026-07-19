# KGame 捕鱼平台 · Linux 部署仓库

把游戏服务部署到 Linux 服务器（Ubuntu 22.04 验证）的一键方案。
域名：`4399521.xyz`，全站 HTTPS + 反向代理（wss / api 统一走 443）。

---

## ⚠️ 安全提醒（必读）
本仓库的配置文件里**含有数据库密码**（MySQL root、系统后台专用账号），
位于：
- `server-deploy/deploy.sh` 顶部的 `DB_ROOT_PASS` / `DB_APP_PASS`
- `wwwroot/wwwroot/tp/tp_63/.../database.php`、`1_8788/.../database.php`
- `kgame-api/` 的 Python 配置

**务必把本仓库设为 Private（私有）！** 不要公开。
若要改密码，需同步改上面三处，保持一致。

---

## 目录结构（对应服务器 `/opt/kgame`）
```
kgame/
├── deploy.sh                 # 一键部署（Ubuntu 22.04）
├── nginx-4399521.conf       # nginx 站点配置（HTTPS 反代）
├── patch-frontend.sh         # 把前端写死的 127.0.0.1 改成域名
├── systemd/
│   ├── kgame-game.service   # Workerman 游戏服守护
│   └── kgame-api.service   # FastAPI 登录服务守护
├── wwwroot/wwwroot/         # 网站根（WEB_ROOT）
│   ├── game/63JHLM/        # Workerman 游戏服（32 worker）
│   ├── h5/63/              # 捕鱼 H5 前端
│   ├── tp/tp_63|tp_728|tp_jili|tp_mjhl/   # 各平台游戏 API
│   ├── 1_8788/             # 系统管理后台 (ThinkAdmin)
│   └── api/                # 内部 API（如有）
├── kgame-api/               # Python 登录分发 (FastAPI :9493)
└── db/                     # 4 个 SQL 初始化文件 (63/728/jili/lhj)
```

## 部署步骤
```bash
# 1. 在服务器上（建议 Ubuntu 22.04），克隆本仓库
sudo apt-get update && sudo apt-get install -y git
git clone https://github.com/yangningnb888/kgame.git /opt/kgame
cd /opt/kgame

# 2. 修改 deploy.sh 顶部参数（尤其 CERT_EMAIL 改成你的邮箱）
vim deploy.sh

# 3. 域名 A 记录先解析到本服务器公网 IP，并放行 80/443
# 4. 一键部署（需 root / sudo）
sudo bash deploy.sh
```
脚本会自动：装 nginx/php7.4-fpm/mysql/certbot → 建库导 SQL →
配置 systemd → 部署 nginx → 申请证书 → 改写前端域名 → 启动全部服务。

## 部署后
- 游戏入口： `https://4399521.xyz/`
- 系统后台： `https://4399521.xyz:8063/admin/login`（`admin / admin123`）
- 总控端：   `https://4399521.xyz:8081/`
- 测试账号： `13800138000 / 123456`

### 常用运维
```bash
systemctl status kgame-game kgame-api php7.4-fpm nginx mysql
journalctl -u kgame-game -f      # 游戏服日志
journalctl -u kgame-api  -f      # 登录服务日志
```

## 端口对照（服务器内网）
| 端口 | 服务 | 对外 |
|------|------|------|
| 16000 | 游戏 WS (Workerman) | 经 `/gamews` (wss) |
| 8163  | tp_63 游戏 API | 经 `/tp63/` |
| 9493  | kgame-api 登录分发 | 经 `/kapi/` |
| 8063  | 系统后台 API | 直连 :8063 |
| 8081  | 总控端前端 | 直连 :8081 |

## 已知注意点
- 前端 `hotUpDateUrl` 指向外部阿里云 OSS（热更新 CDN），与部署无关，保持即可。
- 数据库维护时间窗口已在导入时清空（`SERVER_WHSTART/END`），否则会整天判维护、登录退化成游客。
- 首次启动 MySQL root 密码会被设为 `deploy.sh` 里的 `DB_ROOT_PASS`。
