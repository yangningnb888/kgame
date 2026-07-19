<?php
$刷新桌面 = array(
    'event' => "Msg_LHD_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'history' => ['历史战况 1龙 2平 3虎'],
        'time' => '当前剩余时间',
        'prize' => [
            '1' => [
                'score' => '分数'
            ], //龙
            '2' => [
                'score' => '分数'
            ], //和
            '3' => [
                'score' => '分数'
            ], //虎
        ], //桌面情况

        'banker' => [
            'nickname' => '昵称',
            'gold' => '金币',
            'nickname' => '昵称', //新增
            'num' => '上庄把数', //新增
            'isbanker'=>'是否申请下庄'
        ], //庄家信息

        'applyNum' => [['uid' => '玩家uid', 'nickname' => '昵称']], //修改
        'moreScore' => ['玩家uid' => [
            'gold' => '金币',
            'nickname' => '昵称',
            'headimgurl' => '头像框'
        ]], //分数最高

        'moreWin' => [
            'victory' => '赢得概率',
            'gold' => '金币',
            'nickname' => '昵称',
            'headimgurl' => '头像框'
        ], //胜利率最高

        'WennerUid' => [
            'uid' => '胜算子uid',
            'Loongbet' => '龙的分数',
            'tigerbet' => '虎下注分数',
            'flatbet' => '平下注分数',
            'win' => '胜利率',
        ],

        // 'players' => [
        //     '玩家uid' => [
        //         'nickname' => '昵称',
        //         'headimgurl' => '头像',
        //         'gold' => '金币',
        //         'batGold' => '下注金额',
        //         'batVictory' => '赢得把数'
        //     ]

        // ],
        'time' => '时间',
        'Loongbet' => '龙下注分数', // 龙下注分数
        'tigerbet' => '虎下注分数', // 虎下注分数
        'flatbet' => '平下注分数', //平下注分数
        'circle' => '当日局数',
        'gameState' => '游戏阶段'
    ]
);

$通知玩家押注 = array(
    'event' => "Msg_LHD_State_Stake",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'time' => '时间',
        'banker' => [
            'uid' => '玩家uid',
            'num' => '当庄次数',
            'isbanker'=>'是否申请下庄'
        ],

        'moreWin' => [
            'victory' => '赢得概率',
            'gold' => '金币',
            'nickname' => '昵称',
            'headimgurl' => '头像框'
        ], //胜利率最高

        'WennerUid' => [
            'uid' => '胜算子uid',
            'Loongbet' => '龙的分数',
            'tigerbet' => '虎下注分数',
            'flatbet' => '平下注分数',
            'win' => '胜利率',
        ],

        'WennerUid' => [
            'uid' => '胜算子uid',
            'win' => '胜利率',
        ],
        'applybanker' => [['uid' => '玩家uid', 'nickname' => '昵称']],
        'circle' => '当日局数'
    ]
);

$通知玩家开奖 = array(
    'event' => "Msg_LHD_State_Open",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'time' => '时间',
        'player' => [
            '玩家Uid' => [
                'score' => '输赢分数',
                'gold' => '当前分数',
                'batGold' => '下注金额',
                'batVictory' => '赢得把数'
            ], //有头像的玩家
        ],

        'Loong' => '牌id',
        'tiger' => '牌id'
    ]
);

$玩家下注 = array(
    'event' => "Msg_LHD_Act_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'bat' => '下注金额',
        'code' => '下注位置 1龙 2和 3虎'
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bat' => '下注金额',
        'isWenner' => '1神算子 0不是',
        'code' => '下注位置 1龙 2和 3虎'
    ]
);

$桌面情况 = array(
    'event' => "Msg_LHD_Act_Table",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        '玩家uid' => [
            1 => [
                'score' => 0,
            ],
            2 => [
                'score' => 0,
            ],
            3 => [
                'score' => 0,
            ] //LOONG_TYPE==1 FLAT_TYPE ==2  TIGER_TYPE==3
        ]

    ]
);

$申请庄家 = array(
    'event' => "Msg_LHD_Act_Banker",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '申请庄家uid',
        'nickname' => '昵称',
    ]
);

$申请下庄 = array(
    'event' => "Msg_LHD_Act_BackBanker",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '下庄家uid',

    ]
);

$玩家退出 = array(
    'event' => "Msg_LHD_Out",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => []
);

$玩家进入游戏 = array(
    'event' => "Msg_LHD_Add",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'nickname' => '昵称',
        'headimgurl' => '头像',
        'gold' => '金币',
    ]
);

$更新6个头像 = array(
    'event' => "Msg_LHD_Head",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'moreScore' => ['大富豪 玩家uid'],
        'moreWin' => ['神算子 玩家uid'],
        'applybanker' => [['uid' => '玩家uid', 'nickname' => '昵称']],
    ]
);
$玩家信息 = array(
    'event' => "Msg_LHD_GetUserList",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'players' => [
            '玩家uid' => [
                'nickname' => '昵称',
                'headimgurl' => '头像',
                'gold' => '金币',
                'batGold' => '下注金额',
                'batVictory' => '赢得把数'
            ]

        ],
    ]
);//新增
