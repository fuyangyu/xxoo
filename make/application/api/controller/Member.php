<?php
namespace app\api\controller;
use PhpMyAdmin\Dbi\DbiDummy;
use think\Cache;
use think\Db;

class Member extends Base
{

    //个人信息渲染页
    public function userInfoActio(){
        return $this->fetch('demo');
    }

    //设置昵称渲染页
    public function nicknameActio(){
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $data = Db::name('member')->field('phone,nick_name')->where(['uid' => $uid])->find();
        $nickname = !empty($data['nick_name'])?$data['nick_name']:$data['phone'];

        return json($this->outJson(1,'获取成功',$nickname));
    }

    /**
     * 我的
     * @return \think\response\Json
     */
    public function user(){
        try{
            if ($this->request->isPost()) {
                $data = array();
                $uid = $this->request->param('uid');
                if(!$uid) return json($this->outJson(0,'参数错误'));
                //用户信息
                $user = Db::name('member')->where('uid',$uid)->field('nick_name,face,member_class,vip_end_time,phone,withdrawal_password,member_brokerage_money,task_money,channel_money,static_money,withdraw_money,have_withdrawal_money')->find();
                //直推人数
                $data['directNum'] = Db::name('member')->where('invite_uid',$uid)->count();

//                $money = Db::name('member')->where('uid',$uid)->field('member_brokerage_money,task_money,channel_money,static_money,withdraw_money,have_withdrawal_money')->find();
                if($user) {
                    //累计收益
                    $data['total_money'] = sprintf("%.2f",$user['member_brokerage_money']+$user['task_money']+$user['channel_money']+$user['static_money']);
                    //我的余额
                    $data['balance_money'] = sprintf("%.2f",$data['total_money'] - $user['withdraw_money'] - $user['have_withdrawal_money']);
                    //是否设置密码
                    $data['password'] = !empty($user['withdrawal_password'])?1:0;

                    $data['user']['nick_name'] = $user['nick_name'];
                    if(!empty($user['face'])){
                        $data['user']['face'] = $this->request->domain().$user['face'];
                    }
                    $data['user']['member_class'] = $user['member_class'];
                    $data['user']['vip_end_time'] = $user['vip_end_time'];
                    $data['user']['phone'] = $user['phone'];
                }
                //待提交 审核中 已驳回
                $taskSql = "SELECT
                            SUM(CASE WHEN is_check = 0 THEN 1 ELSE 0 END) as stay_task,
                            SUM(CASE WHEN is_check = 2 THEN 1 ELSE 0 END) as halfway_task,
                            SUM(CASE when is_check = 3 THEN 1 ELSE 0 END) as reject_task
                            FROM wld_send_task_log WHERE uid={$uid}";
                $task = Db::query($taskSql);
                $data['stay_task'] = $task[0]['stay_task'];    //待提交数量
                $data['halfway_task'] = $task[0]['halfway_task'];  //审核中数量
                $data['reject_task'] = $task[0]['reject_task'];    //已驳回数量

                //是否设置了银行卡
                $alipay = Db::name('alipay_info')->where('uid',$uid)->find();
                $bank = Db::name('bank_info')->where('uid',$uid)->find();
                $data['alipay'] = $alipay?1:0;
                $data['bank'] = $bank?1:0;

                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }
    }

    /**
     * 获取用户团队人数
     * @return \think\response\Json
     */
    public function userTeamNum(){

        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        if($team = Cache::get('teamData'.$uid)) {
            $num =count($team);
        }else{
            $user = Db::name('member')->field('uid,nick_name,phone,face,member_class,invite_time,invite_uid')->select();
            $data = $this->GetTeamMember($user,$uid);
            $num = count($data);
            Cache::set('teamData'.$uid,$data,86400);
        }
        return json($this->outJson(1,'获取成功',$num));

    }

    /**
     * 我的-直推明细
     * @return \think\response\Json
     */
    public function userDirectRecord(){

        $uid = $this->request->param('uid');
        $page = $this->request->param('page',1); //页数
        $limit = 10;    //每页数量
        $start = 0;     //开始位置
        if ($page > 1) {
            $start = ($page-1) * $limit;
        }
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $user = Db::name('member')->where('invite_uid',$uid)->field('uid,nick_name,phone,face,member_class,invite_time')->limit($start,$limit)->select();
        if($user){
            foreach($user as &$value){
                $value['phone'] = cp_replace_phone($value['phone']);
                $value['invite_time'] = date("Y-m-d",strtotime($value['invite_time']));
                if(!empty($value['face'])){
                    $value['face'] = $this->request->domain().$value['face'];
                }
            }
        }
        return json($this->outJson(1,'获取成功',$user));
    }

    /**
     * 我的-团队明细
     * @return \think\response\Json
     */
    public function userTeamRecord(){
        $uid = $this->request->param('uid');
        $page = $this->request->param('page',1); //页数
        $limit = 10;    //每页数量
        $start = 0;     //开始位置
        if ($page > 1) {
            $start = ($page-1) * $limit;
        }
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $team = Cache::get('teamData'.$uid);
        if($team) {
            $data = array_slice($team,$start,$limit);
            return json($this->outJson(1,'获取成功',$data));
        }else{
            $user = Db::name('member')->field('uid,nick_name,phone,face,member_class,invite_time,invite_uid')->select();
            $team = $this->GetTeamMember($user,$uid);
            if(!empty($team)){
                foreach($team as &$value){
                    $value['phone'] = cp_replace_phone($value['phone']);
                    $value['invite_time'] = date('Y-m-d',strtotime($value['invite_time']));
                    if(!empty($value['face'])){
                        $value['face'] = $this->request->domain().$value['face'];
                    }
                }
                Cache::set('teamData'.$uid,$team,86400);
                $data = array_slice($team,$start,$limit);

                return json($this->outJson(1,'获取成功',$data));
            }

        }
        return json($this->outJson(0,'获取失败'));
    }

    /**
     * 我的收益
     * @return \think\response\Json
     */
    public function userEarnings(){
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $money = Db::name('member')->where('uid',$uid)->field('member_class,member_brokerage_money,task_money,channel_money,static_money')->find();
        if($money) {
            //累计收益
            $money['total_money'] = sprintf("%.2f",$money['member_brokerage_money']+$money['task_money']+$money['channel_money']+$money['static_money']);
        }
        return json($this->outJson(1,'获取成功',$money));
    }

    /**
     * 收益明细
     * @return \think\response\Json
     */
    public function userEarningsLog(){
        $uid = $this->request->param('uid');
        if(!$uid) return json($this->outJson(0,'参数错误'));
        $ms = $this->request->param('ms',0);    //时间
        $type = $this->request->param('type',0);    //0默认是全部
        $page = $this->request->param('page',1); //页数
        $where = '';
        $limit = 10;    //每页数量
        $start = 0;     //开始位置
        if ($page > 1) {
            $start = ($page-1) * $limit;
        }

        if($type == 4){ //静态收益(未完成)
            if(!empty($ms)){
                $date =" and DATE_FORMAT(channel_time, '%Y-%c') = '$ms'";
            }else{
                $date = '';
            }
            $sql = "SELECT * FROM wld_channel_log WHERE uid=$uid $date ORDER BY id LIMIT $start,$limit;";
            $log = Db::query($sql);
            if(!empty($log)){
                foreach($log as $k => &$v){
                    $log[$k]['html'] = $v['channel_time'].'会员静态收益';
                }
            }

        }else {
            $where .= ' uid ='.$uid;
            if($type == 1){ //推荐佣金
                $where .= ' and type in(1,2,3) and brokerage_type = 1';
            }
            if($type == 2){ //任务收入
                $where .= ' and type = 4 and brokerage_type = 2';
            }
            if($type == 3){ //渠道佣金
                $where .= ' and type = 4 and brokerage_type = 3';
            }

            if(!empty($ms)){
                $date =" and DATE_FORMAT(add_time, '%Y-%c') = '$ms'";
            }else{
                $date = '';
            }

            $sql = "SELECT * FROM wld_brokerage_log WHERE {$where} {$date} ORDER BY id LIMIT $start,$limit;";
            $log = Db::query($sql);
//            $phone = $this->getUserPhone($uid); //获取用户手机
//            $str_phoone = cp_replace_phone($phone);
            if(!empty($log)){
                foreach ($log as $key => $item) {
                    if($item['type'] == 4 && $item['brokerage_type'] == 2){ //判断任务收入显示内容
                        $title = Db::name('task')->where(['task_id' => $item['tid']])->value('title');
                        $log[$key]['html'] = '完成“'.$title.'”获得任务收入';
//                        $log[$key]['show_money'] = $item['money'];
                    }elseif($item['type'] == 4 && $item['brokerage_type'] == 3){    //判断推荐佣金显示内容
                        $phone = Db::name('member')->where(['uid' => $item['sid']])->value('phone');
                        $log[$key]['html'] = '用户'.cp_replace_phone($phone).'完成任务获得渠道佣金';
                    }elseif(in_array($item['type'],[1,2,3]) && $item['brokerage_type'] == 1){   //判断推荐会员显示内容
                        $phone = Db::name('member')->where(['uid' => $item['sid']])->value('phone');
                        $log[$key]['html'] = '用户'.cp_replace_phone($phone).'成为会员获得推荐佣金';
                    }
                }

            }
        }

        return json($this->outJson(1,'获取成功',$log));
    }

    /**
     * 静态分红资金池
     * @return \think\response\Json
     */
    public function userEarningsPool(){

        $data = array();
        //平台总数据
        $money = Db::name('earnings')->where(['send_id'=>666])->find();
        if($money){
            //平台分红总额
            $data['fh_money'] = sprintf("%.2f",($money['member_total_money']*0.03 + $money['task_total_money']*0.05)*0.8);
            //公益基金总额
            $data['gyj_money'] = sprintf("%.2f",($money['member_total_money']*0.03 + $money['task_total_money']*0.05)*0.2);
        }
        //本月数据
        //本月会员收入
        $currentMemberSql = "SELECT SUM(money) as member_money FROM wld_pay_log WHERE pay_status = 2 and from_unixtime(pay_time,'%Y-%m') = date_format(now(), '%Y-%m');";
        $member_money = Db::query($currentMemberSql);
        $data['current_member_money'] = !empty($member_money[0]['member_money'])?$member_money[0]['member_money']:0;
        //本月任务收入
        $currentTaskSql = "SELECT SUM(task_money) as task_money FROM wld_send_task_log WHERE is_check = 1 AND date_format(check_time, '%Y-%m') = date_format(now(), '%Y-%m');";
        $task_money = Db::query($currentTaskSql);
        $data['current_task_money'] = !empty($task_money[0]['task_money'])?$task_money[0]['task_money']:0;
        //本月分红金额
        $data['current_fh_money'] = sprintf("%.2f",($data['current_member_money']*0.03 + $data['current_task_money']*0.05)*0.8);
        //本月公益基金
        $data['current_gyj_money'] = sprintf("%.2f",($data['current_member_money']*0.03 + $data['current_task_money']*0.05)*0.2);


        //检测是否已计算前一天的数据
        $dayLog = Db::name('current_log')->where(['grant_time' => date("Y-m-d",strtotime("-1 day"))])->find();
        if(!$dayLog){
            //本月每日数据（只展示到前一天的数据）
            //前一天会员总收入
            $beforeMemberSql = "SELECT SUM(money) as before_member_money FROM wld_pay_log WHERE pay_status = 2 and from_unixtime(pay_time,'%Y-%m-%d') = date_sub(curdate(),interval 1 day) ;";
            $day_member_money = Db::query($beforeMemberSql);
            $before_member_money = !empty($day_member_money[0]['before_member_money'])?$day_member_money[0]['before_member_money']:0;
            //前一天任务总收入
            $beforeTaksSql = "SELECT SUM(task_money) as before_task_money FROM wld_send_task_log WHERE is_check = 1 AND date_format(check_time, '%Y-%m-%d') = date_sub(curdate(),interval 1 day);";
            $day_task_money = Db::query($beforeTaksSql);
            $before_task_money =!empty($day_task_money[0]['before_task_money'])?$day_task_money[0]['before_task_money']:0;
            if($before_member_money && $before_task_money){
                $before_fh_money=sprintf("%.2f",($before_member_money*0.03+$before_task_money*0.05)*0.8);
                $before_vip_money=sprintf("%.2f",($before_member_money*0.03+$before_task_money*0.05)*0.8/6*1);
                $before_svip_money=sprintf("%.2f",($before_member_money*0.03+$before_task_money*0.05)*0.8/6*2);
                $before_serve_money=sprintf("%.2f",($before_member_money*0.03+$before_task_money*0.05)*0.8/6*3);
                $log = [
                    'fh' => $before_fh_money,
                    'vip_sum_earning' => $before_vip_money,
                    'svip_sum_earning' => $before_svip_money,
                    'serve_sum_earning' => $before_serve_money,
                    'grant_time' => date("Y-m-d",strtotime("-1 day")),
                    'add_time' => date('Y-m-d H:i:s'),
                ];
                Db::name('current_log')->insert($log);
            }
        }
        $currentLogSql = "SELECT * FROM wld_current_log WHERE date_format(grant_time, '%Y-%m') = date_format(now(), '%Y-%m');";
        $data['current_log'] = Db::query($currentLogSql);
        return json($this->outJson(1,'获取成功',$data));

    }

    /**
     * @return mixed
     * 昵称修改处理
     */
    public function setNickname(){
        try{
            $uid = $this->request->param('uid');
            if(!$uid) return json($this->outJson(0,'参数错误'));
            $nickname = trim($this->request->param('nickname'));
            $data = Db::name('member')->where('uid',$uid)->setField('nick_name',$nickname);
            if($data){
                return json($this->outJson(1,'修改成功'));
            }else{
                return json($this->outJson(0,'修改失败'));
            }
        }catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }

    }

    //填写推荐人渲染页
    public function inviteActio(){
        return $this->fetch('demo');
    }


    /**
     * 填写推荐人处理
     * @return \think\response\Json
     */
    public function setInvite(){
        try{
            $uid = $this->request->param('uid');
            $phone = trim($this->request->param('phone'));
            if(!$uid || !$phone) return json($this->outJson(0,'参数错误'));

            //获取填写推荐人uid
            $invite = Db::name('member')->where('phone',$phone)->field('uid,invite_uid')->find();
            if(!$invite) return json($this->outJson(0,'推荐人不存在'));
            //推荐人不能是已推荐的用户
            if($invite['invite_uid'] == $uid) return json($this->outJson(0,'推荐人不能是已推荐的用户'));
            //获取当前用户是否有推荐人
            $former_uid = Db::name('member')->where('uid',$uid)->value('invite_uid');
            if($former_uid) return json($this->outJson(0,'已有推荐人'));
            $member = new \app\api\model\Member();
            $resParent = $member->getInviteCodeParentUid($invite['uid']);
            //修改推荐人
            $resParent['invite_uid'] = $invite['uid'];
            $resParent['invite_time'] = date('Y-m-d H:i:s');
            $data = Db::name('member')->where('uid',$uid)->update($resParent);
            if($data){
                return json($this->outJson(1,'修改成功'));
            }else{
                return json($this->outJson(0,'修改失败'));
            }
        }catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }


    }
    /**
     * @return \think\response\Json
     * 获取用户信息
     */
    public function userInfo(){
        try{
            $uid = $this->request->param('uid');
            if(!$uid) return json($this->outJson(0,'参数错误'));
            $model = new \app\api\model\Member();
            $data = $model->getuserInfo($uid);
            if(!empty($data['face'])){
                $data['face'] = $this->request->domain().$data['face'];
            }
            if($data){
                return json($this->outJson(1,'获取成功',$data));
            }else{
                return json($this->outJson(0,'获取失败'));
            }
        }catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }
    }


    // 头像上传
    public function upFace()
    {
        try{
            if ($this->request->isPost()) {
                $file = request()->file('picture');
                $uid = $this->request->param('uid');
                if (!$file || !$uid) return json($this->outJson(0,'请求参数不完整'));
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads'. DS .'face');
                    $path = '/uploads'. DS .'face';
                    $getSaveName = str_replace("\\","/",$info->getSaveName());
                    $int = Db::name('member')->where(['uid' => $uid])->setField('face',$path.DS.$getSaveName);
                    if ($int) {
                        return json($this->outJson(1,'上传成功',['face' => $this->request->domain().$path.DS.$getSaveName]));
                    } else {
                        return json($this->outJson(0,'上传失败'));
                    }

            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }


    // 绑定支付银行卡(未调用)
    public function payBank()
    {
        try{
            if ($this->request->isPost()) {
                $checkData = $this->request->param();
                $status = $this->request->param('status',1);
                $validate = new \app\api\validate\Bank();
                if (!$vdata = $validate->scene('all')->check($checkData)) {
                    return json($this->outJson(0,$validate->getError()));
                }
                /*$code = $this->request->param('code');
                // 验证短信验证码
                $bool = $this->checkPhoneCode($this->getUserPhone(), trim($code), 'band');
                if (!$bool) return json($this->outJson(0,'短信验证码错误'));*/

                $insert = [
                    'phone' => trim($checkData['phone']),
                    'id_card_num' => trim($checkData['id_card_num']),
                    'user_name' => trim($checkData['user_name']),
                    'bank_name' => trim($checkData['bank_name']),
                    'bank_branch_name' => trim($checkData['bank_branch_name']),
                    'bank_card_num' => trim($checkData['bank_card_num']),
                    'uid' => $this->uid,
                    'add_time' => date('Y-m-d H:i:s')
                ];
                if ($status == 1) {
                    $is_check = Db::name('bank_pay_info')->where(['uid' => $this->uid])->value('id');
                    if ($is_check) return json($this->outJson(0,'提交方式错误'));
                    // 新增
                    $id = Db::name('bank_pay_info')->insertGetId($insert);
                    if ($id) {
                        return json($this->outJson(1,'操作成功'));
                    } else {
                        return json($this->outJson(0,'操作失败'));
                    }
                } else {
                    // 修改
                    unset($insert['uid']);
                    unset($insert['add_time']);
                    Db::name('bank_pay_info')->where(['uid' => $this->uid])->update($insert);
                    return json($this->outJson(1,'操作成功'));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 服务中心升级申请
     * @return \think\response\Json
     */
    public function upServe()
    {
        try{
            if ($this->request->isPost()) {
                $phone = $this->request->param('phone');
                $name = $this->request->param('name');
                $uid = $this->request->param('uid');
                if (!$phone || !$uid) return json($this->outJson(0, '请求参数错误'));
                if (!cp_isMobile($phone)) return json($this->outJson(0, '手机号码格式错误'));
                if (!$name) return json($this->outJson(0, '申请人姓名不能为空'));
                $check  = Db::name('member_serve')->where(['uid' => $uid,'status'=>1])->find();
                if ($check) return json($this->outJson(0, '您已经申请过了,不要重复申请'));
                $member_class = Db::name('member')->where('uid',$uid)->value('member_class');
                $fileData = cp_getCacheFile('system');
                $acommon_money = isset($fileData['common_money']) ? $fileData['common_money'] : '200';
                $expert_money = isset($fileData['expert_money']) ? $fileData['expert_money'] : '1000';
                $serve_money = isset($fileData['serve_money']) ? $fileData['serve_money'] : '10000';
                switch($member_class){
                    case 1:
                        $amount = $serve_money;
                        break;
                    case 2:
                        $amount = $serve_money - $acommon_money;
                        break;
                    case 3:
                        $amount = $serve_money - $expert_money;
                        break;
                    case 4:
                        $amount = $serve_money;
                        break;
                    default:
                        $amount = $serve_money;

                }
                $insert = [
                    'uid' => $uid,
                    'phone' => $phone,
                    'name' => trim($name),
                    'status' => 1,
                    'amount' => $amount,
                    'add_time' => date('Y-m-d H:i:s')
                ];
                // 启动事务
                Db::startTrans();
                try{
                    $id = Db::name('member_serve')->insertGetId($insert);
                    if ($id) {
                        // 提交事务
                        Db::commit();
                        return json($this->outJson(1, '操作成功'));
                    } else {
                        // 回滚事务
                        Db::rollback();
                        return json($this->outJson(0, '操作失败'));
                    }
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return json($this->outJson(0, '操作失败'));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }
}
