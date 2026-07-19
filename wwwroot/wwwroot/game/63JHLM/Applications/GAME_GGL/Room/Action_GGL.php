<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */

$刷新房间 = array(
    'event' => "Msg_GGL_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'setting' => [
            'doublescore' => '底分',
            'level' => '等级',
            'min_gold' => '最低入场分',
            'max_gold' => '最高入场分',
            'vals' => [
                'minprize' => '最低奖金',
                'maxprize' => '最高奖金',
            ],
        ]
    ]
);

$双响炮结算 = array(
    'event' => "Msg_GGL_StageStart",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'flag' => ['目标棋子'],
        'myarea' => [
            'logo' => '棋子',
            'gold' => '金额',
        ],
        'win' => '奖金',
    ]
);

$夜市捞鱼结算 = array(
    'event' => "Msg_GGL_StageStart",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'fish' => '条数',
        'gold' => ['金额'],
        'win' => '奖金',
    ]
);

$麻将结算 = array(
    'event' => "Msg_GGL_StageStart",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'flag' => [],
        'myarea' => [],
        'gold' => '金额',
        'win' => '奖金',
    ]
);

$退出房间 = array(
    'event' => "Msg_GGL_Out",
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

$自定义购买 = array(
    'event' => "Msg_GGL_StartMore",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [
        'num' => '数量',
    ],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'golds' => [],
    ]
);