<?php

$刷新房间 = array(
    'event' => "Msg_QZSG_RoomInfo",
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
                'callbanker' => 0, //-1没有操作 0不叫  1叫了 
                'ishow' => 0, //是否看牌 1看牌
                'allWinScore' => 0, //总共输赢
                'winScore' => 0, //上局输赢 
                'seat' => 0, //座位号
                'isrealy' => 0 //是否参与本局
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
    'event' => "Msg_QZSG_CallBanker",
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
    'event' => "Msg_QZSG_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'time' => '时间',
        'banker' => '庄家uid'

    ]
);


$通知玩家发牌 = array(
    'event' => "Msg_QZSG_FaCards",
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
                'type' => '牌型 1零点 2一点 3二点 4三点 5四点 6五点 7六点 8七点 9八点 10九点 11三公 12炸弹 13爆玖'
            ]
        ],

        'time' => '时间',
    ]
);

$通知玩家结算 = array(
    'event' => "Msg_QZSG_Res",
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
    'event' => "Msg_QZSG_Act_CallBanker",
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
    'event' => "Msg_QZSG_DelayOut",
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
    'event' => "Msg_QZSG_ACT_Delay",
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
    'event' => "Msg_QZSG_Act_Bet",
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
    'event' => "Msg_QZSG_Act_Show",
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
    'event' => "Msg_QZSG_Out",
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
    'event' => "Msg_QZSG_Add",
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
        'gold' => '玩家金币',
        'seat'=>'座位号'
    ]
);

$分数变化 = array(
    'event' => "Msg_QZSG_ChangGold",
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
