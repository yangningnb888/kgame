<?php
define('QUICK_FIRE', 100);  //奖池图标
define('FREE', 50);  //免费旋转
define('WILD', 0);  //wild
define('CHERRY', 1);  //樱桃
define('WATERMELON', 2);  //西瓜
define('PLUM', 3);  //李子
define('BELL', 4);  //铃铛
define('CHILD', 9);  //小孩
define('PURPLE', 11);  //紫色
define('GREEN', 12);  //紫色
define('RED', 13);  //红色

$刷新房间 = array(
    'event' => "Msg_RHEM_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'free' => '当前免费次数',
        'freewild' => ['免费中wild列数 0-4'],
        'freemap' => '免费地图数量',
        'curgrade' => '当前选中档次',
        'map' => ['地图'],
        'max_multiple' => '最大档次',
        'level' => '房间等级',
        'doublescore' => '底分',
        'min_gold' => '入场最小金币数',
        'max_gold' => '入场最大金币数',
        'gold' => '当前分数',
        'game_level' => ['挡位' => '单条线金额'],
        'jackpot_level' => ['挡位' => '金额比例'],
        'allwin' => '免费中的累积得分',
        'allfree' => '总共免费局数',
    ]
);


$开始游戏 = array(
    'event' => "Msg_RHEM_Start",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'multiple' => '1-5档',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'line' => ['线编号' => '数量'],
        'score' => '赢分数',
        'conscore' => '消耗分数',
        'map' => ['地图'],
        'free' => '免费局数',
        'reward' => '奖励等级',
        'jackpot' => '获得奖池金额',
        'collect' => '手记所中奖池等级 5-9  其他则是无',
        'roulette' => [
            'num' => '2-4 为地图数量 7 为增加免费局数',
            'wild' => ['wild列数0-4']
        ],
    ]
);


$退出游戏 = array(
    'event' => "Msg_RHEM_Out",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => []
);



