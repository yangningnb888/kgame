<?php
$刷新桌面 = array(
    'event' => "Msg_ZJH_RoomInfo",
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
                'online' => '玩家在线状况 1在线 0离线',
                'ready' => '准备状态 0没有准备 1准备 ',
                'ishow' => '0没有看牌 1看牌',
                'ispass' => '0没有弃牌 1弃牌',
                'bat' => '下注分数',
                'seat' => '座位号',
                'headimgurl' => '头像',
                'isfail' => '比牌失败',
                'brightCard' => '0没有亮牌 1亮牌'
            ]
        ],
        'gameState' => '游戏阶段',
        'hands' => '玩家手牌',
        'table' => '桌子上的钱',
        'doublescore' => '底分',
        'banker' => '庄家uid',
        'circle' => '当前圈数',
        'curbet' => '当前下注最大数',
        'msg' => [
            '阶段消息'
        ],
        'betAll' => 0, //全下金额
    ]
);

$发牌 = array(
    'event' => "Msg_ZJH_Fa",
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
                'cardsNum' => '玩家手牌分数',
                'gold' => '当前身上金币',
                'curscore' => '消耗金币'
            ]
        ],
        'table' => '桌子上的钱'
    ]
);

$通知下注 = array(
    'event' => "Msg_ZJH_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '当前下注玩家',
        'curbet' => '没有看牌当前下注金额',
        'circle' => '当前局数',
        'betAll' => '全下金额',
        'time' => '时间'
    ]
);

$通知玩家结算 = array(
    'event' => "Msg_ZJH_Res",
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
                'ompare' => ['比牌玩家'],
                'hands' => ['手牌'],
            ],

        ],
        'time' => '时间',
    ]
);

$玩家丢牌 = array(
    'event' => "Msg_ZJH_Act_Discard",
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

$玩家下注 = array(
    'event' => "Msg_ZJH_Act_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'bet' => '下注分数'
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'bet' => '0,不加 1 加倍',
        'gold' => '玩家分数'
    ]
);

$玩家比牌 = array(
    'event' => "Msg_ZJH_Act_Compare",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'uid' => '比牌的玩家uid'
    ],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'winuid' => '赢玩家uid',
        'bet' => '下注金额',
        'gold' => '当前比牌玩家身上的金额',
        'failuid'=>'输玩家的uid'
    ]
);

$玩家看牌 =  array(
    'event' => "Msg_ZJH_Act_Show",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'cards' => ['牌id'],
        'uid' => '看牌玩家'
    ]
);

$玩家亮牌 = array(
    'event' => "Msg_ZJH_Act_BrightCard",
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
$玩家全下 = array(
    'event' => "Msg_ZJH_Act_BetAll",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],

    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'score' => '分数'
    ]
);

$玩家过 = array(
    'event' => "Msg_ZJH_Act_Pass",
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
    'event' => "Msg_ZJH_Act_Tuo",
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
    'event' => "Msg_ZJH_Act_Ready",
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
    'event' => "Msg_ZJH_Out",
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
    'event' => "Msg_ZJH_Add",
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

$玩家进入游戏 = array(
    'event' => "Msg_ZJH_UserState",
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
    'event' => "Msg_ZJH_ChangGold",
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