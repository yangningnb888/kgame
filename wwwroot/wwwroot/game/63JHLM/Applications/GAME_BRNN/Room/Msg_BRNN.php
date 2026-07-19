<?php

$刷新房间 = array(
    'event' => "Msg_BRNN_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        // 'players' => [
        //     '玩家uid' => [
        //         'gold' => '玩家分数',
        //         'nickname' => '玩家昵称',
        //         'headimgurl' => '头像',
        //     ],
        // ],
        'doublescore' => '底分',
        'history' => [
            1 => [
                'info' => ['1赢 0输'],
                'win' => '赢得局数',
                'tran' => '输的局数'
            ], //天
            2 => [
                'info' => ['1赢 0输'],
                'win' => '赢得局数',
                'tran' => '输的局数'
            ], //地
            3 => [
                'info' => ['1赢 0输'],
                'win' => '赢得局数',
                'tran' => '输的局数'
            ], //玄
            4 => [
                'info' => ['1赢 0输'],
                'win' => '赢得局数',
                'tran' => '输的局数'
            ] //黄
        ],

        'banker' => [
            'uid' => '庄家Uid',
            'num' => '当庄局数',
            'gold' => '玩家分数', //新增
            'nickname' => '玩家昵称', //新增
            'headimgurl' => '头像', //新增
        ],
        'circle' => '当日局数',
        'time' => '当前剩余时间',
        'gameState' => '游戏阶段',
        'applyBanker' => '排队中的庄家个数', //修改
        'hall' => [
            1 => ['score' => 0, 'hands' => []], //天
            2 => ['score' => 0, 'hands' => []], //地
            3 => ['score' => 0, 'hands' => []], //玄
            4 => ['score' => 0, 'hands' => []] //黄
        ], //'桌子上的下注情况',
        'mybat' => [
            1 => 0, //天
            2 => 0, //地
            3 => 0, //玄
            4 => 0 //黄 
        ], //自己下注情况
    ]
);

$通知玩家下注 = array(
    'event' => "Msg_BRNN_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'banker' => [
            'uid' => '庄家Uid',
            'num' => '当庄局数',
            'gold' => '玩家分数', //新增
            'nickname' => '玩家昵称', //新增
            'headimgurl' => '头像', //新增
        ],
        'circle' => '当日局数',
        'applyBanker' => '排队中的庄家个数', //修改
        'time' => '时间',
    ]
);

$通知玩家结算 = array(
    'event' => "Msg_BRNN_Res",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'hall' => [
            1 => [
                'hands' => '手牌',
                'type' => '牌型',
                'win' => '1赢庄家 0输庄家',
                'score' => '输赢分数'
            ] //1天 2地 3玄 4黄
        ],
        'banker' =>
        [
            'hands' => '手牌',
            'type' => '牌型',
        ],

        'player' => [
            '玩家uid' => [
                'nickname' => '玩家昵称',
                'score' => '输赢分数'
            ] //赢钱最多的三个+庄家  修改
        ],
        'time' => '时间',
        'isadvance' => '是否提前结束'
    ]
);

$玩家下注 = array(
    'event' => "Msg_BRNN_Act_Bet",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'bat' => '下注金额',
        'code' => '1天 2地 3玄 4黄',
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bat' => '下注金额',
        'code' => '1天 2地 3玄 4黄',
        'gold' => '玩家分数'
    ]
);

$同步玩家下注 = array(
    'event' => "Msg_BRNN_Table",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'hall' => [ //修改
            1 => 0, //天
            2 => 0, //地
            3 => 0, //玄
            4 => 0 //黄
        ] //当前桌子的情况
    ]
);

$玩家申请当庄 = array(
    'event' => "Msg_BRNN_Act_Banker",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bankerNum' => '申请个数' //修改
    ]
);

$玩家退出当庄 = array(
    'event' => "Msg_BRNN_Act_BankerOut",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'bankerNum' => '申请个数' //修改
    ]
);

$玩家退出房间 = array(
    'event' => "Msg_BRNN_Out",
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
    'event' => "Msg_BRNN_Add",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        // 'uid' => '玩家uid',
        // 'nickname' => '昵称',
        // 'headimgurl' => '头像框',
        // 'gold' => '玩家金币'   
    ] //删除
);

$玩家信息 = array(
    'event' => "Msg_BRNN_GetUserList",
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
            ],
        ],
    ]
);//新增