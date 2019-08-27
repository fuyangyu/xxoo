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
        $data = Db::name('member')->alias('m')->join('member_info i','m.uid = i.uid')->where('m.uid',$uid)->field('m.phone,m.face,m.invite_uid,m.nick_name,i.province,i.city')->find();
        if($data['invite_uid']) {
            $invite_phone = Db::name('member')->where(['uid' => $data['invite_uid']])->value('phone');
            $data['invite_phone'] = !empty($invite_phone) ? $invite_phone : 0;
        }else{
            $data['invite_phone'] =  0;
        }
        $data['province_name'] = Db::name('region')->where('id',$data['province'])->value('name');
        $data['city_name'] = Db::name('region')->where('id',$data['city'])->value('name');
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
        if ($check['password'] != $password) {
            return $this->outJson(0,'用户名或者密码错误');
        }
        $token = \auth\Token::instance()->getAccessToken($check['uid'],$check['nick_name']);

        return $this->outJson(1,'登录成功', $token['data']);
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
        $sql = "update wld_member set password = '{$password}' where uid= {$uid}";
        $bool = Db::execute($sql);
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
                'password' => $data['password'],
                'invite_uid' => $data['invite_uid'],
                'member_class' => 1,//普通会员
                'add_time' => date('Y-m-d H:i:s'),
                'invite_time' => $data['invite_time'] ,
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
                    return $this->outJson(1, '注册成功', $token);
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
