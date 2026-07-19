<?php
$刷新房间 = array(
    'event' => "Msg_LDYX_RoomInfo",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'free' => '当前免费次数',
        'level' => '房间等级',
        'doublescore' => '底分',
        'min_gold' => '入场最小金币数',
        'max_gold' => '入场最大金币数',
        'gold' => '当前分数',
        'curstake' => ['当前押注情况'],
    ]
);

$开始游戏 = array(
    'event' => "Msg_LDYX_Start",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'stake' => [
            '类型 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18' => '倍数 最大倍数99',
        ], //押注情况

        //define('LDYX_TYPE_BAR', 1); //BAR
        // define('LDYX_TYPE_BAR50', 2); //BAR X50
        // define('LDYX_TYPE_SEVEN', 3); // 7
        // define('LDYX_TYPE_SEVEN3', 4); //7 X3
        // define('LDYX_TYPE_STAR', 5); //星星
        // define('LDYX_TYPE_STAR3', 6); //星星 X3
        // define('LDYX_TYPE_WATERMENLON', 7); //西瓜
        // define('LDYX_TYPE_WATERMENLON3', 8); //西瓜 X3
        // define('LDYX_TYPE_BELL', 9); //铃铛 
        // define('LDYX_TYPE_BELL3', 10); // 铃铛 X3
        // define('LDYX_TYPE_MANGO', 11); // 柠檬 
        // define('LDYX_TYPE_MANGO3', 12); //柠檬 X3
        // define('LDYX_TYPE_ORANGE', 13); //橘子
        // define('LDYX_TYPE_ORANGE3', 14); //橘子 X3
        // define('LDYX_TYPE_APPLE', 15); // 苹果
        // define('LDYX_TYPE_APPLE3', 16); // 苹果 X3
        // define('LDYX_TYPE_LUCKY', 17); // 蓝色lucky
        // define('LDYX_TYPE_LUCKY_BIG', 18); // 红色lucky
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'gold' => '玩家身上的分数，不包括本局赢得分数',
        'score' => '中奖分数',
        'type' => '类型',
        'countScore' => '赢得总分数',
        'curscore' => '当前消耗的分数',
        'info' => [
            [
                'type' => '类型',
                'score' => '中奖分数',
                'freeArr'=>['免费中的免费的数组']
            ]
        ] //中lucky数据
    ]
);

$比倍 = array(
    'event' => "Msg_LDYX_Than",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [
        'type' => '1大 2小',
        'score' => '参加比倍分数'
    ],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'res' => '骰子点数',
        'gold' => '当前玩家分数',
        'score' => '赢得分数'
    ]
);

$收 = array(
    'event' => "Msg_LDYX_Collect",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'gold' => '当前玩家分数',
    ]
);

$退出游戏 = array(
    'event' => "Msg_LDYX_Out",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'gold' => '分数'
    ]
);

$奖池分数 =  array(
    'event' => "Msg_Game_Jackpot",
    'area' => 1,
    'uid' => 1,
    // cs
    'data' => [],
    //sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'score' => '分数',
    ]
);
