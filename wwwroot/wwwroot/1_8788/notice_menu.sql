-- ============================================================
-- 游戏弹窗公告（公告通知）菜单项
-- 库：`63`（ThinkAdmin 与游戏服共用）
-- 执行位置：在服务器上连库 63 执行一次即可。
-- 作用：把「公告通知」挂到「游戏管理」菜单下（URL 必须用下划线 game_notice，
--       否则 ThinkPHP 的 Str::studly('gamenotice') 只生成 Gamenotice 一个词，
--       匹配不到控制器类 GameNotice → 404）。
-- ------------------------------------------------------------
-- 本 SQL 为「自愈式」：
--   1) 先删掉任何残留的旧无下划线节点 gamenotice（避免重复/旧链接）
--   2) 再幂等插入正确的 game_notice 节点（已存在则不重复插）
-- 重复执行安全，可随时重跑来纠正菜单状态。
-- ============================================================

-- 1) 清理旧的无下划线残留节点（若存在）
DELETE FROM system_menu WHERE node = 'admin/gamenotice/index';

-- 2) 幂等插入正确的 game_notice 节点，挂到「游戏管理」下
INSERT INTO system_menu (pid, title, url, node, icon, params, target, sort, status)
SELECT
    COALESCE((SELECT id FROM (SELECT id FROM system_menu WHERE title LIKE '%游戏管理%' AND url = '#' LIMIT 1) t), 0),
    '公告通知',
    'admin/game_notice/index',
    'admin/game_notice/index',
    '',
    '',
    '_self',
    0,
    1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT node FROM system_menu WHERE node = 'admin/game_notice/index') x
);
