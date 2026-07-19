<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */

$刷新房间 = array(
    'event' => "Msg_ESYD_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'stage' => '1 押注 2 发牌 3 保险 4 说话 5 结算阶段',
        'players' => [
            [
                'nickname' => '昵称',
                'gold' => '金币',
                'uid' => 'uid',
                'headimgurl' => '用户头像地址',
                'seat' => '座位号',
            ]
        ],
        'cards' => ['uid' => ['手牌数组']],
        'notice' => [],
        'level' => '等级',
        'doublescore' => '低分',
        'acts' => ['座位号' => [['bet' => '下注金额', 'cards' => ['手牌数组'], 'bx' => '保险金额', 'uid' => 'uid']]],
        'bankercards' => [],
        'startseat' => '开始座位号',
    ],
);


$位置动作 = array(
    'event' => "Msg_ESYD_PlayerAct",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'nickname' => '昵称',
        'gold' => '金币',
        'uid' => 'uid',
        'headimgurl' => '用户头像地址',
        'seat' => '座位号',
    ]
);


$下注阶段 = array(
    'event' => "Msg_ESYD_GameStart",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'startseat' => '开始座位号',
        'time' => '时间',
    ]
);


$下注 = array(
    'event' => "Msg_ESYD_UserBet",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [
        'gold' => '金额',
        'seat' => '座位号',
    ],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => 'uid',
        'seat' => '座位号',
        'gold' => '金额',
    ]
);


$发牌 = array(
    'event' => "Msg_ESYD_FaCards",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'banker' => '庄家明牌pid',
        'player' => ['座位号' => ['手牌数组']]  //对象
    ]
);


$保险阶段 = array(
    'event' => "Msg_ESYD_CallKeep",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => []
);


$买保险 = array(
    'event' => "Msg_ESYD_BuyKeep",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [
        'seat' => '座位号',
        'buy' => '1 买 0 不买',
    ],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'seat' => '座位号',
        'buy' => '1 买 0 不买',
    ]
);


$发牌 = array(
    'event' => "Msg_ESYD_FaCards",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'banker' => '庄家明牌',
        'player' => ['座位号' => ['手牌数组']]  //对象
    ]
);


$庄家黑杰克判断 = array(
    'event' => "Msg_ESYD_BankerCards",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'banker' => ['庄家手牌为两张时为黑杰克'],
        ]
);


$说话阶段 = array(
    'event' => "Msg_ESYD_CallUserAct",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'seat' => '座位号',
        'uid' => 'uid',
        'time' => '时间',
    ]
);


$要牌 = array(
    'event' => "Msg_ESYD_UserTouch",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [
        'act' => '0 停牌 1 要牌 2 双倍 3 分牌',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'allcards' => [['分牌数组']],
        'card' => '停牌为空，要牌为pid',
        'act' => '0 停牌 1 要牌 2 双倍 3 分牌',
        'ponit' => '',
    ],
);


$要牌 = array(
    'event' => "Msg_ESYD_BankerAllCards",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'cards' => [],
    ],
);


$结算 = array(
    'event' => "Msg_ESYD_Result",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'seatwin' => ['座位号' => '得分'],
        'factwin' => ['座位号' => '未抽水得分'],
        'userwin' => ['uid' => '得分'],
    ]
);


$退出房间 = array(
    'event' => "Msg_ESYD_Out",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => 'uid',
        'gold' => '玩家金币'
    ]
);