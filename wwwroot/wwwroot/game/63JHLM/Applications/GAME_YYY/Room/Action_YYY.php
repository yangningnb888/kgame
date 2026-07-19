<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */
const SINGLE = 1;  //单
const DOUBLE = 2;   //双
const BIGPOINT = 3;  //大
const SMALLPOINT = 4;  //小
const ONEPOINT = 10;  //单个骰子
const COUPLE = 20;    //对子
const BAOZI = 30;  //豹子
const TWOPOINT = 100;  //两个骰子
const ALLSUM = 300;   //三个骰子点数和

const ODDS = [
    1 => 2, 2 => 2, 3 => 2, 4 => 2,
    11 => 2, 12 => 2, 13 => 3, 14 => 3, 15 => 4, 16 => 4, 21 => 9, 22 => 9, 23 => 9, 24 => 9, 25 => 9, 26 => 9,
    30 => 25, 31 => 151, 32 => 151, 33 => 151, 34 => 151, 35 => 151, 36 => 151,
    112 => 6, 113 => 6, 114 => 6, 115 => 6, 116 => 6, 123 => 6, 124 => 6, 125 => 6, 126 => 6, 134 => 6, 135 => 6, 136 => 6, 145 => 6, 146 => 6, 156 => 6,
    304 => 51, 305 => 19, 306 => 15, 307 => 13, 308 => 9, 309 => 7, 310 => 7, 311 => 7, 312 => 7, 313 => 9, 314 => 13, 315 => 15, 316 => 19, 317 => 51
];


$刷新房间 = array(
    'event' => "Msg_YYY_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'history' => [
            'points' => [
                ['点数']
            ],
            'win' => ['区域id' => 1]
        ],
        'allcount' => [
            ['区域id' => 1],
        ],
        'allbet' => ['区域id' => '下注金额'],
        'bankerlist' => ['当前上庄列表uid'],
        'allnum' => '总人数',
        'mybet' => ['区域id' => '下注金额'],
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
    'event' => "Msg_YYY_GetUserList",
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
    'event' => "Msg_YYY_PlayerAct",
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
    'event' => "Msg_YYY_StageBet",
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
    'event' => "Msg_YYY_ActBet",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'region' => '区域id',
        'gold' => '下注金额',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'region' => '区域id',
        'gold' => '下注金额',
    ]
);

$同步下注信息 = array(
    'event' => "Msg_YYY_SysActBet",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bets' => ['区域id' => '下注金额'],
    ]
);

$结算阶段 = array(
    'event' => "Msg_YYY_StageEnd",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'result' => ['区域id' => '1中奖'],
        'points' => [],
        'bankerwin' => '金币',
        'win' => '得分',
        'gold' => 0,
        'time' => '时间'
    ]
);

$玩家上庄 = array(
    'event' => "Msg_YYY_ToBanker",
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
    'event' => "Msg_YYY_BankerInfo",
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
    'event' => "Msg_YYY_Out",
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