<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */

$刷新房间 = array(
    'event' => "Msg_DZPK_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'stage' => '1 发牌 2 押注 3 结算阶段',
        'players' => [
            [
                'nickname' => '昵称',
                'gold' => '金币',
                'uid' => 'uid',
                'headimgurl' => '用户头像地址',
                'seat' => '座位号',
                'cards' => [],
            ]
        ],
        'curbet' => ['uid' => ['act' => '1 小盲注 2 大盲注 3 加注 4 跟注 5 弃牌 6 全下', 'gold' => ['金额']]],
        'publiccards' => ['公共牌id'],
        'notice' => [],
        'allbet' => '底池',
        'bankeruid' => '庄家uid',
        'level' => '等级',
        'doublescore' => '大盲注金额',
        'allbets' => ['uid' => '下注金额']
    ]
);

$位置动作 = array(
    'event' => "Msg_DZPK_PlayerAct",
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

$发牌 = array(
    'event' => "Msg_DZPK_FaCards",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'cards' => [],
        'ingame' => ['参与uid'],
    ]
);

$公共牌 = array(
    'event' => "Msg_DZPK_PublicCards",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'cards' => [],
        'px' => ['uid' => 'px']
    ]
);

$下注阶段 = array(
    'event' => "Msg_DZPK_StageBet",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bets' => ['uid' => '大小盲注金额'],
    ]
);

$通知玩家操纵 = array(
    'event' => "Msg_DZPK_CallUserAct",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '操纵玩家uid',
        'minbet' => '金额',
        'time' => '时间',
    ]
);

$玩家下注 = array(
    'event' => "Msg_DZPK_ActBet",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'gold' => '下注金额 -1 为弃牌',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'act' => '3 加注 4 跟注 5 弃牌',
        'gold' => '下注金额',
    ]
);

$结算 = array(
    'event' => "Msg_DZPK_Result",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'winner' => '赢家uid',
        'wingold' => '赢分',
        'upgold' => ['uid' => '金额'],
        'cards' => [
            'uid' => [
            'cards' => ['最大组合pid'],
            'value' => '牌型价值'
        ]]
    ]
);


$退出房间 = array(
    'event' => "Msg_DZPK_Out",
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