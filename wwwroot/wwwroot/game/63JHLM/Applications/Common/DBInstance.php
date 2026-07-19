<?php
date_default_timezone_set("Asia/Shanghai");

use \GatewayWorker\Lib\DbModel;

class DBInstance
{
    //数据库连接
    public static $db;

    //数据库连接初始化
    public static function Init($dbConfig)
    {
        self::$db = new DbModel(
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['user'],
            $dbConfig['password'],
            $dbConfig['dbname'],
            $dbConfig['tablepre'],
            $dbConfig['charset']
        );
    }

    //获取最近执行的SQL语句
    public static function getQuerySql()
    {
        return self::$db->getQuerySql();
    }

    //分页规则
    public static function Page($page, $count, $pageSize)
    {
        if ($page == 0) {
            $page = 1;
        }

        $curPage = [
            'page' => 1,
            'totalpage' => 1,
            'limit' => '',
        ];

        if ($count > $pageSize) {
            $totalpage = ceil($count / $pageSize);
            if ($page > $totalpage) {
                $page = $totalpage;
            }

            $curPage['page'] = $page;
            $curPage['totalpage'] = $totalpage;
            $curPage['limit'] = (($page - 1) * $pageSize) . "," . $pageSize;
        }

        return $curPage;
    }

    //获取系统配置
    public static function GetSysConfig($key)
    {
        return self::$db->table('sysconfig')->select('val')->where(array('key' => $key))->one(true);
    }

    //获取用户账号信息
    public static function GetRegister($where)
    {
        return self::$db->table('register')->select('*')->where($where)->asArray()->one();
    }

    //获取用户单个信息
    public static function GetUserOneWord($string, $uid)
    {
        return self::$db->table('user')->select($string)->where(['uid' => $uid])->asArray()->one(true);
    }

    //获取用户信息
    public static function GetUser($where)
    {
        return self::$db->table('user')->select('uid,gold,bank,rcard,bankpass,headimgurl,nickname,sex,pictureframe,battery,openid')->where($where)->asArray()->one();
    }

    //更新玩家在线状态 1在线 0 离线
    public static function UpdateUserOnline($uid, $online = 1, $lasttime = true)
    {
        $updata = ['online' => $online];
        if ($lasttime) {
            $updata['last_time'] = date("Y-m-d H:i:s", time());
        }
        return self::$db->table('user')->where(['uid' => $uid])->update($updata);
    }

    //更新游戏内玩家数量
    public static function UpdateAllIngame()
    {
        self::$db->table('user_possition')->where('1')->update(['gtype' => 0, 'level' => 0]);
        //self::$db->table('gamestatus')->where('1')->update(['status' => 2]);
        //self::$db->table('arcade_bet')->where('1')->delete();
        return self::$db->table('gamestatus')->where('1')->update(['ingame' => 0]);
    }

    //更新玩家在线状态 1在线 0 离线
    public static function UpdateUserString($uid, $string)
    {
        return self::$db->table('user')->where(['uid' => $uid])->update($string);
    }

    public static function UpdateUserStatus($uid, $status)
    {
        self::$db->table('user')->where(['uid' => $uid])->update(['status' => $status]);
        self::$db->table('register')->where(['uid' => $uid])->update(['status' => $status]);
    }

    //获取用户鉴权信息
    public static function GetUserAuth($token)
    {
        return self::$db->table('user_online')->select('*')->where(['token' => $token/*, 'create_time' => ['>=', time()]*/])->asArray()->one();
    }

    //修改令牌时效
    public static function SetTokenTime($uid)
    {
        self::$db->table('user_online')->where(['uid' => $uid])->update(['create_time' => time() + 2592000]);
    }

    //修改玩家账户余额
    public static function IncrementGolds($string, $uid, $num)
    {
        return self::$db->table('user')->where(['uid' => $uid])->increment([$string => $num]);
    }

    //获取robot
    public static function GetRobotList($maxgold, $num, $mingold = ROBOTGOLD)
    {
        return self::$db->table('user')->select('uid,gold,bank,bankpass,headimgurl,nickname,sex')->limit($num)->where('agent=2 and online=2 and type=1 and gold>=' . $mingold . ' and gold<=' . $maxgold)->order('last_time')->asArray()->all('uid');
    }

    /**
     * 获取机器人昵称 (用于跑马灯播报)
     */
    public static function GetNickname()
    {
        $user = self::$db->table('user')->select('nickname,uid')->where(['online' => 2, 'type' => 0, 'agent' => 2])->order('last_time DESC')->asArray()->one();
        self::UpdateUserOnline($user['uid'], 2);
        return $user['nickname'];
    }

    //银行存入  $money > 0 取出 $money < 0
    public static function BankDeposit($uid, $money)
    {
        self::$db->table('user')->where(['uid' => $uid])->increment(['gold' => $money]);
        self::$db->table('user')->where(['uid' => $uid])->increment(['bank' => -$money]);
        return true;
    }

    //获取游戏状态
    public static function GetGameStatus()
    {
        $gamestatus = self::GetGameMaintenance();
        $all = self::$db->table('gamestatus')->select('*')->asArray()->all();
        $ret = [];
        foreach ($all as $key => $value) {
            $ret[$value['seession']] = [];
        }
        foreach ($all as $key => $value) {
            if ($gamestatus != 0) {
                $ret[$value['seession']][$value['gtype']] = 2;
            } else {
                $ret[$value['seession']][$value['gtype']] = $value['status'];
            }
        }
        return $ret;
    }

    //修改游戏内玩家数量
    public static function UpdateInGame($gtype, $num)
    {
        self::$db->table('gamestatus')->where(['gtype' => $gtype])->increment(['ingame' => $num]);
    }

    //获取游戏类别
    public static function GetSeession($gtype)
    {
        return self::GetTableOneWord('gamestatus', 'seession', ['gtype' => $gtype]);
    }

    //修改游戏状态
    public static function UpdateGameStatus($gtype, $status)
    {
        if ($status == 2) {
            self::$db->table('gamestatus')->where(['gtype' => $gtype])->update(['ingame' => 0]);
        }
        return self::$db->table('gamestatus')->where(['gtype' => $gtype])->update(['status' => $status]);
    }

    //修改游戏中人数
    public static function IncrementInGame($gtype, $num)
    {
        return self::$db->table('gamestatus')->where(['gtype' => $gtype])->increment(['ingame' => $num]);
    }

    //绑定玩家上级
    public static function InsertUserSuperior($uid, $superior)
    {
        return self::$db->table('user_superior')->insert(array(
            'uid' => $uid,
            'superior' => $superior,
            'control' => 0,
            'createtime' => MyTools::GET_NOW()
        ));
    }

    //修改玩家上级
    public static function UpdateUserSuperior($uid, $superior)
    {
        return self::$db->table('user_superior')->where(['uid' => $uid])->update(['superior' => $superior, 'createtime' => date("Y-m-d H:i:s", time())]);
    }

    //修改玩家场控状态
    public static function UpdateUserControl($uid, $control = 0, $flagget = 0)
    {
        return self::$db->table('user_superior')->where(['uid' => $uid])->update(['control' => $control, 'flagget' => $flagget, 'curget' => 0]);
    }

    //查询玩家上级
    public static function GetUserSuperior($uid)
    {
        return self::$db->table('user_superior')->select('superior')->where(array('uid' => $uid))->asArray()->one(true);
    }

    //修改场控目标值
    public static function IncrementUserGet($uid, $num)
    {
        $superior = self::GetTableWords('user_superior', 'superior,flagget', ['uid' => $uid]);
        self::$db->table('user_superior')->where(['uid' => $uid])->increment(['curget' => $num]);
        if ($superior) {
            self::$db->table('user_superior')->where(['uid' => $superior['superior']])->increment(['curget' => $num]);
        }

        $res = self::GetTableWords('user_superior', '*', ['uid' => $uid]);
        if ($res) {
            if ($res['control'] > 0 && $res['curget'] >= $res['flagget']) {
                self::UpdateUserControl($uid);
            } elseif ($res['control'] < 0 && $res['curget'] <= $res['flagget']) {
                self::UpdateUserControl($uid);
            }

            $res = self::GetTableWords('user_superior', '*', ['uid' => $superior['superior']]);
            if ($res['control'] > 0 && $res['curget'] >= $res['flagget']) {
                self::UpdateUserControl($superior['superior']);
            } elseif ($res['control'] < 0 && $res['curget'] <= $res['flagget']) {
                self::UpdateUserControl($superior['superior']);
            }
        }
    }

    //修改玩家账户余额
    public static function IncrementExtension($uid, $num)
    {
        $data = self::GetTableOneWord('agent', 'uid', ['uid' => $uid]);
        if (!$data) {
            self::$db->table('agent')->insert(
                [
                    'uid' => $uid,
                    'extension' => 0,
                    'power' => 0
                ]
            );
        }
        return self::$db->table('agent')->where(['uid' => $uid])->increment(['extension' => $num]);
    }

    //设置玩家所在游戏
    public static function SetUserPossition($uid, $gtype, $level)
    {
        $user = self::$db->table('user')->select('*')->where(array('uid' => $uid))->asArray()->one();
        if ($user['type'] != 1) {
            $res = self::$db->table('user_possition')->select('*')->where(array('uid' => $uid))->asArray()->one();
            if ($res) {
                self::$db->table('user_possition')->where(['uid' => $uid])->update(['gtype' => $gtype, 'level' => $level]);
            } else {
                self::$db->table('user_possition')->insert(['uid' => $uid, 'gtype' => $gtype, 'level' => $level]);
            }
        }
    }

    //获取玩家位置
    public static function GetUserPossition($uid)
    {
        $res = self::$db->table('user_possition')->select('*')->where(array('uid' => $uid))->asArray()->one();
        if ($res) {
            return [
                'gtype' => $res['gtype'],
                'level' => $res['level']
            ];
        } else {
            return [
                'gtype' => 0,
                'level' => 0
            ];
        }
    }

    //赛事读取
    public static function GetGameSessions($gtype, $select = 'level,min_gold,max_gold,doublescore')
    {
        return self::$db->table('game_config')->select($select)->where(array('gtype' => $gtype))->asArray()->all('level');
    }

    //AI数量配置
    public static function RobotGameSessions()
    {
        $res = self::$db->table('game_config')->select('gtype,level,ai_base,ai_max')->asArray()->all();
        $ret = [];
        foreach ($res as $key => $value) {
            $ret[$value['gtype']][$value['level']] = [
                'ai_base' => $value['ai_base'],
                'ai_max' => $value['ai_max'],
                'inroom' => 0,
            ];
        }
        return $ret;
    }

    //新增邮件
    public static function InsertMail($uid, $outuid, $currency, $num, $type)
    {
        return self::$db->table('mail')->insert([
            'uid' => $uid,
            'outuid' => $outuid,
            'currency' => $currency,
            'number' => $num,
            'created' => MyTools::GET_NOW(),
            'type' => $type,
            'status' => 0,
        ]);
    }

    //修改邮箱状态  0新 1已读未领 2已读已领 3撤回
    public static function UpdateMailStatus($id, $val)
    {
        return self::$db->table('mail')->where(['id' => $id])->update(['status' => $val]);
    }

    //获取邮箱列表
    public static function GetMailList($uid)
    {
        $list = self::$db->select('mail.id,mail.uid,mail.outuid,mail.currency,mail.number,mail.created,mail.type,mail.status,user.headimgurl,user.pictureframe,user.nickname')->from('user')->alias('user')->join('INNER JOIN jh_mail AS mail ON user.uid= mail.outuid')
            ->where('mail.uid=' . $uid . ' AND mail.status<3')->limit(50)->order('status ASC,id DESC')->asArray()->all();
        return $list;
    }

    //获取新邮件
    public static function GetMailStatus($uid)
    {
        $all = self::$db->table('mail')->select('status')->where(array('uid' => $uid))->limit(50)->asArray()->order('id DESC')->all();
        if (in_array(0, $all) || in_array(1, $all)) {
            return 1;
        } else {
            return 2;
        }
    }

    //获取单个字段
    public static function GetTableOneWord($table, $string, $where)
    {
        return self::$db->table($table)->select($string)->where($where)->asArray()->one(true);
    }

    //获取领取记录
    public static function GetIncomeList($uid)
    {
        return self::$db->table('user_profit')->select('id,formuid,currency,num,created')->where(array('uid' => $uid, 'type' => 2))->limit(50)->asArray()->order('id DESC')->all('id');
    }

    //获取字段
    public static function GetTableWords($table, $string, $where)
    {
        return self::$db->table($table)->select($string)->where($where)->asArray()->one();
    }

    //获取游戏配置
    public static function GetGameConfig($gtype, $level)
    {
        $ret = self::$db->table('game_config')->select('min_pnum,max_pnum,base_score,doublescore,min_gold,max_gold,gametype,vals,controls,rebate')->where(array('gtype' => $gtype, 'level' => $level))->asArray()->one();
        if (!$ret) {
            return [];
        }
        $ret['vals'] = json_decode($ret['vals'], true);
        $ret['controls'] = json_decode($ret['controls'], true);
        return $ret;
    }

    //获取游戏等级
    public static function GetGameLevel($gtype)
    {
        return self::$db->table('game_config')->select('level,benginroom')->where(array('gtype' => $gtype))->asArray()->all('level');
    }

    //获取游戏开放信息
    public static function GetGameBeginRoom($gtype)
    {
        if (BEGINROOM) {
            return self::$db->table('game_config')->select('*')->where(array('gtype' => $gtype))->sum('benginroom');
        } else {
            return 0;
        }
    }

    /**
     * 存储数据
     * @param [type] $table
     * @param [type] $datas
     * @return void
     */
    public static function SaveData($table, $data)
    {
        self::$db->table($table)->insert($data);
        return;
    }

    /**
     * 获取回访数据
     *
     * @param [type] $gtype
     * @return array
     */
    public static function GetBackAll($gtype)
    {
        $data['new'] = self::$db->table('game_back')->select('nickname,type,score,created,playnum,id,level')->where(['gtype' => $gtype])->order('created desc')->asArray()->limit(100)->all();
        $data['history'] = self::$db->table('game_back')->select('nickname,type,score,created,playnum,id,level')->where(['gtype' => $gtype])->order('score desc')->asArray()->limit(100)->all();
        return $data;
    }

    /**
     * 获取底分
     */
    public static function GetDoubleScore($gtype)
    {
        return self::$db->table('game_config')->select('doublescore')->where(['gtype' => $gtype])->asArray()->all();
    }

    /**
     * 获取捕鱼场控
     */
    public static function GetFishGetRand($gtype, $level, $uid)
    {
        if (!GAMECONTROLS || $level == 1) {
            return [
                'rand_get' => 0,
                'control' => 0
            ];
        }

        $info = DBInstance::GetTableWords('user_superior', '*', ['uid' => $uid]);
        $profit = DBInstance::GetTableOneWord('game_config', 'profit', ['gtype' => $gtype, 'level' => $level]);
        $controls = DBInstance::GetTableOneWord('game_config', 'controls', ['gtype' => $gtype, 'level' => $level]);
        $controls = json_decode($controls, true);
        $res = [
            'rand_get' => 0,
            'control' => 0
        ];
        if (!empty($info) && $info['control'] != 0) {
            $res['control'] = $info['control'];
            if ($info['control'] < 0) {
                $res['rand_get'] = -15;
                $res['control'] = -2;
            } elseif ($info['control'] > 0) {
                $res['rand_get'] = $gtype == 10 ? 12 : 20;
                $res['control'] = 2;
            }
            return $res;
        } elseif (!empty($info)) {
            $control = DBInstance::GetTableOneWord('user_superior', 'control', ['uid' => $info['superior']]);
            if ($control < 0) {
                $res['rand_get'] = -15;
                $res['control'] = -2;
            } elseif ($control > 0) {
                $res['rand_get'] = $gtype == 10 ? 12 : 20;
                $res['control'] = 2;
            }
            if (!empty($res['rand_get'])) {
                return $res;
            }
        }

        if (!empty($controls)) {
            if ($profit >= $controls['flag']) {
                $res['rand_get'] = $gtype == 10 ? 10 : 15;
                $res['control'] = 1;
            } elseif ($profit <= -$controls['flag']) {
                $res['rand_get'] = -10;
                $res['control'] = -1;
            }
        }

        return $res;
    }

    /**
     * 棋牌场控
     */
    public static function GetChessCardControl($gtype, $level, $uids)
    {
        $ret = [];
        $user = self::$db->table('user')->select('uid,type')->where(['uid' => ['in', $uids]])->asArray()->all('uid');
        $res = self::GetTableWords('game_config', 'doublescore,profit,controls', ['gtype' => $gtype, 'level' => $level]);
        $res['controls'] = json_decode($res['controls'], true);
        $turn = $res['controls']['doubles'];
        array_multisort(array_column($turn, 'rand'), SORT_DESC, $turn);
        $allrand = 0;
        foreach ($turn as $key => $value) {
            if ($res['profit'] <= $res['doublescore'] * $value['double']) {
                $allrand = $value['rand'];
                break;
            }
        }

        $rand = rand(1, 100);
        shuffle($uids);
        foreach ($uids as $key => $value) {
            if ($user[$value] == 1) {
                $ret[$value] = $rand <= $allrand ? 1 : 0;
            } else {
                $control = self::GetTableOneWord('user_superior', 'control', ['uid' => $value]);
                if ($control != 0) {
                    $ret[$value] = $control < 0 ? -2 : 2;
                } else {
                    $ret[$value] = $rand <= $allrand ? -1 : 0;
                }
            }
        }

        return $ret;
    }


    /**
     * 获取百人场控概率
     */
    public static function GetControlRand($gtype, $level = 5)
    {
        if (!GAMECONTROLS) {
            return 0;
        }

        $res = self::GetTableWords('game_config', 'profit,controls', ['gtype' => $gtype, 'level' => $level]);
        $res['controls'] = json_decode($res['controls'], true);
        $turn = $res['controls']['doubles'];
        array_multisort(array_column($turn, 'rand'), SORT_DESC, $turn);
        foreach ($turn as $key => $value) {
            if ($res['profit'] <= $value['betdouble'] * $res['controls']['maxbet']) {
                return $value['rand'];
            }
        }

        return 0;
    }

    /**
     * 查询回放详情并更新访问量
     *
     * @param [type] $id
     * @return array
     */
    public static function GetBankOne($id)
    {
        self::$db->table('game_back')->where(['id' => $id])->increment(['playnum' => 1]);
        $data = self::$db->table('game_back')->select('vals')->where(['id' => $id])->asArray()->one(true);
        return json_decode($data, true);
    }

    //保存游戏奖池
    public static function SaveJackpot($gtype, $level, $score)
    {
        self::$db->table('game_jackpot')->where(['gtype' => $gtype, 'level' => $level])->increment(['jackpot' => $score]);
    }

    //新增玩家炮台数据
    public static function CheckMotnly($uid)
    {
        $endtime = DBInstance::GetTableOneWord('monthlycard', 'endtime', ['uid' => $uid]);
        $flagtime = strtotime(MyTools::GET_TODAY());
        if ($endtime && strtotime($endtime) > $flagtime) {
            $endtime = strtotime($endtime);
            if ($endtime > $flagtime) {
                return 1;
            }
        }

        self::UpdateUserString($uid, ['battery' => 0, 'pictureframe' => 0]);
        return 0;
    }

    //代理查询玩家
    public static function QueryUserList($superior, $type, $page, $agent = 2)
    {
        $str = $type == 3 ? ' AND jh_user.status=0' : ' AND jh_user.online=' . $type;
        $all = self::$db->select('*')->from('user')->join('INNER JOIN jh_user_superior ON jh_user.uid=jh_user_superior.uid')->
        where('jh_user.agent=' . $agent . ' AND jh_user_superior.superior=' . $superior)->asArray()->all('uid');
        $count = empty($all) ? 0 : count($all);
        $list = self::Page($page, $count, 10);
        $list['list'] = self::$db->table('user')->select('jh_user.uid,jh_user.nickname,jh_user.online,jh_user.gold,jh_user.bank')->join('INNER JOIN jh_user_superior ON jh_user.uid=jh_user_superior.uid')->
        where('jh_user.agent=' . $agent . ' AND jh_user_superior.superior=' . $superior . $str)->limit($list['limit'])->asArray()->order('last_time DESC')->all();
        $_res = self::$db->select('*')->from('user')->join('INNER JOIN jh_user_superior ON jh_user.uid=jh_user_superior.uid')->
        where('jh_user.agent=' . $agent . ' AND jh_user_superior.superior=' . $superior . ' AND jh_user.online=1 AND type=0')->asArray()->all();
        $list['online'] = count($_res);
        $list['online'] = $list['online'] > 0 ? $list['online'] : 0;
        if (!empty($list['list'])) {
            foreach ($list['list'] as $key => $value) {
                $income = self::$db->table('user_profit')->where(['uid' => $value['uid'], 'type' => 1, 'currency' => COIN])->sum('num');
                $expenditure = self::$db->table('user_profit')->where(['uid' => $value['uid'], 'type' => 2, 'currency' => COIN])->sum('num');
                $list['list'][$key] += [
                    'income' => $income == 0 ? 0 : $income,
                    'expenditure' => $expenditure == 0 ? 0 : $expenditure,
                    'control' => 0,
                ];
            }
        } else {
            $list['list'] = [];
        }
        unset($list['limit']);
        $list['totalnum'] = $count;
        return $list;
    }

    //玩家信息
    public static function Msg_Hall_QueryUserInfo($uid)
    {
        $data = self::GetTableWords('user', 'uid,pictureframe,headimgurl,nickname,gold,bank,rcard,status,created,last_time', ['uid' => $uid]);
        if ($data == false) {
            return [];
        }
        $data['income'] = self::$db->table('user_profit')->where(['uid' => $uid, 'type' => 1, 'currency' => COIN])->sum('num');
        $data['income'] = $data['income'] > 0 ? $data['income'] : 0;
        $data['expenditure'] = self::$db->table('user_profit')->where(['uid' => $uid, 'type' => 2, 'currency' => COIN])->sum('num');
        $data['expenditure'] = $data['expenditure'] > 0 ? $data['expenditure'] : 0;
        $res = self::GetTableWords('user_superior', 'control,flagget,curget', ['uid' => $uid]);
        $data['control'] = empty($res['control']) ? 0 : $res['control'];

        if ($res['control'] == 0) {
            $data['flagget'] = 0;
        } elseif ($res['control'] < 0) {
            $data['flagget'] = $res['flagget'] - $res['curget'];
        } else {
            $data['flagget'] = $res['flagget'] - $res['curget'];
        }

        $data['monthlyend'] = self::GetTableOneWord('monthlycard', 'endtime', ['uid' => $uid]);
        if ($data['monthlyend'] == false || strtotime(MyTools::GET_TODAY()) - strtotime($data['monthlyend']) > 0) {
            $data['monthlyend'] = '';
        }
        return $data;
    }

    //查询游戏记录
    public static function QueryGameRecord($uid, $page)
    {
        $count = self::$db->table('game_record')->select('*')->where(['uid' => $uid])->asArray()->count();
        $list = self::Page($page, $count, 10);
        $list['list'] = self::$db->table('game_record')->select('gtype,uid,begintime,begingold,endtime,endgold')->where(['uid' => $uid])->limit($list['limit'])->asArray()->order('id DESC')->all();
        unset($list['limit']);
        return $list;
    }

    //代理首页
    public static function Agent($uid)
    {
        $playernum = self::$db->table('user')->select('*')->join('INNER JOIN jh_user_superior ON jh_user.uid=jh_user_superior.uid')->where(['jh_user.agent' => 2, 'jh_user_superior.superior' => $uid])->asArray()->all();
        $playernum = empty($playernum) ? 0 : count($playernum);
        $allagent = self::$db->table('user')->select('*')->join('INNER JOIN jh_user_superior ON jh_user.uid=jh_user_superior.uid')->where(['jh_user.agent' => 1, 'jh_user_superior.superior' => $uid])->asArray()->all();
        $agentnum = empty($allagent) ? 0 : count($allagent);
        $date = MyTools::GET_NOW(strtotime(MyTools::GET_TODAY()));
        $data = self::GetTableWords('user', 'gold,bank,rcard', ['uid' => $uid]);
        $superior = self::GetTableOneWord('user_superior', 'superior', ['uid' => $uid]);
        $card_date = self::GetTableWords('card_detailed', 'recvcard,usecard', ['uid' => $uid]);
        $income = self::$db->table('user_profit')->where(['uid' => $uid, 'type' => 1, 'currency' => COIN])->sum('num');
        $expenditure = self::$db->table('user_profit')->where(['uid' => $uid, 'type' => 2, 'currency' => COIN])->sum('num');
        $getextension = self::$db->table('agent')->select('extension,power')->where(['uid' => $uid])->one();
        $_allinsert = self::$db->table('user')->select('*')->join('INNER JOIN jh_user_superior ON jh_user.uid=jh_user_superior.uid')->where(['jh_user.agent' => 2, 'jh_user_superior.superior' => $uid, 'jh_user_superior.createtime' => ['>=', $date]])->asArray()->all();
        $insertplayer = empty($_allinsert) ? 0 : count($_allinsert);
        return [
            'superior' => $superior > 0 ? $superior : 0,
            'agentnum' => $agentnum,
            'playernum' => $playernum,
            'insertplayer' => $insertplayer,
            'income' => $income ?? 0,
            'expenditure' => $expenditure > 0 ? $expenditure : 0,
            'getextension' => $getextension ? $getextension['extension'] : 0,
            'mcard' => self::GetUserOneWord('mcard', $uid),
            'recv' => $card_date['recvcard'] ?? 0,
            'use' => $card_date['usecard'] ?? 0,
            'gold' => $data['gold'],
            'bank' => $data['bank'],
            'rcard' => $data['rcard'],
            'power' => $getextension ? $getextension['power'] : 0,
        ];
    }

    //增加兑换卡使用次数
    public static function IncrementRcard($uid, $num)
    {
        self::$db->table('user_superior')->where(['uid' => $uid])->increment(['usecard' => $num]);
    }

    //修改兑换卡明细 $type 1 赠送 2 收入 $num
    public static function UpdateCardDate($uid, $type, $num)
    {
        $ret = self::$db->table('card_detailed')->select('*')->where(['uid' => $uid])->asArray()->one();
        if (!$ret) {
            self::$db->table('card_detailed')->insert(['uid' => $uid, 'recvcard' => 0, 'usecard' => 0]);
        }
        $index = $type == 1 ? 'usecard' : 'recvcard';
        self::$db->table('card_detailed')->where(['uid' => $uid])->increment([$index => $num]);
    }

    //检测月卡权益
    public static function InspectJurisdiction()
    {
        $uids = self::$db->table('monthlycard')->select('uid')->asArray()->all();
        foreach ($uids as $key => $value) {
            $ret = self::$db->table('monthlycard')->select('*')->where(['uid' => $value])->asArray()->one();
            if ($ret) {
                $date = strtotime($ret['endtime']);
                if ($date < time()) {  //月卡过期
                    $mcard = self::$db->table('user')->select('mcard')->where(['uid' => $value])->asArray()->one(true);
                    if ($mcard) {  //关闭特权
                        self::$db->table('user')->where(['uid' => $value])->update(['battery' => 0, 'pictureframe' => 0]);
                    }
                }

                if ($ret['lastsigntime'] != MyTools::GET_TODAY(time() - 60 * 60 * 24)) {
                    DBInstance::UpdateMonthlyCard($value, ['continuity' => 0]);
                }
            }
        }
    }

    //代理领取推广金
    public static function AgentGetExtension($uid, $gold)
    {
        self::IncrementGolds('bank', $uid, $gold, false);
        self::$db->table('agent')->where(['uid' => $uid])->update(['extension' => 0]);
        self::$db->table('extensionrecord')->insert(['agent' => $uid, 'getextension' => $gold, 'createtime' => MyTools::GET_NOW()]);
    }

    //推广金列表
    public static function ExtensionList($uid)
    {
        return self::$db->table('extensionrecord')->select('getextension,createtime')->where(['agent' => $uid])->order('createtime DESC')->limit(20)->asArray()->all();
    }

    //查询财富榜
    public static function FortuneList()
    {
        return self::$db->table('user')->select('uid,nickname,headimgurl,pictureframe,bank + gold as allgolds')->where(['agent' => 2])->order('allgolds DESC')->limit(50)->asArray()->all();
    }

    //查询赢分榜
    public static function WinPointsList()
    {
        $data = self::$db->table('user')->select('jh_user.uid,jh_user.nickname,jh_user.headimgurl,jh_user.pictureframe,jh_winpoints.lastwin')->join('INNER JOIN jh_winpoints ON jh_user.uid=jh_winpoints.uid')->where('jh_winpoints.lastwin > 0')->order('jh_winpoints.lastwin DESC')->limit(50)->asArray()->all();
        if (empty($data)) {
            return [];
        }
        return $data;
    }

    //更新赢分榜
    public static function UpdateWinPointsList()
    {
        $day = self::$db->table('winpoints')->select('time')->asArray()->one(true);
        if ($day != MyTools::GET_TODAY()) {
            $date = MyTools::GET_TODAY();
            self::$db->query('UPDATE `jh_winpoints` SET `lastwin`=`curwin`,`curwin`=0,`time`="' . $date . '"');
        }
    }

    //更新兑换卡次数
    public static function UpdateRcardTime()
    {
        return self::$db->table('user_superior')->where('1')->update(['usecard' => 0]);
    }

    //增加坐庄机器人
    public static function InsertBankerRobot($gold, $num)
    {
        $uids = self::$db->table('user')->select('uid')->where(['online' => 2, 'agent' => 2, 'type' => 1])->limit($num)->order('last_time DESC')->asArray()->all();
        foreach ($uids as $key => $value) {
            self::$db->table('user')->where(['uid' => $value])->update(['gold' => rand($gold, ROBOTMAXGOLD)]);
        }
    }


    //增加普通机器人
    public static function InsertNormalRobot($min, $max, $num)
    {
        $uids = self::$db->table('user')->select('uid')->where(['online' => 2, 'agent' => 2, 'type' => 1])->limit($num)->order('last_time DESC')->asArray()->all();
        foreach ($uids as $key => $value) {
            self::$db->table('user')->where(['uid' => $value])->update(['gold' => rand($min, $max)]);
        }
    }

    //修改机器人分数
    public static function UpdateRobotGold()
    {
        $uids = self::$db->table('user')->select('uid')->where(['online' => 2, 'agent' => 2, 'type' => 1, 'gold' => ['>', ROBOTMAXGOLD]])->asArray()->all();
        foreach ($uids as $key => $value) {
            if (rand(1, 10) != 1) {
                continue;
            }
            self::$db->table('user')->where(['uid' => $value])->update(['gold' => rand(ROBOTGOLD, ROBOTMAXGOLD)]);
        }
    }

    //增加赢分
    public static function IncrementWinPoint($uid, $gold)
    {
        $ret = self::$db->table('winpoints')->select('*')->where(['uid' => $uid])->asArray()->one(true);
        if ($ret == false) {
            self::$db->table('winpoints')->insert([
                'uid' => $uid,
                'lastwin' => 0,
                'curwin' => 0,
                'time' => MyTools::GET_TODAY()
            ]);
        }
        if ($gold > 0) {
            self::$db->table('winpoints')->where(['uid' => $uid])->increment(['curwin' => $gold]);
        }
    }

    //新增用户月卡信息
    public static function InsertMonthlyCard($uid)
    {
        $yestoday = date('Y-m-d', time() - 60 * 60 * 24);
        return self::$db->table('monthlycard')->insert([
            'uid' => $uid,
            'createtime' => $yestoday,
            'endtime' => $yestoday,
            'lastsigntime' => $yestoday,
            'continuity' => 0,
            'relieftime' => $yestoday,
            'relief' => 2,
        ]);
    }

    //修改用户月卡信息
    public static function UpdateMonthlyCard($uid, $update)
    {
        return self::$db->table('monthlycard')->where(['uid' => $uid])->update($update);
    }

    public static function IncrementContinuity($uid)
    {
        return self::$db->table('monthlycard')->where(['uid' => $uid])->increment(['continuity' => 1]);
    }

    //获取游戏公告
    public static function GetAgents()
    {
        $ret = self::$db->table('sysconfig')->select('val,key')->where(array('key' => ['in', ['OFF_MES', 'YOU_AGENT', 'OFF_MES_IMG', 'YOU_AGENT_IMG']]))->asArray()->all();
        $res = [
            'offmes' => [],
            'youagent' => [],
        ];

        foreach ($ret as $key => $value) {
            if ($value['key'] == 'OFF_MES') {
                $res['offmes']['context'] = $value['val'];
            } elseif ($value['key'] == 'OFF_MES_IMG') {
                $res['offmes']['img'] = json_decode($value['val'], true);
            } elseif ($value['key'] == 'YOU_AGENT') {
                $res['youagent']['context'] = $value['val'];
            } else {
                $res['youagent']['img'] = json_decode($value['val'], true);
            }
        }
        return $res;
    }

    //修改服务器运行时间
    public static function UpdateSeverDate($date)
    {
        return self::$db->table('sysconfig')->where(['key' => 'SEVER_DATE'])->update(['val' => $date]);
    }

    //修改玩家流水信息
    public static function UpdateUserProfit($uid, $formuid, $type, $currency, $num)
    {
        $res = self::$db->table('user_profit')->select('*')->where(['uid' => $uid, 'formuid' => $formuid, 'type' => $type, 'currency' => $currency])->asArray()->one();
        if (!$res) {
            self::$db->table('user_profit')->insert(
                [
                    'uid' => $uid,
                    'formuid' => $formuid,
                    'type' => $type,
                    'currency' => $currency,
                    'num' => 0,
                    'created' => MyTools::GET_NOW()
                ]);
        }
        return self::$db->table('user_profit')->where(['uid' => $uid, 'formuid' => $formuid, 'type' => $type, 'currency' => $currency])->increment(['num' => $num]);
    }

    //收支明细 'type' => '1 收入 2 支出'  'agent' => '0 所有 1 上级 2 下级'
    public static function GoldDetailed($uid, $type, $selectuid, $agent, $page = 1)
    {
        $join = '';
        $where = 'currency=' . COIN;
        $where .= $type == 1 ? ' AND jh_mail.uid=' . $uid : ' AND jh_mail.outuid=' . $uid;
        if ($agent != 0 && $selectuid == 0) {
            if ($agent == 1) {
                $superior = self::GetUserSuperior($uid);
                $where .= $type == 1 ? ' AND jh_mail.outuid=' . $superior : ' AND jh_mail.uid=' . $superior;
            } else {
                if ($type == 1) {
                    $join = 'INNER JOIN jh_user_superior ON jh_user_superior.superior=jh_mail.uid';
                } else {
                    $join = 'INNER JOIN jh_user_superior ON jh_user_superior.superior=jh_mail.outuid';
                }
            }
        } elseif ($selectuid != 0) {
            $where .= $type == 1 ? ' AND jh_mail.outuid=' . $selectuid : ' AND jh_mail.uid=' . $selectuid;
        }

        $all = self::$db->table('mail')->select('*')->join($join)->where($where)->asArray()->all();
        $count = empty($all) ? 0 : count($all);
        $list = self::Page($page, $count, 10);
        $add = $type == 1 ? 'outuid' : 'uid';
        $join .= ' INNER JOIN jh_user ON jh_user.uid= jh_mail.' . $add;
        $where .= ' AND jh_mail.status!=3';
        $list['list'] = self::$db->table('mail')->select('jh_mail.id,jh_mail.uid,jh_mail.outuid,jh_mail.number,jh_mail.status,jh_mail.created,jh_user.nickname')->
        join($join)->where($where)->group('jh_mail.id,jh_mail.uid,jh_mail.outuid,jh_mail.number,jh_mail.status,jh_mail.created,jh_user.nickname')->
        limit($list['limit'])->asArray()->order('id DESC')->all();
        $list['list'] = empty($list['list']) ? [] : $list['list'];
        unset($list['limit']);
        $use = $type == 1 ? 'outuid' : 'uid';
        foreach ($list['list'] as $key => $value) {
            $list['list'][$key]['uid'] = $value[$use];
            unset($list['list'][$key]['outuid']);
        }

        $list['total'] = self::$db->table('mail')->select('*')->where($where)->sum('number') ?? 0;
        return $list;
    }

    //场控生效值
    public static function GetControl($uid, $gtype, $level)
    {
        if (!GAMECONTROLS) {
            return 0;
        }

        $info = DBInstance::GetTableWords('user_superior', '*', ['uid' => $uid]);
        $profit = DBInstance::GetTableOneWord('game_config', 'profit', ['gtype' => $gtype, 'level' => $level]);
        $controls = DBInstance::GetTableOneWord('game_config', 'controls', ['gtype' => $gtype, 'level' => $level]);
        $controls = json_decode($controls, true);
        if (!empty($info) && $info['control'] != 0) {
            if ($info['control'] > 0) {
                return 2;
            } elseif ($info['control'] < 0) {
                return -2;
            }
        } elseif (!empty($info)) {
            $control = DBInstance::GetTableOneWord('user_superior', 'control', ['uid' => $info['superior']]);
            if ($control > 0) {
                return 2;
            } elseif ($control < 0) {
                return -2;
            }
        }

        if (empty($controls)) {
            $control = 0;
        } else {
            if ($profit >= $controls['flag']) {
                $control = 1;
            } else {
                $control = 0;
            }
        }

        return $control;
    }

    //获取拉霸场控值
    public static function GetLBControl($uid, $gtype, $level)
    {
        if (!GAMECONTROLS) {
            return 0;
        }

        $info = DBInstance::GetTableWords('user_superior', '*', ['uid' => $uid]);
        $res = DBInstance::GetTableWords('game_config', 'profit,controls', ['gtype' => $gtype, 'level' => $level]);
        $res['controls'] = json_decode($res['controls'], true);
        if (!empty($info)) {
            if (!empty($info['control'])) {
                if ($info['control'] > 0) {
                    return 2;
                } elseif ($info['control'] < 0) {
                    return -2;
                }
            }
        } elseif (!empty($info)) {
            $control = DBInstance::GetTableOneWord('user_superior', 'control', ['uid' => $info['superior']]);
            if ($control > 0) {
                return 2;
            } elseif ($control < 0) {
                return -2;
            }
        }

        if (empty($res['controls'])) {
            $control = 0;
        } else {
            if ($res['profit'] >= $res['controls']['maxprofit']) {
                $control = 1;
            } elseif ($res['profit'] <= $res['controls']['minprofit']) {
                $control = -1;
            } else {
                $control = 0;
            }
        }

        return $control;
    }

    //计算满足金币条件的玩家数量
    public static function CountBankerNum($uids, $gold)
    {
        $ret = self::$db->table('user')->select('*')->where(['uid' => ['in', $uids], 'gold' => ['>=', $gold], 'type' => 1])->asArray()->count();
        return $ret == false ? 0 : $ret;
    }

    //获取游戏维护信息
    public static function GetGameMaintenance()
    {
        $start = self::GetSysConfig('SERVER_WHSTART');
        $end = self::GetSysConfig('SERVER_WHEND');
        // 防御：维护窗口为空，或覆盖全天(00:00~23:59)，一律视为未开启维护，
        // 避免被后台"维护时间"接口写入全天窗口后导致所有游戏无法进入。
        if ($start === '' || $end === '') {
            return 0;
        }
        $ts = strtotime($start);
        $te = strtotime($end);
        if ($ts === false || $te === false) {
            return 0;
        }
        if ($ts <= strtotime('today 00:00:00') && $te >= strtotime('today 23:59:00')) {
            return 0;
        }
        $start = $ts;
        $end = $te;
        if (time() < $start && $start - time() <= 15 * 60) {
            return 2;
        } elseif (time() > $start && time() <= $end) {
            return 1;
        } else {
            return 0;
        }
    }

    //维护公告播报
    public static function GetGameWH()
    {
        $res = self::$db->table('sysconfig')->select('key,val')->where(['id' => ['in', [14, 15, 16, 17]]])->asArray()->all('key');
        $str = $res['WH_MES'];
        $start = isset($res['SERVER_WHSTART']) ? strtotime($res['SERVER_WHSTART']) : 0;
        $end = isset($res['SERVER_WHEND']) ? strtotime($res['SERVER_WHEND']) : 0;
        $change = isset($res['WH_MES_TIME']) ? strtotime($res['WH_MES_TIME']) : 0;
        if (time() >= $change && $res['WH_MES'] != '') {
            self::$db->table('sysconfig')->where(['key' => 'WH_MES'])->update(['val' => '']);
            $str = '';
        }

        if (time() < $start && $start - time() <= 60 * 60 || time() > $start && time() <= $end) {
            $str = '本平台将于' . $res['SERVER_WHSTART'] . '至' . $res['SERVER_WHEND'] . '维护，请各位玩家合理安排游戏时间，敬请谅解！';
        }

        return $str;
    }

    //获取玩家月卡详情
    public static function GetMonthlyNum($uid)
    {
        $ret = [
            'recv' => self::$db->table('user_profit')->select('*')->where(['uid' => $uid, 'type' => 1, 'currency' => MCARD])->sum('num'),
            'use' => self::$db->table('user_profit')->select('*')->where(['formuid' => $uid, 'type' => 1, 'currency' => MCARD])->sum('num'),
        ];
        return $ret;
    }

    //新增游戏记录
    public static function InsertGameRecord($uid, $gtype, $begintime, $begingold)
    {
        $endgold = self::GetUserOneWord('gold', $uid);
        $endgold += self::GetUserOneWord('bank', $uid);
        return self::$db->table('game_record')->insert([
            'uid' => $uid,
            'gtype' => $gtype,
            'begintime' => date('Y-m-d H:i:s', $begintime),
            'begingold' => $begingold,
            'endtime' => date('Y-m-d H:i:s', time()),
            'endgold' => $endgold,
        ]);
    }

    //获取字段
    public static function GetTableArr($where)
    {
        return self::$db->table('game_jackpot')->select('gtype,level,jackpot')->where($where)->asArray()->all();
    }

    public static function GetGameJackpot($where)
    {
        return self::$db->table('game_jackpot')->select('probability,jackpot')->where($where)->asArray()->one();
    }

    //游戏缓存存储
    public static function seveUpdateCache($uid, $level, $gtype, $vals)
    {
        $ret = self::$db->table('save_cache')->select('id')->where(['uid' => $uid, 'gtype' => $gtype, 'level' => $level])->asArray()->one(true);
        if ($ret !== false) {
            self::$db->table('save_cache')->where(['id' => $ret])->update(['vals' => $vals]);
        } else {
            self::$db->table('save_cache')->insert([
                'uid' => $uid,
                'gtype' => $gtype,
                'level' => $level,
                'vals' => $vals
            ]);
        }
    }

    //取出游戏缓存
    public static function selectCache($uid, $level, $gtype)
    {
        return self::$db->table('save_cache')->select('vals')->where(['uid' => $uid, 'gtype' => $gtype, 'level' => $level])->asArray()->one(true);
    }

    //新增路径
    public static function AddFinshWays($pionts, $nums)
    {
        return self::$db->table('fish_ways')->insert(['positions' => json_encode($pionts), 'nums' => $nums]);
    }

    //获取所有鱼的列表
    public static function GetFishList($gtype)
    {
        return self::$db->table('fish_list')->select('*')->where(['gtype' => $gtype])->asArray()->all('id');
    }

    //获取所有路径
    public static function GetFisWays()
    {
        $ret = self::$db->table('fish_ways')->select('*')->asArray()->all('id');
        foreach ($ret as $key => $value) {
            $ret[$key]['positions'] = json_decode($value['positions'], true);
        }
        return $ret;
    }

    //新增鱼潮配置
    public static function InsertFishBoom($fishes)
    {
        return self::$db->table('fish_boom')->insert(['fish_list' => json_encode($fishes), 'time' => 70]);
    }

    //新增鱼潮配置
    public static function GetFishBoom()
    {
        $ret = self::$db->table('fish_boom')->select('*')->asArray()->all('id');
        foreach ($ret as $key => $value) {
            $ret[$key]['fish_list'] = json_decode($value['fish_list'], true);
        }
        return $ret;
    }

    //获取庄家信息
    public static function GetBankerInfo($uid)
    {
        return self::$db->table('user')->select('nickname,gold,uid')->where(array('uid' => $uid))->asArray()->one();
    }

    //增加游戏盈亏
    public static function InsertGameProfit($gtype, $level, $num)
    {
        if ($num > 0) {
            $res = self::GetTableOneWord('game_config', 'controls', ['gtype' => $gtype, 'level' => $level]);
            $res = json_decode($res, true);
            $num = intval($res['percent'] * $num / 1000);
        }

        return self::$db->table('game_config')->where(['gtype' => $gtype, 'level' => $level])->increment(['profit' => $num]);
    }

    //血色之夜增加缓存
    public static function InsertXSZYGameCache($uid, $gtype, $level, $vals)
    {
        return self::$db->table('save_cache')->insert(array('uid' => $uid, 'gtype' => $gtype, 'level' => $level, 'vals' => json_encode($vals)));
    }

    //血色之夜增加缓存
    public static function UpdateXSZYGameCache($uid, $gtype, $level, $vals)
    {
        return self::$db->table('save_cache')->where(array('uid' => $uid, 'gtype' => $gtype, 'level' => $level))->update(['vals' => json_encode($vals)]);
    }

    //血色之夜获取缓存
    public static function GetXSZYYGameCache($uid, $gtype, $level)
    {
        $ret = self::$db->table('save_cache')->select('vals')->where(array('uid' => $uid, 'gtype' => $gtype, 'level' => $level))->asArray()->one('true');
        return json_decode($ret, true);
    }

    //修改玩家登陆ip
    public static function UpdateUserIP($uid, $ip)
    {
        return self::$db->table('user')->where(array('uid' => $uid))->update(['last_ip' => $ip]);
    }

    public static function saveUpdatahbsl($uid, $level, $gtype, $vals)
    {
        foreach ($vals as $key => $val) {
            $ret = self::$db->table('cache_hbsl')->select('id')->where(['uid' => $uid, 'gtype' => $gtype, 'level' => $level, 'type' => $key])->asArray()->one(true);
            if ($ret !== false) {
                self::$db->table('cache_hbsl')->where(['id' => $ret])->update(['vals' => json_encode($val)]);
            } else {
                self::$db->table('cache_hbsl')->insert([
                    'uid' => $uid,
                    'gtype' => $gtype,
                    'level' => $level,
                    'type' => $key,
                    'vals' => json_encode($val)
                ]);

            }
        }
    }

    //拉霸游戏获取场控配置
    public static function GetControlInfo($name, $where = [])
    {
        $data = self::$db->table($name)->select('vals,level')->where($where)->asArray()->all();
        $res = [];

        foreach ($data as $key => $val) {
            $res[$val['level']] = json_decode($val['vals'], true);
        }
        return $res;
    }

    public static function selectCacheHBSL($uid, $level, $gtype)
    {
        $data = self::$db->table('cache_hbsl')->select('type,vals')->where(['uid' => $uid, 'gtype' => $gtype, 'level' => $level])->asArray()->all('type');
        return $data;
    }

    //获取当前账户封禁情况
    public static function GetUserStauts($ip, $equipment)
    {
        return self::$db->table('banuser')->select('id')->where('val="' . $ip . '" and `status`=0 or val="' . $equipment . '" and `status`=0')->asArray()->one();
    }


    //获取地图
    public static function GetControlMap($uid, $gtype, $level)
    {
        $ret = self::$db->table('control_maps')->select('*')->where(['uid' => $uid, 'gtype' => $gtype, 'level' => $level])->order('id ASC')->asArray()->one();
        if ($ret) {
            self::$db->table('control_maps')->where(['id' => $ret['id']])->delete();
            $ret = json_decode($ret['value'], true);
        } else {
            $ret = [];
        }

        return $ret;
    }

    //缓存玩家下注情况
    public static function SavePlayerBets($gtype, $level, $bets, $time = 0)
    {
        $ret = self::$db->table('player_bet')->select('*')->where(['gtype' => $gtype, 'level' => $level])->asArray()->one();
        $update = ['playerbets' => empty($bets) ? '{}' : json_encode($bets)];
        if ($time) {
            $update['endtime'] = $time;
        }
        if (!$ret) {
            self::$db->table('player_bet')->insert(array(
                'gtype' => $gtype,
                'level' => $level,
                'playerbets' => empty($bets) ? '{}' : json_encode($bets),
                'endtime' => $time == 0 ? time() : $time
            ));
        }
        return self::$db->table('player_bet')->where(['gtype' => $gtype, 'level' => $level])->update($update);
    }

    //获取下注配置
    public static function GetDuoRenControl($gtype, $level)
    {
        $ret = self::$db->table('duoren_control')->select('*')->where(['gtype' => $gtype, 'level' => $level])->order('id ASC')->asArray()->one();
        if (!$ret) {
            return -1;
        }

        self::$db->table('duoren_control')->where(['id' => $ret['id']])->delete();
        return $ret['region'];
    }

    //设置玩家下注
    public static function SetUserBet ($uid, $gtype, $level, $gold, $bet)
    {
        $ret = self::$db->table('arcade_bet')->select('*')->where(['uid' => $uid])->asArray()->one();
        if (!$ret) {
            self::$db->table('arcade_bet')->insert(array(
                'uid' => $uid,
                'gtype' => $gtype,
                'level' => $level,
                'used' => $gold,
                'bet' => $bet
            ));
        } else {
            self::$db->table('arcade_bet')->where(['uid' => $uid])->update([
                'gtype' => $gtype,
                'level' => $level,
                'used' => $gold,
                'bet' => $bet
            ]);
        }
    }

    //删除玩家下注缓存
    public static function DelUserBet ($uid)
    {
        //self::$db->table('arcade_bet')->where(['uid' => $uid])->delete();
    }

    //封禁玩家
    public static function DealBan()
    {
        $list = self::$db->table('banuser')->select('*')->where('1')->asArray()->all('id');
        $ban_uids = [];
        if ($list) {
            foreach ($list as $key => $value) {
                if (!$value['status']) {
                    $uids = self::$db->table('user')->select('uid')->where('uid=' . $value['val'] . ' or last_ip="' . $value['val'] . '" or equipmentcard="' . $value['val'] . '"')->asArray()->all();
                    if ($uids) {
                        $ban_uids = array_merge($uids, $ban_uids);
                    }
                }
                self::$db->table('user')->where('uid=' . $value['val'] . ' or last_ip="' . $value['val'] . '" or equipmentcard="' . $value['val'] . '"')->update(['status' => $value['status']]);
                if ($value['status'] || is_numeric($value['val'])) {
                    self::$db->table('banuser')->where(['id' => $key])->delete();
                }
            }
        }

        return $ban_uids;
    }
}
