<?php

/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */

$刷新房间 = array(
    'event' => "Msg_HBSL_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'doublescore' => '底分',
        'playerCount' => '当前房间人数',
        'level' => '等级'
    ]
);

$获取玩家信息 = array(
    'event' => "Msg_HBSL_GetUserList",
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
            'pictureframe' => '头像框',
            'gold' => '玩家金币',
            'avoidnum' => '避雷次数',
            'hbgold' => '红包金额'
        ]
    ]
);

$玩家发红包 = array(
    'event' => "Msg_HBSL_Act_Fa",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [
        'score' => '金额',
        'num' => '个数',
        'thunder' => '雷点'
    ],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'nickname' => '昵称',
        'score' => '金额',
        'num' => '个数',
        'thunder' => '雷点',
        'id' => '红包ID',
        'headimgurl' => '头像框'
    ]
);

$玩家发抢 = array(
    'event' => "Msg_HBSL_Act_Qiang",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'id' => '红包id'
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'id' => '红包id',
        'nickname' => '玩家昵称',
        'uid' => '玩家uid',
        'score' => '红包金额',
        'boom' => '炸弹金额',
        'gold' => '玩家当前金币数',
        'jackpost' => '奖池分数',
        'shrinkScore' => '抽水后的分数',
        'type' => '1豹子 2顺子 0没有类型'
    ]
);

$红包时间过期后退还通知 = array(
    'event' => "Msg_HBSL_Return",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'score' => '红包退还金额',
        'gold' => '玩家当前金币数',
    ]
);

$查看详情 = array(
    'event' => "Msg_HBSL_Act_Details",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'id' => '红包id'
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'players' => [
            '玩家uid' => [
                'nickname' => '昵称',
                'score' => '中奖分数',
                'code' => '是否踩雷 0 踩雷 1没有踩雷',
                'headimgurl' => '用户头像地址',
                'date' => '时间',
            ]
        ],
        'score' => '金额',
        'num' => '个数',
        'thunder' => '雷点',
    ]
);

$退出房间 = array(
    'event' => "Msg_HBSL_Out",
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

$游戏记录详情 = array(
    'event' => "Msg_HBSL_HistoryInfo",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [
        'type' => 1, //1 领取发出红包 2 发出红包
        'id' => '数组键值'
    ],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => 'uid',
        'gold' => '玩家金币'
    ]
);

$游戏记录 = array(
    'event' => "Msg_HBSL_History",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [
        'type' => 1 //1 领取发出红包 2 发出红包
    ],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'history' => [
            1 => [
                'list' => [
                    [
                        'profit' => '实际红包金额',
                        'date' => '红包发出的时间',
                        'receive' => '一共抢了多少个',
                        'score' => '红包金额',
                        'num' => '红包总个数',
                        'thunder' => '红包雷点',
                        'nickname' => '发红包的昵称',
                        'boom' => '赔付',
                        'shrinkScore' => '抽水后的分数',
                        'players' => [
                            '玩家uid' => [
                                'nickname' => '昵称',
                                'score' => '中奖分数',
                                'code' => '是否踩雷 0 踩雷 1没有踩雷',
                                'headimgurl' => '用户头像地址',
                                'date' => '时间',
                                'shrinkScore' => '抽水后的分数'
                            ]
                        ],
                    ],
                ],

                'boomnum' => '踩雷数量'
            ], //抢红包 盈利 = shrinkScore-boom

            2 => [
                'list' => [
                    [
                        'profit' => '实际红包金额',
                        'date' => '红包发出的时间',
                        'receive' => '一共抢了多少个',
                        'score' => '红包金额',
                        'num' => '红包总个数',
                        'thunder' => '红包雷点',
                        'nickname' => '发红包的昵称',
                        'boom' => '赔付',
                        'shrinkScore' => '抽水后的分数',
                        'players' => [
                            '玩家uid' => [
                                'nickname' => '昵称',
                                'score' => '中奖分数',
                                'code' => '是否踩雷 0 踩雷 1没有踩雷',
                                'headimgurl' => '用户头像地址',
                                'date' => '时间',
                                'shrinkScore' => '抽水后的分数'
                            ]
                        ],
                    ],
                ],

                'boomnum' => '踩雷数量'
            ] //发红包 盈利 = boom-score
        ]
    ]
);

