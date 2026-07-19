<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */

const BLACK = 1;  //黑
const RED = 2;  //红
const LUCK = 3;  //幸运一击

const PX_BAOZI = 6; //豹子
const PX_SHUNJIN = 5; //顺金
const PX_JINHUA = 4; //金花
const PX_SHUNZI = 3; //顺子
const PX_DUIZI = 2; //对子
const PX_DANZHANG = 1;  //单张

$刷新房间 = array(
    'event' => "Msg_HHDZ_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'history' => [],
        'allbet' => ['黑 1 红 2 幸运一击 3' => '下注金额'],
        'bankerlist' => ['当前上庄列表uid'],
        'allnum' => '总人数',
        'mybet' => ['黑 1 红 2 幸运一击 3' => '下注金额'],
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
    'event' => "Msg_HHDZ_ListInfo",
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
    'event' => "Msg_HHDZ_GetUserList",
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
    'event' => "Msg_HHDZ_PlayerAct",
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
    'event' => "Msg_HHDZ_StageBet",
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
    'event' => "Msg_HHDZ_ActBet",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'region' => '黑 1 红 2 幸运一击 3',
        'gold' => '下注金额',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'region' => '黑 1 红 2 幸运一击 3',
        'gold' => '下注金额',
    ]
);

$同步下注信息 = array(
    'event' => "Msg_HHDZ_SysActBet",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bets' => ['黑 1 红 2 幸运一击 3' => '下注金额'],
    ]
);

$结算阶段 = array(
    'event' => "Msg_HHDZ_StageEnd",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'result' => [
            'blackpx' => '黑方牌型',
            'redpx' => '红方牌型',
            'result' => '开奖结果',
        ],
        'cards' => [
            'black' => ['pid', 'pid'],  //黑
            'red' => ['pid', 'pid']   //红
        ],
        'userwin' => [
            'uid' => '金额变化',
        ],
        'bet' => '',
        'time' => '时间',
    ]
);


$玩家上庄 = array(
    'event' => "Msg_HHDZ_ToBanker",
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
    'event' => "Msg_HHDZ_BankerInfo",
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
    'event' => "Msg_HHDZ_Out",
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