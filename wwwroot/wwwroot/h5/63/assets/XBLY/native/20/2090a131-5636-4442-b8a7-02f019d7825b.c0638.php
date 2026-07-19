<?php
$刷新房间 = array(
    'event' => "Msg_XBLY_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'line' => '当前档位',
        'curgrade' => '当前选中档次',
        'map' => ['地图'],
        '                            ' => '最大线数',
        'max_multiple' => '最大档次',
        'level' => '房间等级',
        'doublescore' => '底分',
        'min_gold' => '入场最小金币数',
        'max_gold' => '入场最大金币数',
        'gold' => '当前分数',
    ]
);

$开始游戏 = array(
    'event' => "Msg_XBLY_Start",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'multiple' => '底分',
        'line' => [], //线路
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'clear' => [
            'data' => [
                [
                    'type' => '中奖类型',
                    'map' => ['中奖地图'],
                    'score' => '分数'
                ],
            ],
            'repairmap' => ['补充地图']
        ],
        'map' => ['地图'],
        'gold' => '当前玩家分数',
        'conscore' => '消耗分数',
    ]
);

$退出游戏 = array(
    'event' => "Msg_XBLY_Out",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'gold' => '玩家分数'
    ]
);
$终极大奖 = array(
    'event' => "Msg_XBLY_GrandPrix",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'gold' => '玩家分数',
        'score' => '赢取分数'
    ]
);

$奖池分数 =  array(
    'event' => "Msg_Game_Jackpot",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'score' => '分数',
    ]
);
