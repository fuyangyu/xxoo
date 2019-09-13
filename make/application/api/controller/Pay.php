<?php
namespace app\api\controller;
use think\Db;
class Pay extends Base
{
    /**
     * 我的钱包
     * @return \think\response\Json
     */
    public function wallet()
    {
        try{
            if ($this->request->isPost()) {
                $data = array();
                $uid = $this->request->param('uid');
                if(!$uid) return json($this->outJson(0,'参数错误'));
                $money = Db::name('member')->where(['uid' => $uid])->field('withdraw_money,have_withdrawal_money,member_brokerage_money,task_money,channel_money,static_money,withdrawal_password')->find();
                if($money) {
                    //可提现金额
                    $data['can_money'] = $money['member_brokerage_money']+$money['task_money']+$money['channel_money']+$money['static_money']-$money['withdraw_money']-$money['have_withdrawal_money'];
                    //提现中
                    $data['have_withdrawal_money'] = $money['have_withdrawal_money'];
                    //已提现
                    $data['withdraw_money'] = $money['withdraw_money'];
                }
                $data['withdrawal_password'] = !empty($money['withdrawal_password'])?1:0;
                $alipay = Db::name('alipay_info')->where('uid',$uid)->find();
                $data['alipay'] = !empty($alipay)?1:0;
                $bank = Db::name('bank_info')->where('uid',$uid)->find();
                $data['bank'] = !empty($bank)?1:0;

                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**我的钱包-提现页验证
     * @return \think\response\Json
     */
    public function withdrawShow(){
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $alipay = Db::name('alipay_info')->where('uid',$uid)->find();
        $bank = Db::name('bank_info')->where('uid',$uid)->find();
        if(!$alipay && !$bank){
            return json($this->outJson(0,'请先完善提现银行卡或提现支付宝'));
        }
        $money = Db::name('member')->where(['uid' => $uid])->field('withdraw_money,have_withdrawal_money,member_brokerage_money,task_money,channel_money,static_money,withdrawal_password')->find();
        //可提现金额
        $data['can_money'] = $money['member_brokerage_money']+$money['task_money']+$money['channel_money']+$money['static_money']-$money['withdraw_money']-$money['have_withdrawal_money'];
        if($alipay){
            $data['alipay'] = 1;
            $data['alipay_account'] = $alipay['alipay'];
        }else{
            $data['alipay'] = 0;
        }
        if($bank){
            $data['bank'] = 1;
            $data['bank_account'] = $bank['bank_name'].substr($bank['bank_account'],-4);
        }else{
            $data['bank'] = 0;
        }

        return json($this->outJson(1,'获取成功',$data));
    }

    /**
     * 提现
     * @return \think\response\Json
     */
    public function withdraw(){
        try{
            if ($this->request->isPost()) {
                $uid = $this->request->param('uid');
                if(!$uid) return json($this->outJson(0,'参数错误'));
                $money = trim($this->request->param('money'));
                $type = $this->request->param('type'); //提现方式 1银行卡 2支付宝
                $password = trim($this->request->param('password'));
                if (!$money || !$type || !$password) return json($this->outJson(0,'请求参数不完整'));
                $member = Db::name('member')->where(['uid' => $uid])->field('withdraw_money,have_withdrawal_money,member_brokerage_money,task_money,channel_money,static_money,withdrawal_password,phone')->find();
                //可提现金额
                $can_money = $member['member_brokerage_money']+$member['task_money']+$member['channel_money']+$member['static_money']-$member['withdraw_money']-$member['have_withdrawal_money'];
                // 检测可用余额
                if ($money > $can_money) {
                    return json($this->outJson(0,'您输入的提现金额高于账户余额！'));
                }
                if ($money > 5000) {
                    return json($this->outJson(0,'最高提现金额应小于50000'));
                }
                if ($money < 50) {
                    return json($this->outJson(0,'最低提现金额不能小于50'));
                }
                if(!$member['withdrawal_password']){
                    return json($this->outJson(0,'您的提现密码还未设置，请先设置提现密码！'));
                }
                if($member['withdrawal_password'] != $password){
                    return json($this->outJson(0,'密码错误'));
                }
                $sever_money = $money*0.05; //手续费
                $real_money = $money-$sever_money;
                $insert = [
                    'uid' => $uid,
                    'phone' => $member['phone'],
                    'deposit_money' => $money,
                    'sever_money' => $money*0.05,
                    'real_money' => $real_money,
                    'type' => $type,
                    'depos_status' => 1,
                    'add_time' => date('Y-m-d H:i:s')
                ];
                // 启动事务
                Db::startTrans();
                try{
                    $id = Db::name('deposit_log')->insertGetId($insert);
                    $id2 = Db::name('member')->where(['uid' => $uid])->setInc('withdraw_money',$real_money);
                    if ($id && $id2) {
                        // 提交事务
                        Db::commit();
                        return json($this->outJson(1,'申请提现成功',$id));
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
     * 提现记录
     * @return \think\response\Json
     */
    public function withdrawLog(){
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $sql = "SELECT id,uid,add_time,real_money,type,depos_status, CASE depos_status WHEN 1 THEN '提现中' WHEN 2 THEN '成功' WHEN 3 THEN '提现失败' END AS status_show FROM wld_deposit_log WHERE uid = {$uid};";
        $log = Db::query($sql);
        if(!empty($log)){
            foreach($log as $k=>$v){
                if($v['type'] == 1){
                    $bank = Db::name('bank_info')->where('uid',$v['uid'])->field('bank_name,bank_account')->find();
                    $log[$k]['account'] = $bank['bank_name'].'('.substr($bank['bank_account'],-4).')';
                }
                if($v['type'] == 2){
                    $alipay = Db::name('alipay_info')->where('uid',$v['uid'])->value('alipay');
                    $log[$k]['account'] = $alipay;
                }
                $log[$k]['account'] = '';
            }
        }
        return json($this->outJson(1,'获取成功',$log));
    }

    /**
     * 提现详情
     * @return \think\response\Json
     */
    public function withdrawDetails(){
        $id = $this->request->param('id');
        if(!$id) return json($this->outJson(0,'参数错误'));
        $log = Db::name('deposit_log')->where('id',$id)->field('id,uid,type,add_time,deposit_money,sever_money,depos_status,check_msg')->find();
        if(!empty($log)){
            if($log['type'] == 1){
                $bank = Db::name('bank_info')->where('uid',$log['uid'])->field('bank_name,bank_account')->find();
                $log['account'] = $bank['bank_name'].'('.substr($bank['bank_account'],-4).')';
            }
            if($log['type'] == 2){
                $alipay = Db::name('alipay_info')->where('uid',$log['uid'])->value('alipay');
                $log['account'] = $alipay;
            }
            $log['account'] = '';
            return json($this->outJson(1,'获取成功',$log));
        }
        return json($this->outJson(0,'获取失败',$log));
    }

    /**
     * 添加/修改支付宝账号
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function addAlipay(){
        $aliPay = $this->request->param();
        if(!$aliPay['uid']) return json($this->outJson(0,'参数错误'));
        $alipay_info = Db::name('alipay_info')->where('uid',$aliPay['uid'])->find();
        $data = [
            'alipay' => $aliPay['alipay'],
            'real_name' => $aliPay['real_name'],
            'time' => date('Y-m-d H:i:h'),
        ];
        if($alipay_info){   //修改
            $id = Db::name('alipay_info')->where('uid',$aliPay['uid'])->update($data);
            if($id){
                return json($this->outJson(1,'修改成功',$id));
            }else{
                return json($this->outJson(0,'修改失败'));
            }
        }else{  //新增
            $data['uid'] = $aliPay['uid'];
            $id = Db::name('alipay_info')->insertGetId($data);
            if($id){
                return json($this->outJson(1,'添加成功',$id));
            }else{
                return json($this->outJson(0,'修改失败'));
            }
        }
    }

    /**
     * 提现密码设置
     * @return \think\response\Json
     */
    public function withdrawalPassword(){
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $withdrawal = $this->request->param('withdrawal_password');
//        if(strlen($withdrawal) != 6){
//            return json($this->outJson(0,'密码规则错误'));
//        }
        $id = Db::name('member')->where('uid',$uid)->setField('withdrawal_password',$withdrawal);
        if($id){
            return json($this->outJson(1,'设置成功',$id));
        }else{
            return json($this->outJson(0,'设置失败'));
        }

    }

    /**
     * 银行卡设置验证
     * @return \think\response\Json
     */
    public function bankVerify(){
        try{
            $checkData = $this->request->param();
            $validate = new \app\api\validate\Bank();
            if (!$vdata = $validate->scene('all')->check($checkData)) {
                return json($this->outJson(0,$validate->getError()));
            }
            $host = "http://b4bankcard.market.alicloudapi.com";
            $path = "/bank4Check";
            $method = "GET";
            $appcode = "64d63b3687e943bd8734efd661a7981c";
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = "accountNo={$checkData['bank_account']}&idCard={$checkData['id_card']}&mobile={$checkData['bank_phone']}&name={$checkData['user_name']}";
            $bodys = "";
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            //curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
            //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
            if (1 == strpos("$".$host, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $out_put = json_decode(curl_exec($curl),true);
            if($out_put['status'] != 01){
                return json($this->outJson($out_put['status'],$out_put['msg']));
            }
            return json($this->outJson(1,'验证成功',$out_put));
         } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 设置银行卡
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function bankSet(){
        $checkData =$this->request->param();
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $validate = new \app\api\validate\Bank();
        if (!$vdata = $validate->scene('all')->check($checkData)) {
            return json($this->outJson(0,$validate->getError()));
        }
        $bank = [
                'bank_phone' => trim($checkData['bank_phone']),
                'user_name' => trim($checkData['user_name']),
                'bank_name' => trim($checkData['bank']),
                'id_card' => trim($checkData['id_card']),
                'bank_account' => trim($checkData['bank_account']),
                'add_time' => date('Y-m-d H:i:s')
            ];
        $bank_info = Db::name('bank_info')->where('uid',$uid)->find();
        if($bank_info){ //修改
            $id = Db::name('bank_info')->where('uid',$uid)->update($bank);
            if($id){
                return json($this->outJson(1,'修改成功',$id));
            }else{
                return json($this->outJson(0,'修改失败'));
            }
        }else{  //新增
            $bank['uid'] = $this->uid;
            $id = Db::name('bank_info')->insertGetId($bank);
            if($id){
                return json($this->outJson(1,'设置成功',$id));
            }else{
                return json($this->outJson(0,'修改失败'));
            }
        }
    }

    /**
     * 获取用户的银行卡信息
     * @return \think\response\Json
     */
    public function getBank()
    {
        try{
            $uid = $this->request->param('uid');
            if(!$uid) return json($this->outJson(0,'参数错误'));
            if ($this->request->isPost()) {
                $data = Db::name('bank_info')
                    ->where(['uid' => $uid])
                    ->field('user_name,id_card,bank_account,bank_name,bank_phone')
                    ->find();
                if($data){
                    $data['user_name'] = cp_substr_cut($data['user_name']);
                    $data['id_card'] = cp_func_substr_replace($data['id_card'],'*',4,10);
                    $data['bank_account'] = cp_func_substr_replace($data['bank_account'],'*',4,11);
                    $data['bank_phone'] = cp_func_substr_replace($data['bank_phone'],'*',3,4);
                }
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
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
            $uid = $this->request->param('uid');
            if(!$uid) return json($this->outJson(0,'参数错误'));
            if ($this->request->isPost()) {
                $data = Db::name('pay_log')->where(['uid' => $uid])->field('money,pay_status,add_time,pay_time')->select();
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
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        //获取用户详情
        $user = Db::name('member')->where('uid',$uid)->field('nick_name,face,member_class,vip_end_time,phone')->find();
        if($user['face']){
            $user['face'] = $this->request->domain().$user['face'];
        }
        //会员权益佣金比例
        $allot = Db::name('allot_log')->where('user_level','in','2,3,4')->field('allot_one,allot_two,charge_type,user_level')->select();
        foreach($allot as $k=>$v){
            if($v['user_level'] == 2){
                if($v['charge_type'] == 1){
                    $data['vip']['recommend'] = $v['allot_one'];
                    $data['vip']['between'] = $v['allot_two'];
                }
                if($v['charge_type'] == 2){
                    $data['vtask']['recommend'] = $v['allot_one'];
                    $data['vtask']['between'] = $v['allot_two'];
                }
            }
            if($v['user_level'] == 3){
                if($v['charge_type'] == 1){
                    $data['svip']['recommend'] = $v['allot_one'];
                    $data['svip']['between'] = $v['allot_two'];
                }
                if($v['charge_type'] == 2){
                    $data['stask']['recommend'] = $v['allot_one'];
                    $data['stask']['between'] = $v['allot_two'];
                }
            }
            if($v['user_level'] == 4){
                if($v['charge_type'] == 1){
                    $data['ssvip']['recommend'] = $v['allot_one'];
                    $data['ssvip']['between'] = $v['allot_two'];
                }
                if($v['charge_type'] == 2){
                    $data['sstask']['recommend'] = $v['allot_one'];
                    $data['sstask']['between'] = $v['allot_two'];
                }
            }
        }
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

            $vip = '2,3,4';

            $svp = '3,4';

            $ssvip = '4';

        $vip_task = $model->memberTaskMore($vip,1,5);
        if($vip_task){
            foreach($vip_task as $k=>&$v){
                $v['task_icon'] = $this->request->domain().$v['task_icon'];
                $json_area = json_decode($v['task_area'],true);
                $v['task_area'] = $json_area['city'];
            }
        }
        $data['vip_task'] = $vip_task;

        $svip_task = $model->memberTaskMore($svp,1,5);
        if($svip_task){
            foreach($svip_task as $k => &$v){
                $v['task_icon'] = $this->request->domain().$v['task_icon'];
                $json_area = json_decode($v['task_area'],true);
                $v['task_area'] = $json_area['city'];
            }
        }
        $data['svip_task'] = $svip_task;

        $ssvip_task = $model->memberTaskMore($ssvip,1,5);
        if($ssvip_task){
            foreach($ssvip_task as $k => &$v){
                $v['task_icon'] = $this->request->domain().$v['task_icon'];
                $json_area = json_decode($v['task_area'],true);
                $v['task_area'] = $json_area['city'];
            }
        }
        $data['ssvip_task'] = $ssvip_task;

        //获取最新5条已完成任务无筛选条件
        $sql = "SELECT s.id,s.title,s.uid,s.task_money,s.check_time,m.nick_name,m.face,m.phone,c.name,t.task_icon FROM wld_send_task_log AS s
                LEFT JOIN wld_member AS m ON s.uid = m.uid LEFT JOIN wld_task_classify c ON s.task_cid = c.task_cid LEFT JOIN wld_task t on s.task_id = t.task_id
                WHERE s.is_check = 1 ORDER BY s.check_time DESC LIMIT 5;";
        $data['task'] = Db::query($sql);
        if($data['task']){
            foreach($data['task'] as &$v){
                $v['task_icon'] = $this->request->domain().$v['task_icon'];
                if($v['face']){
                    $v['face'] = $this->request->domain().$v['face'];
                }
            }
        }
        $data['user'] = $user;

        return json($this->outJson(1,'成功',$data));

    }

    /**
     * 会员专属任务更多(未调用)
     * @return \think\response\Json
     */
    public function privilegeTaskMore(){

        $page = $this->request->param('page',1); //页数
        $task_user_level = $this->request->param('task_user_level');    //滑动停留会员等级
        //专属VIP任务 获取比本身会员高一级的任务
        $model = new \app\api\model\Member();
        if($task_user_level == 2){
            $user_level = '2,3,4';
        }elseif($task_user_level == 3){
            $user_level = '3,4';
        }elseif($task_user_level == 4){
            $user_level = '4';
        }
        $data = $model->memberTaskMore($user_level,$page,15);

        return json($this->outJson(1,'成功',$data));
    }

    /**
     * 开通会员页面
     * @return \think\response\Json
     */
    public function payShow(){
        $data = array();
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        //获取用户信息
        $data['user'] = Db::name('member')->where('uid',$uid)->field('uid,phone,nick_name,nick_name,member_class,face,vip_start_time,vip_end_time')->find();
        if(!empty($data['user']['face'])){
            $data['user']['face'] = $this->request->domain().$data['user']['face'];
        }
        //获取充值信息
        $money_arr = cp_getCacheFile('system');
        $data['vip'] = isset($money_arr['common_money']) ? $money_arr['common_money'] : 200;
        $data['svip'] = isset($money_arr['expert_money']) ? $money_arr['expert_money'] : 1000;
        return json($this->outJson(1,'获取成功',$data));
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
                $pay_status = $this->request->param('pay_status');  //支付方式 2：支付宝 1：微信支付  3：快捷支付
                $uid = $this->request->param('uid');
                if (!in_array($level, [2,3])) return json($this->outJson(0, '充值失败！'));
                if (!in_array($pay_status, [1,2,3])) return json($this->outJson(0, '支付方式错误！'));
                if (!$level || !$pay_status || !$type || !$uid) return json($this->outJson(0,'参数不合法'));
                $user = Db::name('member')->where(['uid'=>$uid])->field('uid,total_money,task_money,member_class,parent_level_1,parent_level_2,parent_level_3,invite_uid')->find();
                $money_arr = cp_getCacheFile('system'); //获取充值金额
                $common_money = isset($money_arr['common_money']) ? $money_arr['common_money'] : 200;
                $expert_money = isset($money_arr['expert_money']) ? $money_arr['expert_money'] : 1000;
                $money = 0;
                //判断充值类型
                if($type == 1 && $user['member_class'] == 1){ //充值
                    //根据充值等级获取充值金额
                    if ($level == 2) { //vip
                        $money = $common_money;
                        $html_title = '充值'.$money . '元升级为VIP';
                    }
                    if ($level == 3) { //svip
                        $money = $expert_money;
                        $html_title = '充值'.$money . '元升级为SVIP';
                    }
                }elseif($type == 2){    //续费
                    //根据续费等级获取续费金额
                    if($level == 2 && $user['member_class'] == 2){ //vip
                        $money = $common_money;
                        $html_title = 'VIP续费'.$money.'元';
                    }
                    if($level == 3 && $user['member_class'] == 3){ //svip
                        $money = $expert_money;
                        $html_title = 'SVIP续费'.$money.'元';
                    }
                }elseif($type == 3 && $user['member_class'] == 2){    //升级(只有已是vip会员才能升级只能升级svip)
                    //续费只补差价
                    $money = $expert_money - $common_money;
                    $html_title = 'VIP升级SVIP补差价'.$money.'元';
                }else{
                    return json($this->outJson(0,'充值失败!'));
                }
                //实际支付金额
                $actual_money = $money - cp_randomFloat(2,false,0,1);

                // 支付订单写入
//                $model = new \app\api\model\AllotLog();
//                $pay_log = $model->createPayLogData($uid, $money, $pay_status, $type, $html_title,$vip);
                $pay_log = [
                    'uid' => $uid,
                    'money' => $money,
                    'actual_money' => $actual_money,
                    'type' => $type, //充值类型 1：充值 2：续费 3：升级'
                    'pay_mode' => $pay_status, //支付方式 2：支付宝 1：微信支付  3：快捷支付
                    'add_time' => time(),
                    'order_sn' => date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8),
                    'goods_name' => $html_title,
                    'vip' => $level,
                    'member_class' => $user['member_class'],//会员充值前等级 2：VIP 3：svip 4:服务中心'
                    'pay_status' => 1 // 支付状态 1：待支付  2：已支付 3：支付失败'
                ];
                $pay_log_id = Db::name('pay_log')->insertGetId($pay_log);
                if($pay_log_id && $pay_log['order_sn']){
                    $m = new \app\index\controller\Pay();
                    // 会员充值方式
                    switch ($pay_status) {
                        case 2:
                            // 支付宝支付 生成h5可以跳转的支付链接
                            $url = $m->create($html_title, $pay_log['order_sn'], $actual_money);
                            return json($this->outJson(1, '操作成功', $url));
                            break;
                        case 1:
                            // 微信支付
                            $res = $m->createWxPay($html_title, $pay_log['order_sn'], $actual_money);
                            return json($res);
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
                            return json($this->outJson(1, '操作成功', $res['data']));
                            break;
                    }
                }else{
                    return json($this->outJson(0, '操作失败'));
                }

//                $data = $model->userPay($this->uid,$level,$pay_status);
//                return json($data);
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'),$e->getMessage());
        }
    }

}
