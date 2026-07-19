<?php

$刷新房间 = array(
    'event' => "Msg_SRNN_RoomInfo",
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
                'nickname' => '玩家昵称',
                'headimgurl' => '头像',
                'istuo' => '托管状态 0没有托管 1总数的最大 2最大的1/2 3最大的1/4 4最大的1/8',
                'handsnum' => '手牌张数',
                'callbanker' => 0, //0没有操作 叫庄状态 1没叫 2叫了
                'ishow' => 0, //是否看牌 1看牌
                'ready' => 0, //是否准备 1 准备
                'allWinScore' => 0, //总共输赢
                'winScore' => 0, //上局输赢 
                'bet' => '玩家下注金额'
            ],
        ],

        'hands' => [], //玩家手牌
        'doublescore' => '底分',
        'banker' => '庄家uid',
        'gameState' => '游戏阶段',
        'msg' => [] //不同阶段补发消息
    ]
);


$通知玩家叫地主 = array(
    'event' => "Msg_SRNN_CallBanker",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '加庄uid',
        'time' => '时间',
    ]
);

$通知玩家下注 = array(
    'event' => "Msg_SRNN_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bet' => [],
        'time' => '时间',
        'banker' => '庄家uid'

    ]
);

$通知玩家发牌 = array(
    'event' => "Msg_SRNN_FaCards",
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
                'hands' => ['手牌'], //4-黑桃>3-红桃>2-梅花>1-方块
            ]
        ], //1401 小王, 1501 大王

        'time' => '时间',
    ]
);

$通知玩家结算 = array(
    'event' => "Msg_SRNN_Res",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'player' => [
            '玩家uid' => [
                'type' => '牌型 1没牛 2牛一 3牛二 4牛三 5牛四 6牛五 7牛六 8牛七 9牛八 10牛九 11牛牛 12银牛 13 金牛',
                'score' => '输赢分数',
                'gold' => '当前玩家身上的金币'
            ]
        ],
        'time' => '时间',
    ]
);

$玩家叫庄 = array(
    'event' => "Msg_SRNN_Act_CallBanker",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'type' => '1不叫 2 叫',
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'type' => '1不叫 2 叫',
        'uid' => '玩家uid',
    ]
);

$玩家下注 = array(
    'event' => "Msg_SRNN_Act_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'bet' => '下注金额',
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bet' => '下注金额',
        'uid' => '玩家uid',
    ]
);

$申请看牌 = array(
    'event' => "Msg_SRNN_Act_Show",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid' //修改
    ]
);


$玩家退出房间 = array(
    'event' => "Msg_SRNN_Out",
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

$玩家进入房间 = array(
    'event' => "Msg_SRNN_Add",
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
        'headimgurl' => '头像框',
        'gold' => '玩家金币'
    ]
);

$玩家准备 = array(
    'event' => "Msg_SRNN_Ready",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid'
    ]
); 

$托管 = array(
    'event' => "Msg_SRNN_Act_Tuo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'istuo' => '0取消托管 1总数的最大 2最大的1/2 3最大的1/4 4最大的1/8'
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid', //修改
        'istuo' => '0取消托管 1总数的最大 2最大的1/2 3最大的1/4 4最大的1/8'
    ]
);

$分数变化 = array(
    'event' => "Msg_SRNN_ChangGold",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        '玩家uid' => '玩家分数', //修改
    ]
);