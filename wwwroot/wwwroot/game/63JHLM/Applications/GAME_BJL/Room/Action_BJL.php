<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */

$刷新房间 = array(
    'event' => "Msg_BJL_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'history' => [],
        'allbet' => ['庄对子 1 闲对子 2 庄赢 3 闲赢 4 平 5 庄天王 6 闲天王 7 同点平 8' => '下注金额'],
        'bankerlist' => ['当前上庄列表uid'],
        'allnum' => '总人数',
        'mybet' => ['庄对子 1 闲对子 2 庄赢 3 闲赢 4 平 5 庄天王 6 闲天王 7 同点平 8' => '下注金额'],
        'time' => '阶段剩余时间',
        'stage' => '游戏阶段 1 下注 2 结算',
        'banker' => [
            'nickname' => '昵称',
            'gold' => '金币',
            'circle' => '当前局数',
            'uid' => 'uid',
        ],
        'player' => [
            'uid' => 'uid',
            'gold' => '总分',
            'name' => '名字',
        ]
    ]
);

$获取玩家信息 = array(
    'event' => "Msg_BJL_GetUserList",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => [
            'headimgurl' => '用户头像地址',
            'nickname' => '昵称',
            'gold' => '玩家金币',
        ]
    ]
);

$位置动作 = array(
    'event' => "Msg_BJL_PlayerAct",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => 'uid',
    ]
);

$下注阶段 = array(
    'event' => "Msg_BJL_StageBet",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'time' => '时间'
    ]
);

$玩家下注 = array(
    'event' => "Msg_BJL_ActBet",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'region' => '庄对子 1 闲对子 2 庄赢 3 闲赢 4 平 5 庄天王 6 闲天王 7 同点平 8',
        'gold' => '下注金额',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'region' => '庄对子 1 闲对子 2 庄赢 3 闲赢 4 平 5 庄天王 6 闲天王 7 同点平 8',
        'gold' => '下注金额',
    ]
);

$同步下注信息 = array(
    'event' => "Msg_BJL_SysActBet",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bets' => ['庄对子 1 闲对子 2 庄赢 3 闲赢 4 平 5 庄天王 6 闲天王 7 同点平 8' => '下注金额'],
    ]
);

$结算阶段 = array(
    'event' => "Msg_BJL_StageEnd",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'result' => ['庄对子 1 闲对子 2 庄赢 3 闲赢 4 平 5 庄天王 6 闲天王 7 同点平 8' => '1中奖 0 未中奖'],
        'cards' => [
            'banker' => ['pid', 'pid'],  //庄
            'player' => ['pid', 'pid']   //闲
        ],
        'bigwiner' => [
            'uid' => [
            'nickname' => '昵称',
            'win' => '金币',
                ]],
        'bet' => '下注金额',
        'win' => '得分',
        'time' => '时间'
    ]
);

$玩家上庄 = array(
    'event' => "Msg_BJL_ToBanker",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [
        'stage' => '1 上庄 0 取消',
    ],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'list' => ['当前上庄uid'],
    ]
);

$庄家信息 = array(
    'event' => "Msg_BJL_BankerInfo",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'nickname' => '庄家昵称',
        'gold' => '金币',
        'uid' => 'uid',
    ]
);

$退出房间 = array(
    'event' => "Msg_BJL_Out",
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