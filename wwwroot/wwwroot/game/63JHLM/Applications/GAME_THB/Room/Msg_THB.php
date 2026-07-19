<?php
$刷新房间 = array(
    'event' => "Msg_THB_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'curgrade' => '当前选中档次',
        'map' => ['地图'],
        'max_multiple' => '最大档次',
        // 'max_gear' => '最大倍数',
        'level' => '房间等级',
        'doublescore' => '底分',
        'min_gold' => '入场最小金币数',
        'max_gold' => '入场最大金币数',
        'gold' => '当前分数',
        'free' => '免费次数',
        'greeConfig' => '档位配置表',
        'max_multiple' => '最大档位',
        'jackpotConfig' => '奖池配置'
    ]
);

$开始游戏 = [
    'event' => "Msg_THB_Start",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'multiple' => '档位',
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'win' => [
            [
                'multiple' => '倍数',
                'type' => '类型'
            ]
        ],
        'score' => '赢分数',
        'gold' => '当前玩家分数',
        'conscore' => '消耗分数',
        'curMap' => [['中奖坐标'], []],
        'map' => ['地图'],
        'curfree' => '中的免费次数',
        'free' => '当前剩余免费次数',
        'type' => '1,2,3,4', //大小奖
        'jackpot' => '中奖池1金额',
        'jackpotleve' => [], //'0没有中奖  1mini，2 min，3 maior，4 supre ',
        'boxType' => ['1绿色,2紫色,3橘色,4绿色+紫色,5绿色+橘色,6紫色+橘色,7绿色+紫色+橘色'],
    ]
];

$退出游戏 = array(
    'event' => "Msg_THB_Out",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'gold' => '玩家分数'
    ]
);
