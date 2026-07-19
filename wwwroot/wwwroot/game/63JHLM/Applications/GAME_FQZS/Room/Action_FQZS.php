<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */
const ODDS = [
    11 => 5,  //鸡
    12 => 7,
    13 => 7,
    14 => 7,
    21 => 5,  //兔
    22 => 7,
    23 => 7,
    24 => 7,
    31 => 24, //鲨鱼
    32 => 50, //金鲨鱼
];

$刷新房间 = array(
    'event' => "Msg_FQZS_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'history' => [],
        'allbet' => ['区域id' => '下注金额'],
        'bankerlist' => ['当前上庄列表uid'],
        'allnum' => '总人数',
        'mybet' => ['区域id' => '下注金额'],
        'time' => '阶段剩余时间',
        'stage' => '游戏阶段 1 下注 2 结算',
        'sysbet' => ['神算子区域id' => '下注金额'],
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
        ],
        'playerlist' => [
            [
                'uid' => 'uid',
                'nickname' => '昵称',
                'gold' => '金币',
                'headimgurl' => '头像',
            ]
        ],
    ]
);

$头像列表 = array(
    'event' => "Msg_FQZS_ListInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        [
            'uid' => 'uid',
            'nickname' => '昵称',
            'gold' => '金币',
            'headimgurl' => '头像',
        ]
    ],
);

$获取玩家信息 = array(
    'event' => "Msg_FQZS_GetUserList",
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
            'allbet' => '20局下注金额',
            'wincircle' => '获胜局数',
        ]
    ]
);

$位置动作 = array(
    'event' => "Msg_FQZS_PlayerAct",
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
    'event' => "Msg_FQZS_StageBet",
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
    'event' => "Msg_FQZS_ActBet",
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
        'uid' => '下注uid',
    ]
);


$同步下注信息 = array(
    'event' => "Msg_FQZS_SysActBet",
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
    'event' => "Msg_FQZS_StageEnd",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'result' => '区域id',
        'bankerwin' => '金币',
        'bet' => '下注金额',
        'time' => '时间',
        'userwin' => [
            'uid' => '金额变化',
        ],
        'usergold' => [
            'uid' => '金币',
        ],
    ]
);

$玩家上庄 = array(
    'event' => "Msg_FQZS_ToBanker",
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
    'event' => "Msg_FQZS_BankerInfo",
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
    'event' => "Msg_FQZS_Out",
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