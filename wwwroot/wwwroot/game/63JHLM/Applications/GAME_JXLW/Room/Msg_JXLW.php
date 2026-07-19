<?php
$刷新房间 = array(
    'event' => "Msg_JXLW_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'line' => '拉线次数',
        'free' => '当前免费次数',
        'curgrade' => '当前选中档次',
        'map' => ['地图'],
        'max_line' => '最大线数',
        'max_multiple' => '最大档次',
        'level' => '房间等级',
        'doublescore' => '底分',
        'min_gold' => '入场最小金币数',
        'max_gold' => '入场最大金币数',
        'gold' => '当前分数',
    ]
);

$开始游戏 = array(
    'event' => "Msg_JXLW_Start",
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
        'win' => [
            [
                'num' => '中的个数',
                'type' => '中的水果类型',
                'line' => '中线ID',
                'multiple' => '倍数'
            ]
        ],
        'score' => '赢分数',
        'gold' => '当前玩家分数',
        'conscore' => '消耗分数',
        'map' => ['地图'],
        'type' => '1,2,3,4'//大小奖
    ]
);

$退出游戏 = array(
    'event' => "Msg_JXLW_Out",
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
        'uid' => '玩家uid',
        'line' => '线数',
        'doublescore' => '底分',
        'curgrade' => '当前选中档次',
        'map' => ['中奖前的地图'],
        'data' => [
            [
                'win' => [
                    [
                        'num' => '中的个数',
                        'type' => '中的水果类型',
                        'line' => '中线ID',
                        'multiple' => '倍数'
                    ]
                ],
                'jackpot' => '中奖池分数',
                'curfree' => '当前免费次数',
                'score' => '赢分数',
                'free' => '免费次数',
                'gold' => '当前玩家分数',
                'conscore' => '消耗分数',
                'map' => ['地图'],
            ],
        ]
        // 'win' => [
        //     [
        //         'num' => '中的个数',
        //         'type' => '中的水果类型',
        //         'line' => '中线ID',
        //         'multiple' => '倍数'
        //     ]
        // ],
        // 'jackpot' => '中奖池分数',
        // 'curfree' => '当前免费次数',
        // 'score' => '赢分数',
        // 'free' => '免费次数',
        // 'gold' => '当前玩家分数',
        // 'conscore' => '消耗分数',
        // 'map' => ['地图'],
        // 'uid' => '玩家uid',
        // 'line' => '线数',
        // 'Bscore' => '底分',
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
            'type' => ' 类型 1分数和倍数达到标准 2免费次数3中奖池',
            'score' => '分数',
            'created' => '时间',
            'playnum' => '播放次数',
            'id' => 'id',
        ], //最新入围
        'history' => [
            'nickname' => '头像',
            'type' => ' 类型 1分数和倍数达到标准 2免费次数3中奖池',
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
    'data' => [
        'gtype' => '游戏编号',
        'level' => '房间等级',
    ], //data为空时查询所以 不为空是查询单独
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        [

            'gtype' => '游戏编号',
            'level' => '房间等级',
            'score' => '分数',
        ]

    ]
);
