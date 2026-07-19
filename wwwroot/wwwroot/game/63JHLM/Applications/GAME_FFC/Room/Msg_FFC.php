<?php
define('FFC_LOONG_TYPE', 1); //龙
define('FFC_FLAT_TYPE', 2); //平
define('FFC_TIGER_TYPE', 3); //虎
define('FFC_BIG_TYPE', 4); //大
define('FFC_SMALL_TYPE', 5); //小
define('FFC_SINGLE_TYPE', 6); //单
define('FFC_DOUBLE_TYPE', 7); //双

define('BET_WANG', 5); //万
define('BET_QIAN', 4); //千
define('BET_BAI', 3); //百
define('BET_SHI', 2); //十
define('BET_GE', 1); //个

$刷新桌面 = array(
    'event' => "Msg_FFC_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'history' => ['历史战况 1龙 2和 3虎 4大 5小 6单 7双'],
        'time' => '当前剩余时间',
        'prize' => [
            1 => 0, //龙
            2 => 0, //平
            3 => 0, //虎
            4 => 0, //大
            5 => 0, //小
            6 => 0, //单
            7 => 0 //双
        ], //桌面情况

        'banker' => [
            'nickname' => '昵称',
            'gold' => '金币',
            'nickname' => '昵称', //新增
            'num' => '上庄把数' //新增
        ], //庄家信息

        'applybanker' => ['uid' => [
            'nickname' => '昵称',
            'headimgurl' =>  '头像',
            'gold' => '金币'
        ]], //上庄列表

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
            'bet' => [
                1 => '金额',
                2 => '金额',
                3 => '金额',
                4 => [1 => '金额'],
                5 => [1 => '金额'],
                6 => [1 => '金额'],
                7 => [1 => '金额'],
            ], // 龙虎和 对应分数， 大小单双 对应买的球对应分数
            'win' => '胜利率',
        ],

        'time' => '时间',
        'bet' => [ //自己下注情况
            1 => '金额',
            2 => '金额',
            3 => '金额',
            4 => [1 => '金额'],
            5 => [1 => '金额'],
            6 => [1 => '金额'],
            7 => [1 => '金额'],
        ], // 龙虎和 对应分数， 大小单双 对应买的球对应分数 
        'gameState' => '游戏阶段'
    ]
);

//// 、/////
$通知玩家押注 = array(
    'event' => "Msg_FFC_State_Stake",
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
            'nickname' => '昵称',
            'gold' => '金币',
            'nickname' => '昵称', //新增
            'num' => '上庄把数' //新增
        ],

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

        'applybanker' => [
            [
                'uid' => '玩家uid',
                'nickname' => '昵称',
                'headimgurl' =>  '头像',
                'gold' => '金币'
            ]
        ], //上庄列表
    ]
);

$通知玩家开奖 = array(
    'event' => "Msg_FFC_State_Open",
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
                'betGold' => '下注金额',
                'betVictory' => '赢得把数'
            ], //有头像的玩家
        ],

        'openArr' => [
            1 => '数字', //个
            2 => '数字', //十
            3 => '数字', //百
            4 => '数字', //千
            5 => '数字', //万
        ]
    ]
);

$玩家下注 = array(
    'event' => "Msg_FFC_Act_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'bet' => '下注金额',
        'code' => '下注位置 1龙 2和 3虎 4大 5小 6单 7双',
        'region' => '1个 2十 3百 4千 5万 0龙、和、虎没有位置'
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bet' => '下注金额',
        'isWenner' => '1神算子 0不是',
        'code' => '下注位置 1龙 2和 3虎 4大 5小 6单 7双',
        'region' => '1个 2十 3百 4千 5万'
    ]
);

$桌面情况 = array(
    'event' => "Msg_FFC_Act_Table",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        '玩家uid' => [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0
        ] //1龙 2和 3虎 4大 5小 6单 7双

    ]
);

$申请庄家 = array(
    'event' => "Msg_FFC_Act_Banker",
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
    'event' => "Msg_FFC_Act_BackBanker",
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
    'event' => "Msg_FFC_Out",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'gold' => '玩家金币'
    ]
);

$玩家进入游戏 = array(
    'event' => "Msg_FFC_Add",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => []
);

$更新6个头像 = array(
    'event' => "Msg_FFC_Head",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
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
    ]
);

$玩家信息 = array(
    'event' => "Msg_FFC_GetUserList",
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
                'betGold' => '下注金额',
                'betVictory' => '赢得把数'
            ]
        ],
    ]
);//新增
