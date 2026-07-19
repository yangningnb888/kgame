<?php
$刷新房间 = array(
    'event' => "Msg_XSZY_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'free' => '当前免费次数',
        'freecandle' => '免费中的蜡烛数量',
        'curgrade' => '当前选中档次',
        'map' => ['地图'],
        'max_multiple' => '最大档次',
        'level' => '房间等级',
        'doublescore' => '底分',
        'min_gold' => '入场最小金币数',
        'max_gold' => '入场最大金币数',
        'gold' => '当前分数',
        'collect' => ['等级' => ['对应蜡烛数量']],
    ]
);


$开始游戏 = array(
    'event' => "Msg_XSZY_Start",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'multiple' => '1-6',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'line' => [
            'linenum' => '线编号',
            'len' => '数量',
        ],
        'collect' => ['对应蜡烛数量'],
        'score' => '赢分数',
        'conscore' => '消耗分数',
        'map' => ['地图'],
        'free' => '免费局数',
        'curgetfree' => '当前局中的免费局数',
        'freecandle' => '免费中的蜡烛数量',
        'reward' => '1 - 4奖励',
        'freewild' => ['免费wild下标'],
        'multiple' => '1-6',
    ]
);


$退出游戏 = array(
    'event' => "Msg_XSZY_Out",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => []
);



