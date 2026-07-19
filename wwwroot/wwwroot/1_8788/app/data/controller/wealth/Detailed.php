<?php

namespace app\data\controller\wealth;

use app\data\model\ShopGoodsCate;
use think\admin\Controller;
use think\admin\extend\DataExtend;
use think\facade\Db;
use think\facade\Request;

/**
 * 商品分类管理
 * Class Cate
 * @package app\data\controller\shop
 */
class Detailed extends Controller
{
    public function index()
    {
        $this->title = "收支明细";
        $this->fetch();
    }


    public function getmail()
    {
        $type = input('type');
        $currency = input('currency');
        $relationship = input('relationship');
        $uid = input('uid');
        $starttime = input('start');
        $end = input('end');
        $limit = Request::instance()->param('limit');
        $page = Request::instance()->param('page');
        if (empty($type)) {
            $type = 1;
        }

        if (empty($relationship)) {
            $relationship = 2;
        }

        $start = ($page - 1) * $limit;
        $data = [];
        $all = [];
        $str = '';
        $sum_str = '';
        $allnum = [];

        if ($uid) {
            if ($type == 1 && $relationship == 1) {
                $str = 'SELECT uid,outuid,number,created,`status` FROM jh_mail WHERE uid=' . $uid . ' AND currency=' . $currency;
                $sum_str = 'SELECT sum(number) as sum FROM jh_mail WHERE uid=' . $uid . ' AND currency=' . $currency;
            } elseif ($type == 1 && $relationship == 2) {
                $superior = Db::query('SELECT superior FROM jh_user_superior WHERE uid=' . $uid);
                $superior = $superior[0]['superior'];
                $str = 'SELECT uid,outuid,number,created,`status`  FROM jh_mail WHERE uid=' . $uid . ' AND outuid=' . $superior . ' AND currency=' . $currency;
                $sum_str = 'SELECT sum(number) as sum FROM jh_mail WHERE uid=' . $uid . ' AND outuid=' . $superior . ' AND currency=' . $currency;
            } elseif ($type == 1 && $relationship == 3) {
                $str = 'SELECT jh_mail.uid,outuid,number,created,`status`  FROM jh_mail INNER JOIN jh_user_superior ON jh_user_superior.uid=jh_mail.uid  WHERE jh_mail.uid=' . $uid . ' AND jh_user_superior.superior=' . $uid . ' AND currency=' . $currency;
                $sum_str = 'SELECT  sum(number) as sum FROM jh_mail INNER JOIN jh_user_superior ON jh_user_superior.uid=jh_mail.uid  WHERE jh_mail.uid=' . $uid . ' AND jh_user_superior.superior=' . $uid . ' AND currency=' . $currency;
            } elseif ($type == 2 && $relationship == 1) {
                $str = 'SELECT uid,outuid,number,created,`status` FROM jh_mail WHERE outuid=' . $uid . ' AND currency=' . $currency;
                $sum_str = 'SELECT sum(number) as sum FROM jh_mail WHERE outuid=' . $uid . ' AND currency=' . $currency;
            } elseif ($type == 2 && $relationship == 2) {
                $superior = Db::query('SELECT superior FROM jh_user_superior WHERE uid=' . $uid);
                $superior = $superior[0]['superior'];
                $str = 'SELECT uid,outuid,number,created,`status` FROM jh_mail WHERE outuid=' . $uid . ' AND uid=' . $superior . ' AND currency=' . $currency;
                $sum_str = 'SELECT sum(number) as sum FROM jh_mail WHERE outuid=' . $uid . ' AND uid=' . $superior . ' AND currency=' . $currency;
            } elseif ($type == 2 && $relationship == 3) {
                $str = 'SELECT jh_mail.uid,outuid,number,created,`status` FROM jh_mail INNER JOIN jh_user_superior ON jh_user_superior.uid=jh_mail.uid  WHERE outuid=' . $uid . ' AND jh_user_superior.superior=' . $uid . ' AND currency=' . $currency;
                $sum_str = 'SELECT sum(number) as sum FROM jh_mail INNER JOIN jh_user_superior ON jh_user_superior.uid=jh_mail.uid  WHERE outuid=' . $uid . ' AND jh_user_superior.superior=' . $uid . ' AND currency=' . $currency;
            }

            if (!empty($str) && !empty($starttime)) {
                $str .= ' AND created>="' . $starttime . '"';
                $sum_str .= ' AND created>="' . $starttime . '"';
            }

            if (!empty($str) && !empty($end)) {
                $str .= ' AND created<="' . $end . '"';
                $sum_str .= ' AND created<="' . $end . '"';
            }

            if (!empty($str)) {
                $str .= ' ORDER BY created DESC';
                $all = Db::query($str);
                $allnum = Db::query($sum_str . ' AND status!=3');
                $str .= ' LIMIT ' . $start . ',' . $limit;
                $data = Db::query($str);
                $_arr = ['新邮件', '已读', '已领', '撤回', '删除'];
                foreach ($data as $key => $value) {
                    $data[$key]['status'] = $_arr[$value['status']];
                }
            }
        }

        return [
            'code' => 0,
            'msg' => '',
            'count' => count($all),
            'data' => $data,
            'allnum' => $allnum[0]['sum'] ?? 0,
        ];
    }
}