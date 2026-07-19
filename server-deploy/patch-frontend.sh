#!/usr/bin/env bash
###############################################################################
# 把前端 ServerConfig 里的硬编码内网地址，改成走域名反向代理（HTTPS/WSS）。
#
# 用法（deploy.sh 会自动调用）：
#   bash patch-frontend.sh <域名> <前端目录>
#
# 做的事：
#   http://127.0.0.1:8163/   ->  https://<域名>/tp63/      (游戏 API / 登录分发)
#   ws://127.0.0.1:16000       ->  wss://<域名>/gamews       (游戏 WebSocket)
#
# 这些字符串出现在所有 serverType（NWC / outTest / cjbyCS / cjby63）里，
# 脚本一次性全替换。会先备份 .bak_domain。
###############################################################################
set -euo pipefail

DOMAIN="${1:-4399521.xyz}"
FRONT_DIR="${2:-}"
[ -n "$FRONT_DIR" ] || { echo "用法: $0 <域名> <前端目录>"; exit 1; }
[ -d "$FRONT_DIR" ] || { echo "前端目录不存在: $FRONT_DIR"; exit 1; }

echo "==> 改写前端 ServerConfig 到 https://${DOMAIN} (wss://${DOMAIN}/gamews)"

# 找 main bundle（assets/main/index.*.js，通常一个）
shopt -s nullglob
BUNDLES=("$FRONT_DIR"/assets/main/index.*.js)
shopt -u nullglob
[ "${#BUNDLES[@]}" -gt 0 ] || { echo "找不到 main bundle (assets/main/index.*.js)"; exit 1; }

for f in "${BUNDLES[@]}"; do
  cp -f "$f" "$f.bak_domain"   # 备份原始
  # 用 # 作分隔符，避免 URL 里的 / 需要转义
  sed -i "s#http://127.0.0.1:8163/#https://${DOMAIN}/tp63/#g; s#ws://127.0.0.1:16000#wss://${DOMAIN}/gamews#g" "$f"
  echo "    已处理: $f"
done

# 登录页 h5/63/index.html 的内联脚本也有硬编码，HTTPS 页面禁止混合内容，必须一并改
HTML="$FRONT_DIR/index.html"
if [ -f "$HTML" ]; then
  cp -f "$HTML" "$HTML.bak_domain"
  # 1) 登录分发走 /kapi 反代（原 http://域名:9493/login/login/dispatch）
  sed -i "s#http://' + location.hostname + ':9493#https://' + location.hostname + '/kapi#g" "$HTML"
  # 2) 登录成功后跳转去掉明文 ws://域名:16000，改用 ServerConfig 里已 patch 的 wss://域名/gamews
  sed -i "s#&addr=ws://' + location.hostname + ':16000##g" "$HTML"
  echo "    已处理登录页: $HTML"
fi

echo "==> 完成。浏览器请用 Ctrl+F5 硬刷新以加载新 JS。"
