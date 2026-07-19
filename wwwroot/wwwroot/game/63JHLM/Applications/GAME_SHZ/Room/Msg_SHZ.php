<?php
$刷新房间 = array(
    'event' => "Msg_SHZ_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'free' => '当前免费次数',
        'curgrade' => '当前选中档次',
        'map' => ['地图'],
        'max_multiple' => '最大档次',
        'level' => '房间等级',
        'doublescore' => '底分',
        'min_gold' => '入场最小金币数',
        'max_gold' => '入场最大金币数',
        'gold' => '当前分数',
        'game' => '下游戏次数',
        'resinfo' => [
            'win' => [
                [
                    'num' => '中的个数',
                    'type' => '中的类型',
                    'line' => '中的线',
                    'dir' => '0不分 1正 2反',
                    'multiple' => '倍数'
                ]
            ],
            'game' => '小游戏次数',
            'score' => '赢分数',
            'gold' => '当前玩家分数',
            'conscore' => '消耗分数',
            'map' => ['地图']
        ],
        'score' => '还有多少分没有收'

    ]
);

$开始游戏 = array(
    'event' => "Msg_SHZ_Start",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'multiple' => '底分',
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'win' => [
            [
                'num' => '中的个数',
                'type' => '中的类型',
                'line' => '中的线',
                'dir' => '0不分 1正 2反',
                'multiple' => '倍数'
            ]
        ],
        'game' => '小游戏次数',
        'score' => '赢分数',
        'gold' => '当前玩家分数',
        'conscore' => '消耗分数',
        'map' => ['地图'],
        'type' => '1,2,3,4'//大小奖
    ]
);

$比倍 = array(
    'event' => "Msg_SHZ_Than",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'type' => '1大 2小 3和',
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'res' => ['骰子点数'],
        'gold' => '当前玩家分数',
        'history' => ['1大2小3平']
    ]
);

$收 = array(
    'event' => "Msg_SHZ_Collect",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'gold' => '当前玩家分数',
    ]
);

$小游戏 = array(
    'event' => "Msg_SHZ_Game",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        [
            'outType' => '外层类型',
            'innerTypes' => ['内层类型'],
            'gold' => '分数',
            'game' => '剩余小游戏次数'
        ],
    ]

);

$退出游戏 = array(
    'event' => "Msg_SHZ_Out",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => []
);

$游戏回放详情 = array(
    'event' => "Msg_Game_Back_Info",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'id' => '回放id'
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'nickname' => '玩家昵称',
        'doublescore' => '底分',
        'curgrade' => '当前选中档次',
        'map' => ['中奖前的地图'],
        'data' => [
            'resinfo' => [
                'win' => [
                    [
                        'num' => '中的个数',
                        'type' => '中的类型',
                        'line' => '中的线',
                        'dir' => '0不分 1正 2反',
                        'multiple' => '倍数'
                    ]
                ],
                'game' => '小游戏次数',
                'score' => '赢分数',
                'gold' => '当前玩家分数',
                'conscore' => '消耗分数',
                'map' => ['地图']
            ],
            'gameinfo' => [
                [
                    'outType' => '外层类型',
                    'innerTypes' => ['内层类型'],
                    'gold' => '分数',
                    'game' => '剩余小游戏次数'
                ],
            ]

        ]
    ]
);

$游戏回放列表 = array(
    'event' => "Msg_Game_Back_List",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'new' => [
            'nickname' => '头像',
            'type' => '类型 1有小游戏 2没有小游戏',
            'score' => '分数',
            'created' => '时间',
            'playnum' => '播放次数',
            'id' => 'id',
        ], //最新入围
        'history' => [
            'nickname' => '头像',
            'type' => '类型 1有小游戏 2没有小游戏',
            'score' => '分数',
            'created' => '时间',
            'playnum' => '播放次数',
            'id' => 'id',
        ], //历史排行
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
