<?php
$刷新桌面 = array(
    'event' => "Msg_JDDDZ_RoomInfo",
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
                'gold' => '玩家分数',
                'nickname' => '昵称',
                'cardsNum' => '手牌张数',
                'multiple' => '倍数',
                'ready' => '0没准备 1准备',
                'istuo' => '0不托管 1托管',
                'online' => '1在线 0离线',
                'sex' => '性别  1男 2女',
                'seat' => '座位号',
                'callbanker' => '抢地主叫分',
                'lastCards' => [
                    'cards' => ['上一手牌'],
                    'type' => '牌型'
                ]
            ]
        ],

        'gameState' => '游戏阶段',
        'hands' => '玩家手牌',
        'banker' => '地主uid',
        'recorder' => '记牌器',
        'doublescore' => '底分',
        'rid' => '房间号',
        'bankerCards' => '地主牌',
        'beishu' => [
            'bankerCards' => 0, //地主牌的分数
            'boom' => 0, //炸弹分数
        ]
    ]
);

$发牌 = array(
    'event' => "Msg_JDDDZ_Fa",
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
                'cardsNum' => '玩家手牌分数'
            ]
        ],
        'hands' => '手牌',
    ]
);

$通知叫地主 = array(
    'event' => "Msg_JDDDZ_CallBanker",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'time' => '时间'
    ]
);

$通知加倍 = array(
    'event' => "Msg_JDDDZ_Double",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'time' => '时间',
        'banker' => '庄家',
        'tally' => ['记牌器'],
        'bankerCards' => ['地主牌']
    ]
);

$通知打牌 = array(
    'event' => "Msg_JDDDZ_Da",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'time' => '时间',
        'type' => '', //当前需要出的牌型 -1住的出牌 其他跟牌
        'ispass' => '0过 1可以出牌'
    ]
);

$通知玩家结算 = array(
    'event' => "Msg_JDDDZ_Res",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'cards' => [] //牌
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'players' => [
            '玩家uid' => [
                'score' => '输赢的分数',
                'gold' => '玩家现在的分数',
                'hands' => '玩家手上的牌',
                'beishu' => '玩家倍数'
            ],
            'spring' => '0没有春天 1 有春天',
            'antispring' => '0没有反春 1有反天'

        ]
    ]
);

$玩家叫分 = array(
    'event' => "Msg_JDDDZ_Act_CallScore",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'score' => '0不要 1分 2分 3分 '
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'score' => '1分 2分 3分'
    ]
);

$玩家加倍 = array(
    'event' => "Msg_JDDDZ_Act_Beishu",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'beishu' => '0,不加 1 加倍'
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'beishu' => '0,不加 1 加倍'
    ]
);
$玩家打牌 = array(
    'event' => "Msg_JDDDZ_Act_Da",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'cards' => [] //牌
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'cards' => [], //牌
        'type' => '牌型'
    ]
);

$玩家过 = array(
    'event' => "Msg_JDDDZ_Act_Pass",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => []
);

$玩家托管 = array(
    'event' => "Msg_JDDDZ_Act_Tuo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'istuo' => '0不托管 1托管'
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'istuo' => '0不托管 1托管'
    ]
);

$玩家准备 = array(
    'event' => "Msg_JDDDZ_Act_Ready",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
    ]
);

$玩家退出 = array(
    'event' => "Msg_JDDDZ_Out",
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
    'event' => "Msg_JDDDZ_Add",
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
        'seat' => '座位号',
        'sex' => '性别 1男 2女'
    ]
);

$玩家进入游戏 = array(
    'event' => "Msg_JDDDZ_UserState",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'online' => '0不在线 1 在线'
    ]
);

$分数变化 = array(
    'event' => "Msg_JDDDZ_ChangGold",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid'=>'玩家uid',
        'gold' => '玩家分数', 
    ]
);