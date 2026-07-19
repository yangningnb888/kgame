<?php
/**
 * Created by PhpStorm.
 * User: 赵薇
 * Date: 2021/7/20
 * Time: 10:00
 */

$退出房间 = array(
    'event' => "Msg_JCBY_Out",
    'area' => 0,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'seat' => '动作座位号'
    ]
);


$位置动作 = array(
    'event' => "Msg_JCBY_PlayerAct",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'seat' => '动作座位号',
        'player' => [
            'uid' => 'uid',
            'gold' => '总分',
            'name' => '名字',
        ]
    ]
);

$刷新房间 = array(
    'event' => "Msg_JCBY_RoomInfo",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'time_ms' => '当前毫秒级时间戳',
        'stoptime' => '暂停时间戳',
        'fishtideid' => '鱼潮id',
        'lasttidetime' => '上次鱼潮开启时间戳（毫秒级）',
        'fishlist' => array(
            'id' => [
                'id' => '',
                'type' => '种类id',
                'wayid' => '路径id',
                'createtime' => '出生时间',
                'speed' => '速度',
                'fishes' => ['鱼阵对应生成的小鱼'],
            ],
        ),

        'players' => array(
            'uid' => [
                'seat' => '座位号',
                'name' => '昵称',
                'gold' => '金币',
                'level' => '炮台等级',
            ],
        ),
        'setting' => [
            'doublescore' => '底分',
            'level' => '房间等级',
            'min_gold' => '参赛最低分',
            'max_gold' => '参赛最高分',
        ]
    ]
);

$生成鱼 = array(
    'event' => "Msg_JCBY_CreateFish",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => array(
        'fish' => [
            'id' => [
                'type' => '种类id',
                'wayid' => '路径id',
                'createtime' => '出生时间',
                'speed' => '速度',
                'couples' => ['炸弹鱼对应死亡的types'],
                'fishes' => ['鱼阵对应生成的小鱼'],
                'circlepoint' => '圆圈队列中心', //圆圈队列中心
                'endtime' => '游出时间',    //游出时间
            ],
        ]
    )
);

$切换炮台 = array(
    'event' => "Msg_JCBY_ActChange",
    'area' => 1,
    'uid' => 0,
    // cs
    'data' => [
        'level' => '炮台等级'
    ],
    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'seat' => '操作玩家',
        'level' => '炮台等级'
    ]
);

$发射炮弹 = array(
    'event' => "Msg_JCBY_ActShoot",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'level' => '炮弹等级',
        'x' => 'X轴坐标',
        'y' => 'Y轴坐标',
        'angle' => '角度',
        'dir_x' => 'X轴方向',
        'dir_y' => 'Y轴方向',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'seat' => '发射玩家',
        'level' => '炮弹等级',
        'x' => 'X轴坐标',
        'y' => 'Y轴坐标',
        'angle' => '角度',
        'dir_x' => 'X轴方向',
        'dir_y' => 'Y轴方向',
        'bulletid' => '子弹编号',
    ],
);

$捕获鱼群 = array(
    'event' => "Msg_JCBY_GetFish",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'bulletid' => '子弹编号',
        'fish' => '<)))><<编号',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'seat' => '发射玩家',
        'bulletid' => '炮弹编号',
        'gold' => '发射玩家总金币',
        'shootid' => '打中鱼id',
        'fish' => array('id1单个鱼' => 'win','id2鱼阵' => ['num' => 'win']),
        'types' => ['闪电特效鱼种类'],
    ]
);

$锁定 = array(
    'event' => "Msg_JCBY_Locking",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'fish' => '<)))><<编号',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => '玩家uid',
        'fish' => '<)))><<编号',
    ]
);

$鱼潮 = array(
    'event' => "Msg_JCBY_FishTide",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'tide' => '鱼潮编号',
    ]
);

$更换炮台 = array(
    'event' => "Msg_JCBY_FishBattery",
    'area' => 1,
    'uid' => 0,

    // cs
    'data' => [
        'battery' => '编号',
    ],

    // sc
    'status' => '1成功 其他失败',
    'msg' => "对应的提示",
    'data' => [
        'uid' => 'uid',
        'battery' => '编号',
    ]
);