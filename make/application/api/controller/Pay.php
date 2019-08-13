<?php
namespace app\api\controller;
use think\Db;
class Pay extends Base
{
    /**
     * 提现申请
     * @return \think\response\Json
     */
    public function pull()
    {
        try{
            if ($this->request->isPost()) {
                $money = trim($this->request->param('money'));
                if (!$money) return json($this->outJson(0,'请求参数不完整'));
                $old = Db::name('member')->where(['uid' => $this->uid])->field('balance,phone')->find();
                // 检测可用余额
                if ($money > $old['balance']) {
                    return json($this->outJson(0,'金额不能超过最大可提现余额'));
                }
                // 检测银行卡绑定信息
                $check = Db::name('bank_info')->where(['uid' => $this->uid])->value('id');
                if(!$check) return json($this->outJson(0,'未绑定银行卡信息无法进行提现'));
                $insert = [
                    'uid' => $this->uid,
                    'phone' => $old['phone'],
                    'money' => $money,
                    'add_time' => date('Y-m-d H:i:s')
                ];
                // 启动事务
                Db::startTrans();
                try{
                    $id = Db::name('deposit_log')->insertGetId($insert);
                    $id2 = Db::name('member')->where(['uid' => $this->uid])->setDec('balance',$money);
                    if ($id && $id2) {
                        // 提交事务
                        Db::commit();
                        return json($this->outJson(1,'申请提现成功'));
                    } else {
                        // 回滚事务
                        Db::rollback();
                        return json($this->outJson(0,'申请提现失败'));
                    }
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return json($this->outJson(0,'申请提现失败'));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 我的钱包
     * @return \think\response\Json
     */
    public function wallet()
    {
        try{
            if ($this->request->isPost()) {
                $balance = Db::name('member')->where(['uid' => $this->uid])->value('balance');
                $frost_money = Db::name('hire_log')->where(['uid' => $this->uid,'is_check' => 0])->sum('hire_money');
                return json($this->outJson(1,'获取成功',['use_money' => $balance, 'frost_money' => $frost_money]));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 充值记录
     * @return \think\response\Json
     */
    public function pushPayLog()
    {
        try{
            if ($this->request->isPost()) {
                $data = Db::name('pay_log')->where(['uid' => $this->uid])->field('money,pay_status,add_time,pay_time')->select();
                $res = [];
                if ($data) {
                    foreach ($data as $k => $v) {
                        $res[$k]['money'] = $v['money'];
                        if ($v['pay_status'] == 2) {
                            $res[$k]['name'] = '已完成';
                            $res[$k]['time'] = date('Y-m-d H:i:s',$v['pay_time']);
                        } else {
                            $res[$k]['name'] = '失败';
                            $res[$k]['time'] = date('Y-m-d H:i:s',$v['add_time']);
                        }
                    }
                }
                return json($this->outJson(1,'获取成功',$res));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 快捷支付发送短信 TODO 测试用
     * @return \think\response\Json
     */
    /*public function fastPaySms()
    {
        try{
            if ($this->request->isPost()) {
                $order_sn = $this->request->param('order_sn');
                if (!$order_sn) return json($this->outJson(0,'请求参数不合法'));
                $is_order_sn = Db::name('pay_log')->where(['order_sn' => $order_sn, 'pay_status' => 1])->find();
                if (!$is_order_sn) {
                    return json($this->outJson(0,'非法订单号'));
                }
                $bankInfo = Db::name('bank_info')->where(['uid' => $this->uid])->find();
                $m = new \app\index\controller\Pay();
                $money = $is_order_sn['money'];
//                $money = 0.1;// TODO 测试用
                $data = $m->createShortcutSms($is_order_sn['goods_name'], $order_sn, $money,$bankInfo);
                return json($data);
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }*/

    /**
     * 快捷支付-去支付
     * @return \think\response\Json
     */
    public function fastPay()
    {
        try{
            if ($this->request->isPost()) {
                $order_sn = $this->request->param('order_sn');
                $code = $this->request->param('code');
                if (!$order_sn && !$code) return json($this->outJson(0,'请求参数不合法'));
                $is_order_sn = Db::name('pay_log')->where(['order_sn' => $order_sn, 'pay_status' => 1])->find();
                if (!$is_order_sn) {
                    return json($this->outJson(0,'非法订单号'));
                }
                $bankInfo = Db::name('bank_info')->where(['uid' => $this->uid])->find();
                $m = new \app\index\controller\Pay();
                $money = $is_order_sn['money'];
                //$money = 0.1; // TODO 测试用
                $data = $m->createShortcutPay($is_order_sn['goods_name'], $order_sn, $money,$bankInfo,$code);
                return json($data);
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }
    }


    /**
     * 会员页面
     */
    public function memberCenter(){

        $data = array();
        $uid = !empty($this->uid)?$this->uid:$this->request->param('uid');
        $task_user_level = $this->request->param('task_user_level');
        //获取用户详情
        $user = Db::name('member')->where('uid',$uid)->field('nick_name,face,member_class,vip_end_time')->find();
        //根据用户会员情况获取会员
        $vip = cp_getCacheFile('system');
        switch($user['member_class']){
            case 1:
            case 2:
                $data['common_money'] = isset($vip['common_money']) ? $vip['common_money'] : 200;     //vip
                $data['expert_money'] = isset($vip['expert_money']) ? $vip['expert_money'] : 1000;    //svip
                $data['serve_money'] = isset($vip['serve_money']) ? $vip['serve_money'] : 10000;      //服务中心
                break;
            case 3:
                $data['expert_money'] = isset($vip['expert_money']) ? $vip['expert_money'] : 1000;
                $data['serve_money'] = isset($vip['serve_money']) ? $vip['serve_money'] : 10000;
                break;
            case 4:
                $data['serve_money'] = isset($vip['serve_money']) ? $vip['serve_money'] : 10000;
                break;
        }
        //专属VIP任务 获取比本身会员高一级的任务
        $model = new \app\api\model\Member();
        if($task_user_level = 2){
            $user_level = '2,3,4';
        }elseif($task_user_level = 3){
            $user_level = '3,4';
        }elseif($task_user_level = 4){
            $user_level = '4';
        }
        $level_task = $model->memberTaskMore($user_level,1,5);
        //获取最新5条已完成任务无筛选条件
        $sql = "SELECT s.id,s.title,s.uid,s.task_money,s.check_time,m.nick_name,m.face,c.name FROM wld_send_task_log AS s
                LEFT JOIN wld_member AS m ON s.uid = m.uid LEFT JOIN wld_task_classify c ON s.task_cid = c.task_cid
                WHERE s.is_check = 1 ORDER BY s.check_time DESC LIMIT 5;";
        $data['task'] = Db::query($sql);
        $data['user'] = $user;
        $data['level_task'] = $level_task;

        return json($this->outJson('0','成功',$data));

    }

    /**
     * 会员专属任务
     * @return \think\response\Json
     */
    public function privilegeTaskMore(){

        $page = $this->request->param('page',1); //页数
        $task_user_level = $this->request->param('task_user_level');    //滑动停留会员等级
        //专属VIP任务 获取比本身会员高一级的任务
        $model = new \app\api\model\Member();
        if($task_user_level = 2){
            $user_level = '2,3,4';
        }elseif($task_user_level = 3){
            $user_level = '3,4';
        }elseif($task_user_level = 4){
            $user_level = '4';
        }
        $data = $model->memberTaskMore($user_level,$page,15);

        return json($this->outJson('0','成功',$data));
    }

    /**
     * 会员充值
     * @return \think\response\Json
     */
    public function chargePay()
    {
        try{
            if ($this->request->isPost()) {
                $level = $this->request->param('level');    //充值会员等级 2：vip 3:svip
                $type = $this->request->param('type');  //充值类型 1：充值 2：续费 3.升级
                $pay_status = $this->request->param('pay_status');  //支付方式 1：支付宝 2：微信支付  3：快捷支付
                $uid = !empty($this->uid)?$this->uid:$this->request->param('uid');
                if (!in_array($level, [2,3])) return $this->outJson(0, '充值失败！');
                if (!in_array($pay_status, [1,2,3])) return $this->outJson(0, '支付方式错误！');
                if (!$level || !$pay_status || !$type || !$uid) return json($this->outJson(0,'参数不合法'));

                $model = new \app\api\model\AllotLog();
                $user = Db::name('member')->where(['uid'=>$uid])->field('uid,total_money,task_money,member_class,parent_level_1,parent_level_2,parent_level_3,invite_uid')->find();
                $money_arr = cp_getCacheFile('system'); //获取充值金额
                $common_money = isset($money_arr['common_money']) ? $money_arr['common_money'] : 200;
                $expert_money = isset($money_arr['expert_money']) ? $money_arr['expert_money'] : 1000;
                //判断充值类型
                if($type == 1 && $user['member_class'] == 1){ //充值
                    //根据充值等级获取充值金额
                    if ($level == 2) { //vip
                        $money = $common_money;
                        $html_title = '充值'.$money . '元升级为VIP';
                        $vip = 1;
                    }
                    if ($level == 3) { //svip
                        $money = $expert_money;
                        $html_title = '充值'.$money . '元升级为SVIP';
                        $vip = 2;
                    }
                }elseif($type == 2){    //续费
                    //根据续费等级获取续费金额
                    if($level == 2 && $user['member_class'] == 2){ //vip
                        $money = $common_money;
                        $html_title = 'VIP续费'.$money;
                        $vip = 1;
                    }
                    if($level == 3 && $user['member_class'] == 3){ //svip
                        $money = $expert_money;
                        $html_title = 'SVIP续费'.$money;
                        $vip = 2;
                    }
                }elseif($type == 3 && $user['member_class'] == 2){    //升级(只有已是vip会员才能升级只能升级svip)
                    //续费只补差价
                    $money = $expert_money - $common_money;
                    $html_title = 'VIP升级SVIP补差价'.$money;
                    $vip = 3;
                }else{
                    return json($this->outJson(0,'充值失败!'));
                }
                //实际支付金额
                $actual_money = cp_randomFloat(2,false,0,1);

                // 支付订单写入
                $pay_log = $model->createPayLogData($uid, $money, $pay_status, $type, $html_title,$vip);
                $pay_log_id = Db::name('pay_log')->insertGetId($pay_log);

                if($pay_log_id && $pay_log['order_sn']){
                    $m = new \app\index\controller\Pay();
                    // 会员充值方式
                    switch ($pay_status) {
                        case 1:
                            // 支付宝支付 生成app可以跳转的支付链接
                            $url = $m->create($html_title, $pay_log['order_sn'], $actual_money);
                            return json($this->outJson(1, '操作成功', ['app_pay_url' => $url]));
                            break;
                        case 2:
                            // 微信支付
                            $res = $m->createWxPay($html_title, $pay_log['order_sn'], $actual_money);
                            if (!$res['status']) {
                                return json($this->outJson(0, $res['msg']));
                            }
                            return json($this->outJson(1, '操作成功', $res['data']));
                            break;
                        case 3:
                            // 如果是快捷支付 需要判断银行卡信息是否健全
                            $is_check_bank = [];
                            if ($pay_status == 3) {
                                // todo 快捷支付
                                $is_check_bank = Db::name('bank_pay_info')->where(['uid' => $uid])->find();
                                if (!$is_check_bank) {
                                    return json($this->outJson(100, '银行卡信息不完整,无法使用快捷支付'));
                                }
                                if (!$is_check_bank['user_name'] || !$is_check_bank['bank_name'] || !$is_check_bank['bank_branch_name'] || !$is_check_bank['bank_card_num'] || !$is_check_bank['id_card_num'] || !$is_check_bank['phone']) {
                                    return json($this->outJson(100, '银行卡信息不完整,无法使用快捷支付'));
                                }
                            }
                            $res = $m->createShortcutSms($html_title, $pay_log['order_sn'], $actual_money, $is_check_bank);
                            if (!$res['status']) {
                                return json($this->outJson(0, $res['msg']));
                            }
                            return $this->outJson(1, '操作成功', $res['data']);
                            break;
                    }
                }else{
                    return $this->outJson(0, '操作失败');
                }

//                $data = $model->userPay($this->uid,$level,$pay_status);
//                return json($data);
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

}
