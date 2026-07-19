-- ============================================================
-- 游戏弹窗公告（公告通知）菜单项
-- 库：`63`（ThinkAdmin 与游戏服共用）
-- 执行位置：在服务器 4399521 上连库 63 执行一次即可。
-- 作用：把「公告通知」挂到「游戏管理」菜单下。
-- ------------------------------------------------------------
-- 备选方案（无需本 SQL）：
--   后台「系统管理 → 菜单管理 → 刷新菜单」会自动扫到
--   GameNotice.php 里 @menu true 注解的 admin/gamenotice/index，
--   然后在菜单管理界面把新出现的「公告通知」手动拖到「游戏管理」下即可。
-- ============================================================

-- 说明：MySQL 不允许在 INSERT...SELECT 中直接子查询目标表，故子查询均包一层派生表别名。
INSERT INTO system_menu (pid, title, url, node, icon, params, target, sort, status)
SELECT
    COALESCE((SELECT id FROM (SELECT id FROM system_menu WHERE title LIKE '%游戏管理%' AND url = '#' LIMIT 1) t), 0),
    '公告通知',
    'admin/gamenotice/index',
    'admin/gamenotice/index',
    '',
    '',
    '_self',
    0,
    1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT node FROM system_menu WHERE node = 'admin/gamenotice/index') x
);
