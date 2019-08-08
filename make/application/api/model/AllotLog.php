<?php
namespace app\api\model;
use think\Db;
use think\Response;
class AllotLog extends Base
{
    //指定主键
    protected $pk = 'id';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';


    /**
     * 提交任务并且创建佣金记录(暂未调用)
     * @param $uid
     * @param $id
     * @param $img
     * @return array
     */
    public function subTask($uid, $id, $img)
    {
        $old = Db::name('send_task_log')->where(['id' => $id])->field('task_money,task_id,id,is_check')->find();
        if (!$old) return $this->outJson(0, '参数不合法');
        if ($old['is_check'] == 2) return $this->outJson(0, '该任务已经提交,耐心等待平台审核');
        if (!file_exists("." . $img)) return $this->outJson(0, '图片上传失败');
        // 修改任务数据
        $update = [
            'img' => $img,
            'is_check' => 2,
            'sub_time' => date('Y-m-d H:i:s')
        ];
        // 计算佣金 并且创建佣金记录数据
        $data = $this->checkTypeCreateData($uid, 2, $old['task_money'], $old['task_id'], $id);
        $insert_self_hire_log = $this->createSelfTaskHireLog($uid,$old['task_money'], $old['task_id'], $id);
        array_push($data,$insert_self_hire_log);
        $num = 0;
        $insert_id = 0;
        // 启动事务
        Db::startTrans();
        try {
            $offer = Db::name('send_task_log')->where(['id' => $id])->update($update);
            if ($data && count($data) == 1) {
                $insert_id = Db::name('hire_log')->insertGetId($data[0]);
                $num = $insert_id;
            }
            if ($data && count($data) > 1) {
                $insert_id = Db::name('hire_log')->insertAll($data);
                $num = count($data);
            }
            if ($offer > 0 && $insert_id == $num) {
                // 提交事务
                Db::commit();
                return $this->outJson(1, '操作成功');
            } else {
                // 回滚事务
                Db::rollback();
                return $this->outJson(0, '操作失败');
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->outJson(0, '操作失败,编码002');
        }
    }

    /**
     * 会员充值
     * @param int $uid 用户UID
     * @param int $level 充值会员级别 1：会员开通 2：VIP 3：svip
     * @param int $pay_status 支付方式 1：余额支付 2：支付宝 3：快捷支付 4:微信支付
     * @param int $renew 默认值：1 续费标识
     * @param int $upgrade 默认值：1 升级标识
     * @return array
     */
    public function userPay($uid, $level, $pay_status = 1)
    {
        $money_arr = cp_getCacheFile('system');
        $money = 0;
        $member_class = 1;
        if (!in_array($level, [1, 2])) return $this->outJson(0, '非法请求！');
        if (!in_array($pay_status, [2, 3, 4])) return $this->outJson(0, '非法请求！');
        $html_title = '';
        if ($level == 1) {
            // 普通VIP会员充值
            $money = isset($money_arr['common_money']) ? $money_arr['common_money'] : 0.1;
            $member_class = 2;
            $html_title = $money . '元升级为VIP';
        }
        if ($level == 2) {
            // 高级VIP充值
            $money = isset($money_arr['expert_money']) ? $money_arr['expert_money'] : 0.1;
            $member_class = 3;
            $html_title = $money . '元升级为高级VIP';
        }
        if (!$money) return $this->outJson(0, '非法操作');
        $old = Db::name('member')->where(['uid' => $uid])->field('member_class,balance')->find();
        if ($pay_status == 1) {
            if ($old['balance'] < $money) {
                return $this->outJson(0, '余额不足');
            }
        }
        // 判断当期用户的等级 如果是高级用户了那就无需充值了
        if ($old['member_class'] == 3) {
            return $this->outJson(0, '您已经是高级VIP了,无法再进行升级');
        }
        if ($old['member_class'] == 2 && $old['member_class'] == $member_class) {
            return $this->outJson(0, '您目前是VIP,只能升级为高级VIP');
        }

        $is_check_bank = [];
        // 如果是快捷支付 需要判断银行卡信息是否健全
        if ($pay_status == 3) {
            // todo 快捷支付
            $is_check_bank = Db::name('bank_pay_info')->where(['uid' => $uid])->find();
            if (!$is_check_bank) {
                return $this->outJson(100, '银行卡信息不完整,无法使用快捷支付');
            }
            if (!$is_check_bank['user_name'] || !$is_check_bank['bank_name'] || !$is_check_bank['bank_branch_name'] || !$is_check_bank['bank_card_num'] || !$is_check_bank['id_card_num'] || !$is_check_bank['phone']) {
                return $this->outJson(100, '银行卡信息不完整,无法使用快捷支付');
            }
        }

        $insert_hire_log = [];

        // 创建会员充值佣金记录
        switch ($pay_status) {
            case 1:
                // 余额支付
                $insert_hire_log = $this->checkTypeCreateData($uid, 1, $money, $uid, 0, $pay_status);
                break;
            case 2:
                // 支付宝
                $insert_hire_log = $this->checkTypeCreateData($uid, 1, $money, $uid, 0, $pay_status);
                break;
            case 3:
                // 快捷支付
                $insert_hire_log = $this->checkTypeCreateData($uid, 1, $money, $uid, 0, $pay_status);
                break;
            case 4:
                // 微信支付
                $insert_hire_log = $this->checkTypeCreateData($uid, 1, $money, $uid, 0, $pay_status);
                break;
        }
        $status = false;
        $insert_id = 0;
        $offer_id = 0;
        // 其他支付跳转
        $url = "";
        // 启动事务
        Db::startTrans();
        try {
            // 支付记录写入
            $pay_log = $this->createPayLogData($uid, $money, $pay_status, 1, $html_title);
            $pay_log_id = Db::name('pay_log')->insertGetId($pay_log);

            // 佣金记录写入
            if ($insert_hire_log) {
                $status = true;
                // 不一定有
                foreach ($insert_hire_log as $k => $v) {
                    $insert_hire_log[$k]['order_sn'] = $pay_log['order_sn'];
                }
                $insert_id = Db::name('hire_log')->insertAll($insert_hire_log);
            }

            // 如果是余额支付
            if ($pay_status == 1) {
                // 支付成功后 需要修改用户的等级状态 和 金额
                $up_member_status = Db::name('member')->where(['uid' => $uid])->setDec('balance', $money);
                $up_member_status_2 = Db::name('member')->where(['uid' => $uid])->setField('member_class', $member_class);
                if ($insert_hire_log) {
                    $status = true;
                    foreach ($insert_hire_log as $k => $v) {
                        $offer_id = Db::name('member')->where(['uid' => $v['uid']])->setInc('balance', $v['hire_money']);
                    }
                }
                if ($status) {
                    // 存在佣金的情况下
                    if ($pay_log_id && $insert_id && $offer_id && $up_member_status && $up_member_status_2) {
                        Db::commit();
                        return $this->outJson(1, '操作成功', ['app_pay_url' => $url]);
                    } else {
                        // 回滚事务
                        Db::rollback();
                        return $this->outJson(0, '操作失败,编码001');
                    }
                } else {
                    // 没有佣金的情况
                    if ($pay_log_id && $up_member_status && $up_member_status_2) {
                        Db::commit();
                        return $this->outJson(1, '操作成功', ['app_pay_url' => $url]);
                    } else {
                        Db::rollback();
                        return $this->outJson(0, '操作失败,编码002');
                    }
                }
            }

            if ($pay_status == 2) {
                // 支付宝支付 生成app可以跳转的支付链接
                //$money = 0.01;// TODO 测试专用 上线后注释掉
                $url = (new \app\index\controller\Pay())->create($html_title, $pay_log['order_sn'], $money);
                if ($status) {
                    // 存在佣金的情况
                    if ($pay_log_id && $insert_id) {
                        Db::commit();
                        return $this->outJson(1, '操作成功', ['app_pay_url' => $url]);
                    } else {
                        Db::rollback();
                        return $this->outJson(0, '操作失败,编码003');
                    }
                } else {
                    // 没有佣金的情况
                    if ($pay_log_id) {
                        Db::commit();
                        return $this->outJson(1, '操作成功', ['app_pay_url' => $url]);
                    } else {
                        Db::rollback();
                        return $this->outJson(0, '操作失败,编码004');
                    }
                }
            }

            if ($pay_status == 3) {
                // 快捷支付 银行卡支付
                // 发送该卡号短信验证码
                //$money = 0.1;// TODO 测试专用 上线后注释掉
                $m = new \app\index\controller\Pay();
                $res = $m->createShortcutSms($html_title, $pay_log['order_sn'], $money, $is_check_bank);
                if (!$res['status']) {
                    Db::rollback();
                    return $this->outJson(0, $res['msg']);
                }
                if ($status) {
                    // 存在佣金的情况
                    if ($pay_log_id && $insert_id) {
                        Db::commit();
                        return $this->outJson(1, '操作成功', [
                            'order_sn' => $pay_log['order_sn']
                        ]);
                    } else {
                        Db::rollback();
                        return $this->outJson(0, '操作失败,编码003');
                    }
                } else {
                    // 没有佣金的情况
                    if ($pay_log_id) {
                        Db::commit();
                        return $this->outJson(1, '操作成功', [
                            'order_sn' => $pay_log['order_sn']
                        ]);
                    } else {
                        Db::rollback();
                        return $this->outJson(0, '操作失败,编码004');
                    }
                }
            }

            if ($pay_status == 4) {
                // 微信支付 生成app可需要的参数
                //$money = 0.01;// TODO 测试专用 上线后注释掉
                $res = (new \app\index\controller\Pay())->createWxPay($html_title, $pay_log['order_sn'], $money);
                if (!$res['status']) {
                    Db::rollback();
                    return $this->outJson(0, $res['msg']);
                }
                if ($status) {
                    // 存在佣金的情况
                    if ($pay_log_id && $insert_id) {
                        Db::commit();
                        return $this->outJson(1, '操作成功', $res['data']);
                    } else {
                        Db::rollback();
                        return $this->outJson(0, '操作失败,编码003');
                    }
                } else {
                    // 没有佣金的情况
                    if ($pay_log_id) {
                        Db::commit();
                        return $this->outJson(1, '操作成功', $res['data']);
                    } else {
                        Db::rollback();
                        return $this->outJson(0, '操作失败,编码004');
                    }
                }
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->outJson(0, '操作失败');
        }
    }

    /**
     * 创建本身获取自带的佣金记录
     * @param $uid
     * @param $money
     * @param $type_id
     * @param $type_log_id
     * @return array
     */
    protected function createSelfTaskHireLog($uid,$money,$type_id, $type_log_id)
    {
        // 查询当前用户的具体信息
        $user = Db::name('member')
            ->where(['uid' => $uid])
            ->find();
        return [
            'uid' => $uid,
            'user_level' => $user['member_class'],
            'lower_parent_level' => 0,
            'ratio' => 0,
            'hire_type' => 2,
            'lower_uid' => $uid,
            'hire_money' => $money,
            'type_id' => $type_id,
            'type_log_id' => $type_log_id,
            'add_time' => time(),
        ];
    }

    /**
     * 检测业务类型并且创建相应的业务佣金记录
     * @param int $uid  当前用户Id
     * @param int $type 业务类型
     * @param int $money  业务佣金
     * @param int $type_id 业务类型id 广告任务-task_id 会员收费 uid
     * @param int $task_log_id 任务日志id 只有是广告任务才传入
     * @param int $pay_status 支付方式 只有会员充值才传入
     * @return array
     */
    protected function checkTypeCreateData($uid, $type, $money, $type_id, $task_log_id = 0, $pay_status = 1)
    {
        // 查询当前用户的具体信息
        $user = Db::name('member')
            ->where(['uid' => $uid])
            ->find();
        $insert = [];
        switch($type)
        {
            case 1:
                // TODO 会员收费
                if ($user['parent_level_1']) {
                    // 存在父一级的情况
                    $data = $this->createMemberHireLogData($user['parent_level_1'],$type,$uid,1, $money, $type_id, $pay_status);
                    if ($data) array_push($insert,$data);
                }
                if ($user['parent_level_2']) {
                    // 父二级
                    $data = $this->createMemberHireLogData($user['parent_level_2'],$type,$uid,2, $money, $type_id, $pay_status);
                    if ($data) array_push($insert,$data);
                }
                if ($user['parent_level_3']) {
                    // 父三级
                    $data = $this->createMemberHireLogData($user['parent_level_3'],$type,$uid,3, $money, $type_id, $pay_status);
                    if ($data) array_push($insert,$data);
                }
                if ($user['parents']) {
                    // 直接上级
                    $data = $this->createServerMemberHireLogData($user,$money,$pay_status);
                    if ($data) {
                        foreach ($data as $k => $v) {
                            array_push($insert,$v);
                        }
                    }
                }
                break;
            case 2:
                // TODO 广告任务
                if ($user['parent_level_1']) {
                    // 存在父一级的情况
                    $data = $this->createTaskHireLogData($user['parent_level_1'],$type,$uid,1, $money, $type_id, $task_log_id);
                    if ($data) array_push($insert,$data);
                }
                if ($user['parent_level_2']) {
                    // 父二级
                    $data = $this->createTaskHireLogData($user['parent_level_2'],$type,$uid,2, $money, $type_id, $task_log_id);
                    if ($data) array_push($insert,$data);
                }
                if ($user['parent_level_3']) {
                    // 父三级
                    $data = $this->createTaskHireLogData($user['parent_level_3'],$type,$uid,3, $money, $type_id, $task_log_id);
                    if ($data) array_push($insert,$data);
                }
                if ($user['parents']) {
                    // 直接上级
                    $data = $this->createServerTaskHireLogData($user,$money, $type_id, $task_log_id);
                    if ($data) {
                        foreach ($data as $k => $v) {
                            array_push($insert,$v);
                        }
                    }
                }
                break;
        }
        return $insert;
    }

    /**
     * 创建任务-佣金记录数据
     * @param int $parent_id  用户的父级id
     * @param int $type   业务类型
     * @param int $uid    当前用户id
     * @param int $lower_parent_level    当前用户的父几级
     * @param int $money  当前业务佣金
     * @param int $type_id 所属任务id或者所属会员id
     * @param int $task_log_id 任务日志id
     * @return array
     */
    protected function createTaskHireLogData($parent_id, $type, $uid, $lower_parent_level, $money, $type_id, $task_log_id)
    {
        // 查询当前用户父级的会员等级
        $parent_level = Db::name('member')
                ->where(['uid' => $parent_id])
                ->value('member_class');
        // 每个会员级别对应所占比列
        $value_html = '';
        switch($lower_parent_level)
        {
            case 1:
                $value_html = 'allot_one';
                break;
            case 2:
                $value_html = 'allot_two';
                break;
            case 3:
                $value_html = 'allot_three';
                break;
        }
        // 查询当期父级业务所占比例
        $scale = Db::name('allot_log')
            ->where(['charge_type' => $type, 'user_level' => $parent_level])
            ->value($value_html);
        if ($scale && $scale > 0) {
            // 当前用户父级会员 所占等级比列 按百分比计算需要除以100
            $ratio = $scale / 100;

            // 初始化公式所计算的具体分配给用户的佣金
            $init_money = computational(2,$money);

            // 如果当前用户的父级会员是服务中心会员级别的 还需要计算拿团队的比列
            $server_money = 0;
            /*if ($parent_level == 4) {
                $infinite_scale = Db::name('allot_log')
                    ->where(['charge_type' => 2, 'user_level' => $parent_level])
                    ->value('infinite');
                if ($infinite_scale && $infinite_scale > 0) {
                    $infinite_ratio = $infinite_scale / 100;
                    $server_money = $init_money * $infinite_ratio;
                    $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                }
            }*/

            // 计算所占比列
            $init_m = $init_money * $ratio;
            $hire_money = $init_m + $server_money;
            $hire_money = substr(sprintf("%.3f",$hire_money),0,-1);
            $arr = [
                'uid' => $parent_id,
                'user_level' => $parent_level,
                'lower_parent_level' => $lower_parent_level,
                'ratio' => $scale,
                'hire_type' => $type,
                'lower_uid' => $uid,
                'hire_money' => $hire_money,
                'type_id' => $type_id,
                'type_log_id' => $task_log_id,
                'add_time' => time(),
            ];
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 【提交任务】检测服务中心的个数并且创建佣金记录
     * @param array $userData  当前用户的信息
     * @param int $money  当前业务佣金
     * @param int $type_id 所属任务id
     * @param int $task_log_id 任务日志id
     * @return array
     */
    protected function createServerTaskHireLogData($userData,$money, $type_id, $task_log_id)
    {
        // 查询当前用户父级的会员等级
        $parent_uid_s = [];
        if ($userData['parent_level_1']) {
            array_push($parent_uid_s,$userData['parent_level_1']);
        }
        if ($userData['parent_level_2']) {
            array_push($parent_uid_s,$userData['parent_level_2']);
        }
        if ($userData['parent_level_3']) {
            array_push($parent_uid_s,$userData['parent_level_3']);
        }
        if (!$parent_uid_s) return [];

        // 初始化公式所计算的具体分配给用户的佣金
        $init_money = computational(2,$money);
        // 查询服务中心所占的比列
        $scale = Db::name('allot_log')
            ->where(['charge_type' => 2, 'user_level' => 4])
            ->value('infinite');
        if ($scale && $scale > 0) {
            $ratio = $scale / 100;
            $server_money = $init_money * $ratio;
            $server_money = substr(sprintf("%.3f",$server_money),0,-1);

            // 查询出上三级会员等级
            $user_parent_data = Db::name('member')
                ->where(['uid' => ['in',$parent_uid_s]])
                ->field('member_class,uid')
                ->select();

            if ($user_parent_data) {
                // 检测父三级到底有几个服务中心
                $check_level = 0;
                $user_parents_uid = [];
                foreach ($user_parent_data as $k => $v) {
                    if ($v['member_class'] == 4) {
                        $check_level++;
                        $user_parents_uid[] = $v['uid'];
                    }
                }
                $result = [];
                // 匹配服务中心个数
                switch($check_level)
                {
                    case 0:
                        // 一个都没有
                        $uid_s = $this->seekParentsUid($userData['uid'],3,$user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 2,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $type_id,
                                    'type_log_id' => $task_log_id,
                                    'add_time' => time(),
                                ];
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 1:
                        // 有一个
                        $uid_s = $this->seekParentsUid($userData['uid'],2,$user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 2,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $type_id,
                                    'type_log_id' => $task_log_id,
                                    'add_time' => time(),
                                ];
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 2:
                        // 有二个服务中心 那么最多只能找一个服务中心 找不到返回
                        $uid_s = $this->seekParentsUid($userData['uid'],1,$user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 2,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $type_id,
                                    'type_log_id' => $task_log_id,
                                    'add_time' => time(),
                                ];
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 3:
                        // 有三个
                        $result = [];
                        break;
                }
                return $result;
            } else {
                // 没有父级情况 也是直接退出
                return [];
            }
        } else {
            // 没有相对应的比列设置也是直接退出
            return [];
        }
    }

    /**
     * 初始化用户数据缓存
     */
    public function initMemberCacheData()
    {
        $data = Db::name('member')->where('uid','>',0)->select();
        cp_setCacheFile('users',[]);
        cp_setCacheFile('users',$data);
        echo '初始化成功';
    }

    /**
     * 寻找当前用户id 父级服务中心等级的个数
     * @param int $uid 用户Id
     * @param int $num 取几代
     * @param array $user_parents_uid 当期父类id属于服务中心的数组
     * @return array
     */
    public function seekParentsUid($uid, $num, $user_parents_uid = [])
    {
        $users = Db::name('member')->select();
        if ($users) {
            // 递归遍历当前用户id 的 所有父级id
            $data = \cocolait\helper\CpData::parentChannel($users,$uid,'uid','parents');
            if ($data) {
                array_shift($data);
                $count = count($data);
                if ($count > 0) {
                    $parents_uid = [];
                    foreach ($data as $k => $v) {
                        if ($v['member_class'] == 4) {
                            if ($user_parents_uid) {
                                if (!in_array($v['uid'],$user_parents_uid)) {
                                    array_push($parents_uid,$v['uid']);
                                }
                            } else {
                                array_push($parents_uid,$v['uid']);
                            }
                        }
                    }
                    $count_s = count($parents_uid);
                    if ($count_s > 0) {
                        if ($count_s >= $num) {
                            $temp = [];
                            for ($i = 0; $i < $num; $i++) {
                                $temp[] = $parents_uid[$i];
                            }
                            return $temp;
                        } else {
                            return $parents_uid;
                        }
                    } else {
                        return [];
                    }
                } else {
                    return [];
                }
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    /**
     * 创建任务-会员充值佣金数据
     * @param int $parent_id  用户的父级id
     * @param int $type   业务类型
     * @param int $uid    当前用户id
     * @param int $lower_parent_level    当前用户的父几级
     * @param int $money  当前充值金额
     * @param int $type_id 会员id
     * @param int $pay_mode 支付方式
     * @return array
     */
    protected function createMemberHireLogData($parent_id, $type, $uid, $lower_parent_level, $money, $type_id,$pay_mode = 1)
    {
        // 查询用户的等级
        $parent_level = Db::name('member')
            ->where(['uid' => $parent_id])
            ->value('member_class');
        // 每个级别取对应的级别数据
        $value_html = '';
        switch($lower_parent_level)
        {
            case 1:
                $value_html = 'allot_one';
                break;
            case 2:
                $value_html = 'allot_two';
                break;
            case 3:
                $value_html = 'allot_three';
                break;
        }
        // 查询业务所占比例
        $scale = Db::name('allot_log')
            ->where(['charge_type' => $type, 'user_level' => $parent_level])
            ->value($value_html);
        if ($scale && $scale > 0) {
            // 比列按百分比 需要除以100
            $ratio = $scale / 100;

            // 初始化公式所计算的具体分配给用户的佣金
            $init_money = computational(1,$money);

            // 如果当前用户的父级会员是服务中心会员级别的 还需要计算拿团队的比列
            $server_money = 0;
            /*if ($parent_level == 4) {
                $infinite_scale = Db::name('allot_log')
                    ->where(['charge_type' => 1, 'user_level' => $parent_level])
                    ->value('infinite');
                if ($infinite_scale && $infinite_scale > 0) {
                    $infinite_ratio = $infinite_scale / 100;
                    $server_money = $init_money * $infinite_ratio;
                    $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                }
            }*/

            // 计算所占比列
            $init_m = $init_money * $ratio;
            $hire_money = $init_m + $server_money;
            $hire_money = substr(sprintf("%.3f",$hire_money),0,-1);

            // 初始化 佣金记录
            $hire_log = [
                'uid' => $parent_id,
                'user_level' => $parent_level,
                'lower_parent_level' => $lower_parent_level,
                'ratio' => $scale,
                'hire_type' => $type,
                'lower_uid' => $uid,
                'hire_money' => $hire_money,
                'type_id' => $type_id,
                'type_log_id' => 0,
                'add_time' => time()
            ];
            if ($pay_mode == 1) {
                // 余额支付 直接发放
                $hire_log['is_check'] = 1;
                $hire_log['check_time'] = date('Y-m-d H:i:s');
            }
            return $hire_log;
        } else {
            return [];
        }
    }


    /**
     * 【会员充值】检测服务中心的个数并且创建佣金记录
     * @param array $userData  当前用户的信息
     * @param int $money  当前业务佣金
     * @param int $pay_mode 支付方式
     * @return array
     */
    protected function createServerMemberHireLogData($userData,$money, $pay_mode)
    {
        // 查询当前用户父级的会员等级
        $parent_uid_s = [];
        if ($userData['parent_level_1']) {
            array_push($parent_uid_s,$userData['parent_level_1']);
        }
        if ($userData['parent_level_2']) {
            array_push($parent_uid_s,$userData['parent_level_2']);
        }
        if ($userData['parent_level_3']) {
            array_push($parent_uid_s,$userData['parent_level_3']);
        }
        if (!$parent_uid_s) return [];

        // 初始化公式所计算的具体分配给用户的佣金
        $init_money = computational(1,$money);
        // 查询服务中心所占的比列
        $scale = Db::name('allot_log')
            ->where(['charge_type' => 1, 'user_level' => 4])
            ->value('infinite');
        if ($scale && $scale > 0) {
            $ratio = $scale / 100;
            $server_money = $init_money * $ratio;
            $server_money = substr(sprintf("%.3f",$server_money),0,-1);

            // 查询出上三级会员等级
            $user_parent_data = Db::name('member')
                ->where(['uid' => ['in',$parent_uid_s]])
                ->field('member_class,uid')
                ->select();

            if ($user_parent_data) {
                // 检测父三级到底有几个服务中心
                $check_level = 0;
                $user_parents_uid = [];
                foreach ($user_parent_data as $k => $v) {
                    if ($v['member_class'] == 4) {
                        $check_level++;
                        $user_parents_uid[] = $v['uid'];
                    }
                }
                $result = [];
                // 匹配服务中心个数
                switch($check_level)
                {
                    case 0:
                        // 一个都没有
                        $uid_s = $this->seekParentsUid($userData['uid'],3, $user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 1,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $userData['uid'],
                                    'type_log_id' => 0,
                                    'add_time' => time(),
                                ];
                                if ($pay_mode == 1) {
                                    // 余额支付 直接发放
                                    $result[$i]['is_check'] = 1;
                                    $result[$i]['check_time'] = date('Y-m-d H:i:s');
                                }
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 1:
                        // 有一个
                        $uid_s = $this->seekParentsUid($userData['uid'],2, $user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 1,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $userData['uid'],
                                    'type_log_id' => 0,
                                    'add_time' => time(),
                                ];
                                if ($pay_mode == 1) {
                                    // 余额支付 直接发放
                                    $result[$i]['is_check'] = 1;
                                    $result[$i]['check_time'] = date('Y-m-d H:i:s');
                                }
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 2:
                        // 有二个服务中心 那么最多只能找一个服务中心 找不到返回[]
                        $uid_s = $this->seekParentsUid($userData['uid'],1,$user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 1,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $userData['uid'],
                                    'type_log_id' => 0,
                                    'add_time' => time(),
                                ];
                                if ($pay_mode == 1) {
                                    // 余额支付 直接发放
                                    $result[$i]['is_check'] = 1;
                                    $result[$i]['check_time'] = date('Y-m-d H:i:s');
                                }
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 3:
                        // 有三个
                        $result = [];
                        break;
                }
                return $result;
            } else {
                // 没有父级情况 也是直接退出
                return [];
            }
        } else {
            // 没有相对应的比列设置也是直接退出
            return [];
        }
    }

    /**
     * 创建用户支付记录数据
     * @param int $uid  用户id
     * @param int $money 充值金额
     * @param int $pay_mode 支付方式
     * @param int $type 业务类型 1：会员升级充值
     * @param string $goods_name 商品名称
     * @return array
     */
    protected function createPayLogData($uid, $money, $pay_mode,$type, $goods_name)
    {
        // 初始化 充值记录
        $pay_log = [
            'uid' => $uid,
            'money' => $money,
            'type' => $type,
            'pay_mode' => $pay_mode,
            'add_time' => time(),
            'order_sn' => $this->createOrderSn(),
            'goods_name' => $goods_name
        ];
        if ($pay_mode == 1) {
            // 余额支付
            // 充值记录
            $pay_log['pay_time'] = time();
            $pay_log['pay_status'] = 2;
        } else {
            // TODO 其他支付方式 必须支付完成才进行 所有操作状态修改
            // TODO 包括用户余额额度的增加 佣金状态 支付状态
            $pay_log['pay_status'] = 1;
        }
        return $pay_log;
    }

    /**
     * TODO 其他支付方式 支付成功后 调用
     * @param $order_sn
     * @return array
     */
    protected function restPay($order_sn)
    {
        // 支付成功后 拿到订单号 修改状态
        $check = Db::name('pay_log')->where(['order_sn' => $order_sn])->field('id,uid')->find();
        if ($check) {
            // 修改订单状态
            // 启动事务
            Db::startTrans();
            try{
                $pay_log_id = Db::name('pay_log')->where(['id' => $check['id']])->update([
                    'pay_time' => time(),
                    'pay_status' => 2
                ]);

                $member_id = 0;
                $hire_log_id = 0;
                // 查看是否存在佣金 存在佣金进行状态解锁
                $check_hire_log = Db::name('hire_log')
                                ->where(['lower_uid' => $check['uid'],'type_id' => $check['type_id'],'hire_type'=>1,'is_check' => 0,'order_sn' => $order_sn])
                                ->field('id,hire_money,uid')
                                ->select();
                if ($check_hire_log) {
                    foreach ($check_hire_log as $v) {
                        // 给会员的待发佣金解锁并且新增
                        $member_id = Db::name('member')->where(['uid' => $v['uid']])->setInc('balance',$v['hire_money']);
                        // 解锁佣金状态 发放佣金
                        $hire_log_id = Db::name('hire_log')->where(['id' => $v['id']])->update([
                            'is_check' => 1,
                            'check_time' => date('Y-m-d H:i:s')
                        ]);
                    }
                    if ($pay_log_id && $member_id && $hire_log_id) {
                        // 提交事务
                        Db::commit();
                        return $this->outJson(1,'操作成功');
                    } else {
                        Db::rollback();
                        return $this->outJson(0,'操作失败,编码001');
                    }
                } else {
                    if ($pay_log_id) {
                        // 提交事务
                        Db::commit();
                        return $this->outJson(1,'操作成功');
                    } else {
                        Db::rollback();
                        return $this->outJson(0,'操作失败,编码002');
                    }
                }
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->outJson(0,'操作失败,编码003');
            }
        } else {
            return $this->outJson(0,'无效订单号');
        }
    }

    /**
     * 创建唯一的订单号
     * @return string
     */
    public function createOrderSn()
    {
        $orderSn =  date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);;
        return $orderSn;
    }

}
