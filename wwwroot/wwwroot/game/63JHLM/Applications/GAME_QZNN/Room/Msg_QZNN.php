<?php

$刷新房间 = array(
    'event' => "Msg_QZNN_RoomInfo",
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
                'handsnum' => '手牌张数',
                'callbanker' => 0, //-1没有操作 0不叫  1一倍 2两倍 4 四倍
                'ishow' => 0, //是否看牌 1看牌
                'allWinScore' => 0, //总共输赢
                'winScore' => 0, //上局输赢 
                'bet' => '玩家下注金额',
                'isrealy' => '是否参与本本局',
                'seat'=>'座位号'
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
    'event' => "Msg_QZNN_CallBanker",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'CallBankerArr' => [], //叫庄可选参数
        'time' => '时间',
    ]
);

$通知玩家下注 = array(
    'event' => "Msg_QZNN_Bet",
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
    'event' => "Msg_QZNN_FaCards",
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
                'type'=>'牌型',
                'hands' => ['手牌'], //4-黑桃>3-红桃>2-梅花>1-方块
            ]
        ],

        'time' => '时间',
    ]
);

define('TYPE_NOT', 1); //没牛
define('TYPE_ONE', 2); //牛一
define('TYPE_TWO', 3); //牛二
define('TYPE_THREE', 4); //牛三
define('TYPE_FORE', 5); //牛四
define('TYPE_FIVE', 6); //牛五
define('TYPE_SIX', 7); //牛六
define('TYPE_SEVEN', 8); //牛七
define('TYPE_EIGHT', 9); //牛八
define('TYPE_NINE', 10); //牛九
define('TYPE_TEN', 11); //牛牛
define('TYPE_FORE_F', 12); //四花牛
define('TYP_EFIVE_F', 13); //五花牛

$通知玩家结算 = array(
    'event' => "Msg_QZNN_Res",
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
                'score' => '输赢分数',
                'gold' => '当前玩家身上的金币'
            ]
        ],
        'time' => '时间',
    ]
);

$玩家叫庄 = array(
    'event' => "Msg_QZNN_Act_CallBanker",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'type' => '-1没有操作 0不叫  1一倍 2两倍 4 四倍',
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'type' => '0不叫  1一倍 2两倍 4四倍',
        'uid' => '玩家uid',
    ]
);

$通知玩家多次不操作是否退出 = array(
    'event' => "Msg_QZNN_DelayOut",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'time' => '时间',
    ]
);

$玩家多次不操作是否退出 = array(
    'event' => "Msg_QZNN_ACT_Delay",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'type' => '0退出 1继续游戏'
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'type' => '0退出 1继续游戏'
    ]
);
$玩家下注 = array(
    'event' => "Msg_QZNN_Act_Bet",
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
    'event' => "Msg_QZNN_Act_Show",
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
    'event' => "Msg_QZNN_Out",
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
    'event' => "Msg_QZNN_Add",
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

$分数变化 = array(
    'event' => "Msg_QZNN_ChangGold",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        '玩家uid' => '玩家分数', 
    ]
);
