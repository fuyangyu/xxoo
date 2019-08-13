<?php
namespace app\api\model;
use think\Db;
use think\Request;
use think\Response;
class Member extends Base
{
    //指定主键
    protected $pk = 'uid';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    /**
     * 生成唯一的邀请码
     * @return string
     */
    protected function createInviteCode()
    {
        while(true)
        {
            $inviteCode = \cocolait\helper\CpMsubstr::rand_string(6,3);
            $inviteCode = strtolower($inviteCode);
            $uid = $this->where(['invite_code' => $inviteCode])->value('uid');
            if (!$uid) {
                return $inviteCode;
                break;
            }
        }
    }

    /**
     * 获取个人信息
     * @param $uid 用户
     * @return mixed
     */
    public function getuserInfo($uid){

        $uid = !empty($uid)?$uid:-1;
        $sql = "select m.phone,m.face,m.invite_uid,i.nick_name,i.province,i.city FROM `wld_member` as m LEFT JOIN wld_member_info as i ON m.uid = i.uid WHERE m.uid = {$uid};";
        $data = Db::query($sql);
        if($data[0]['invite_uid']) {
            $invite_phone = Db::name('member')->where(['uid' => $data[0]['invite_uid']])->value('phone');
            $data['invite_phone'] = !empty($invite_phone) ? $invite_phone : 0;
        }else{
            $data['invite_phone'] =  0;
        }
        return $data;
    }

    /**
     * 个人中心-接口数据（目前不用）
     * @param $uid
     * @return array
     * @throws \think\Exception
     */
    public function getIndexData($uid)
    {
        $obj = $this->where(['uid' => $uid])->field('phone,face,member_class,balance')->find();
        if ($obj) {
            $data = $obj->toArray();
        }
        $data['face'] = isset($data['face']) && $data['face'] ? $data['face'] : $this->appDefaultFace;
        $data['all_total_money'] = isset($data['balance']) ? $data['balance'] : 0;
        $data['today_total_money'] = Db::name('hire_log')
                                    ->where(['uid' => $uid,'is_check' => 1])
                                    ->whereTime('check_time','today')
                                    ->sum('hire_money');
        $data['deposit_money'] = Db::name('deposit_log')
                                ->where(['uid' => $uid,'is_check' => 1])
                                ->sum('money');
        $info_id = Db::name('bank_info')->where(['uid' => $uid])->value('bank_card_num');
        $b_id = Db::name('bank_pay_info')->where(['uid' => $uid])->value('id');
        $member_id = Db::name('member_info')->where(['uid' => $uid])->value('id');
        $data['is_bank'] = $info_id ? 1 : 0;
        $data['is_up_bank'] = $b_id ? 1 : 0;
        $data['is_member'] = $member_id ? 1 : 0;
        $data['bank_card_num'] = $info_id ? $info_id : '';
        return $data;
    }

    /**
     * 获取用户个人资料 (暂未调用)
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getMemberInfo($uid)
    {
        $phone = $this->where(['uid' => $uid])->value('phone');
        $old = Db::name('member_info')
                ->where(['uid' => $uid])
                ->field('true_name,province,city,address,district')
                ->find();
        if ($old) {
            $old['phone'] = $phone;
            return $old;
        } else {
            $data = [
                'true_name' => '',
                'province' => '',
                'city' => '',
                'district' => '',
                'address' => '',
                'phone' => $phone
            ];
            return $data;
        }
    }

    /**
     * 获取我的团队 等级会员的个数 以及 那个会员级别具体信息 【已废弃】
     * @param $uid
     * @param $status
     * @param int $level
     * @return array
     */
    public function getUserChildTeamInfo($uid, $status, $level = 1)
    {
        if ($status == 1) {
            // 获取每个会员级别的数量
            $data = Db::query("SELECT * FROM wld_member WHERE `parent_level_1` = {$uid} OR `parent_level_2` = {$uid} OR `parent_level_3` = {$uid} ORDER BY `uid` DESC");
            $temp = [
                'user_level_1_total' => 0,
                'user_level_2_total' => 0,
                'user_level_3_total' => 0
            ];
            foreach ($data as $v) {
                if ($v['parent_level_1'] == $uid) {
                    $temp['user_level_1_total']++;
                }
                if ($v['parent_level_2'] == $uid) {
                    $temp['user_level_2_total']++;
                }
                if ($v['parent_level_3'] == $uid) {
                    $temp['user_level_3_total']++;
                }
            }
            return $temp;
        } else {
            // 获取每个会员级别的下线用户
            $data = [];
            switch ($level) {
                case 1:
                    $data = Db::name('member')->field('phone,member_class,face,add_time')->where(['parent_level_1' => $uid])->select();
                    break;
                case 2:
                    $data = Db::name('member')->field('phone,member_class,face,add_time')->where(['parent_level_2' => $uid])->select();
                    break;
                case 3:
                    $data = Db::name('member')->field('phone,member_class,face,add_time')->where(['parent_level_3' => $uid])->select();
                    break;
            }
            $res = $this->sendUserClass($data);
            return $res;
        }
    }

    /**
     * 获取团队下线会员数
     * @param $uid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getUserChildTeams($uid, $page = 1, $limit = 15)
    {
        $start = 0;
        if ($page != 1) {
            $start = ($page-1) * $limit;
        }
        $limits = $start . "," . $limit;
        $data = Db::query("SELECT * FROM wld_member WHERE `parent_level_1` = {$uid} OR `parent_level_2` = {$uid} OR `parent_level_3` = {$uid} OR `parents` = {$uid} ORDER BY `uid` DESC LIMIT {$limits}");
        // 查询总记录数
        $total = Db::query("SELECT count(*) as total FROM wld_member WHERE `parent_level_1` = {$uid} OR `parent_level_2` = {$uid} OR `parent_level_3` = {$uid} OR `parents` = {$uid}");
        $pageNum = 0;
        // 计算总页数
        if (isset($total[0]['total']) && $total[0]['total'] > 0) {
            $pageNum = ceil($total[0]['total'] / $limit);
        }
        $result = [];
        if ($data) {
            foreach ($data as $k => $v) {
                if (!$v['face']) {
                    $result[$k]['face'] = $this->appDefaultFace;
                }
                $result[$k]['member_class'] = $this->getMemberClassAttr($v['member_class']);
                $result[$k]['phone'] = $v['phone'];
                $result[$k]['add_time'] = $v['add_time'];
            }
        }
        return $this->outJson(1,'获取成功',['result' => $result, 'page_total' => $pageNum]);
    }

    /**
     * 处理每个级别会员下线的个数和具体用户信息 【已废弃】
     * @param $data
     * @return array
     */
    protected function sendUserClass($data)
    {
        $init = [
            'level_1' => [
                'total' => 0,
                'user_info' => []
            ],
            'level_2' => [
                'total' => 0,
                'user_info' => []
            ],
            'level_3' => [
                'total' => 0,
                'user_info' => []
            ],
        ];
        if ($data) {
            foreach ($data as $v) {
                if ($v['member_class'] == 1) {
                    // 普通会员
                    $init['level_1']['total']++;
                    array_push($init['level_1']['user_info'],$v);
                }
                if ($v['member_class'] == 2) {
                    // 普通VIP
                    $init['level_2']['total']++;
                    array_push($init['level_2']['user_info'],$v);
                }
                if ($v['member_class'] == 3) {
                    // 高级VIP
                    $init['level_3']['total']++;
                    array_push($init['level_3']['user_info'],$v);
                }
            }
        }
        foreach ($init as $k => $v) {
            if ($v['total'] > 0) {
                foreach ($v['user_info'] as $k2 => $v2) {
                    if (!$v2['face']) {
                        $init[$k]['user_info'][$k2]['face'] = $this->appDefaultFace;
                    }
                    $init[$k]['user_info'][$k2]['member_class'] = $this->getMemberClassAttr($v2['member_class']);
                }
            }
        }
        return $init;
    }

    /**
     * 用户个人资料新增和更新(暂时没用)
     * @param $uid
     * @param $data
     * @return bool
     * @throws \think\Exception
     */
    public function setStoreMemberInfo($uid, $data)
    {
        $id = Db::name('member_info')->where(['uid' => $uid])->value('id');
        if ($id) {
            Db::name('member_info')->where(['id' => $id])->update($data);
            return true;
        } else {
            $data['uid'] = $uid;
            $data['add_time'] = date('Y-m-d H:i:s');
            $id = Db::name('member_info')->insertGetId($data);
            if ($id) {
                return true;
            } else {
                return false;
            }
        }
    }

    // 获取器
    protected function getMemberClassAttr($value)
    {
        $html = '';
        switch ($value) {
            case 1:
                $html = '普通会员';
                break;
            case 2:
                $html = 'VIP';
                break;
            case 3:
                $html = '高级VIP';
                break;
            case 4:
                $html = '服务中心';
                break;
        }
        return $html;
    }

    /**
     * 登录
     * @param $data
     * @return array
     */
    public function sendLogin($data)
    {
        $phone = trim($data['phone']);
        $password = $data['password'];
        $check = Db::name('member')->where(['phone' => $phone])->field('uid,password,nick_name')->find();
        if (!$check) return $this->outJson(0,'用户名或者密码错误');
        if ($check['password'] != cp_encryption_password($password)) {
            return $this->outJson(0,'用户名或者密码错误');
        }
        $token = \auth\Token::instance()->getAccessToken($check['uid'],$check['nick_name']);

        return $this->outJson(1,'登录成功',['data' => $token['data']]);
    }

    /**
     * 忘记密码
     * @param $data
     * @return array
     * @throws \think\Exception
     */
    public function sendFind($data)
    {
        $phone = trim($data['phone']);
        $password = $data['password'];
        $uid = Db::name('member')->where(['phone' => $phone])->value('uid');
        if (!$uid) return $this->outJson(0,'该手机号码未注册');
        $bool = Db::name('member')->where(['uid' => $uid])->update([
            'password' => cp_encryption_password($password)
        ]);
        if ($bool) {
            return $this->outJson(1,'修改成功');
        } else {
            return $this->outJson(0,'修改失败');
        }
    }

    /**
     * 用户注册
     * @param $data
     * @return array
     */
    public function sendReg($data)
    {
        Db::startTrans();
        try{
//            $invite_code = $this->createInviteCode();
            // 注册新用户
            $uid = Db::name('member')->insertGetId([
                'phone' => trim($data['phone']),
                'password' => cp_encryption_password($data['password']),
                'invite_uid' => $data['invite_uid'],
                'member_class' => 1,//普通会员
                'add_time' => date('Y-m-d H:i:s')
            ]);

            //添加用户地址
            if($uid) {
                Db::name('member_info')->insert([
                    'uid' => $uid,
                    'province' => $data['province'],
                    'city' => $data['city'],
                    'add_time' => date('Y-m-d H:i:s')
                ]);

                // 邀请注册 注册关系网
                $resParent = $this->getInviteCodeParentUid($data['invite_uid']);
                if ($resParent) {
                    $bool = Db::name('member')->where(['uid' => $uid])->update($resParent);
                } else {
                    $bool = true;
                }
                if ($bool) {
                    // 提交事务
                    Db::commit();
                    $token = \auth\Token::instance()->getAccessToken($uid);
                    return $this->outJson(1, '注册成功', ['token' => $token['data']]);
                } else {
                    Db::rollback();
                    return $this->outJson(0, '注册失败');
                }
            }else {
                Db::rollback();
                return $this->outJson(0, '注册失败');
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->outJson(0,'注册失败',['debug' => $e->getMessage()]);
        }
    }

    /**
     * 通过邀请码-注册 找父级 绑定关系网
     * @param $invite_code
     * @return array
     */
    public function getInviteCodeParentUid($invite_uid=-1)
    {
        $data = [];
//        $infinity_code = config('code.invite_code') ? config('code.invite_code') : '';
//        if ($invite_code == $invite_code) return [];
//        $invite_code = strtolower($invite_code);
        // 查询父级一
        $old = Db::name('member')
                    ->where(['uid' => $invite_uid])
                    ->field('uid,parent_level_1,parent_level_2,parent_level_3,member_class')
                    ->find();
        if (!$old) return [];
        //推荐人一二级不为空 对应 自己二三级
        if ($old['parent_level_1'] && $old['parent_level_2']) {

            $data['parent_level_1'] = $old['uid'];
            $data['parent_level_2'] = $old['parent_level_1'];
            $data['parent_level_3'] = $old['parent_level_2'];
        }
        // 存在父类第一层id 第二次父类为空的情况
        if ($old['parent_level_1']) {
            $data['parent_level_1'] = $old['uid'];
            $data['parent_level_2'] = $old['parent_level_1'];
        }else{
            // 父级 第一层 如果为空 直接就是邀请人
            $data['parent_level_1'] = $old['uid'];
        }

        return $data;
    }

    //专属任务更多
    public function memberTaskMore($user_level = 2,$page = 1,$limit = 5){

        $start = 0;     //开始位置
        if ($page > 1) {
            $start = ($page-1) * $limit;
        }
        $sql = "SELECT task_id,title,task_icon,is_area,task_area,start_time,task_money,(taks_fixation_num+get_task_num) as rap_num FROM wld_task
                    WHERE start_time < unix_timestamp(now()) AND status = 1 AND task_user_level IN ($user_level)
                    ORDER BY add_time DESC LIMIT {$start},{$limit};";
        $task = Db::query($sql);
        return $this->outJson(1,'成功',$task);
    }
}
