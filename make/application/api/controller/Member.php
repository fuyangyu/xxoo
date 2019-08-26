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
        $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
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
                $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
                if(!$uid) return json($this->outJson(2,'未登录'));
                //用户信息
                $data['user'] = Db::name('member')->where('uid',$uid)->field('nick_name,face,member_class,vip_end_time')->find();
                //直推人数
                $data['directNum'] = Db::name('member')->where('invite_uid',$uid)->count();

                $money = Db::name('member')->where('uid',$uid)->field('member_brokerage_money,task_money,channel_money,static_money,withdraw_money,have_withdrawal_money')->find();
                if($money) {
                    //累计收益
                    $data['total_money'] = $money['member_brokerage_money']+$money['task_money']+$money['channel_money']+$money['static_money'];
                    //我的余额
                    $data['balance_money'] = $data['total_money'] - $money['withdraw_money'] - $money['have_withdrawal_money'];
                }
                //待提交 审核中 已驳回
                $taskSql = "SELECT
                            SUM(CASE WHEN is_check = 0 THEN 1 ELSE 0 END) as stay_task,
                            SUM(CASE WHEN is_check = 2 THEN 1 ELSE 0 END) as halfway_task,
                            SUM(CASE when is_check = 3 THEN 1 ELSE 0 END) as reject_task
                            FROM wld_send_task_log ";
                $task = Db::query($taskSql);
                $data['stay_task'] = $task[0]['stay_task'];    //待提交数量
                $data['halfway_task'] = $task[0]['halfway_task'];  //审核中数量
                $data['reject_task'] = $task[0]['reject_task'];    //已驳回数量

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
        if($this->request->isPost()) {
            $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
            if(!$uid) return json($this->outJson(2,'未登录'));
            if(Cache::get('team'.$uid)) {
                $num = Cache::get('team'.$uid);
            }else{
                $data = $this->getUserTeam($uid);
                $num = count($data);
                Cache::set('team'.$uid,$num,86400);
            }
            return json($this->outJson(1,'获取成功',$num));
        }else{
            return json($this->outJson(500,'非法操作'));
        }

    }

    /**
     * 我的-直推明细
     * @return \think\response\Json
     */
    public function userDirectRecord(){

        $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
        if(!$uid) return json($this->outJson(2,'未登录'));
        $user = Db::name('member')->where('invite_uid',$uid)->field('nick_name,phone,face,member_class,invite_time')->select();
        if($user){
            $model = new \app\api\model\Member();
            foreach($user as &$value){
                $value['phone'] = cp_replace_phone($value['phone']);
                $value['member_class'] = $model->getMemberClassAttr($value['member_class']);
            }
        }
        return json($this->outJson(1,'获取成功',$user));
    }

    /**
     * 我的-团队明细
     * @return \think\response\Json
     */
    public function userTeamRecord(){
        $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
        if(!$uid) return json($this->outJson(2,'未登录'));
        if(Cache::get('team'.$uid)) {
            $data = Cache::get('team'.$uid);
        }else{
            $data = $this->getUserTeam($uid);
            Cache::set('team'.$uid,$data,86400);
        }
        if(!empty($data)){
            $model = new \app\api\model\Member();
            foreach($data as &$value){
                $value['phone'] = cp_replace_phone($value['phone']);
                $value['member_class'] = $model->getMemberClassAttr($value['member_class']);
            }
        }
        return json($this->outJson(1,'获取成功',$data));
    }

    /**
     * 我的收益
     * @return \think\response\Json
     */
    public function userEarnings(){
        $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
        if(!$uid) return json($this->outJson(2,'未登录'));
        $money = Db::name('member')->where('uid',$uid)->field('member_class,member_brokerage_money,task_money,channel_money,static_money')->find();
        if($money) {
            //累计收益
            $money['total_money'] = $money['member_brokerage_money']+$money['task_money']+$money['channel_money']+$money['static_money'];
        }
        return json($this->outJson(1,'获取成功',$money));
    }

    /**
     * 收益明细
     * @return \think\response\Json
     */
    public function userEarningsLog(){
        $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
        $ms = $this->request->param('ms',0);    //时间
        $type = $this->request->param('type',0);    //0默认是全部
        $page = $this->request->param('page',1); //页数
        $where = '';
        if(!$uid) return json($this->outJson(2,'未登录'));
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
                foreach($log as $k => $v){
                    $log[$k]['html'] = $v['channel_time'].'会员静态收益';
                }
            }
        }else {
            $condition = " (l.uid = $uid OR l.uid_one = $uid OR l.uid_two = $uid OR l.serve_uid_one = $uid OR l.serve_uid_two = $uid)";
            if($type == 1){ //推荐佣金
                $where = ' and l.type in(1,2,3)';
            }
            if($type == 2){ //任务收入
                $where = ' and l.type = 4';
                $condition = ' l.uid ='.$uid;
            }
            if($type == 3){ //渠道佣金
                $where = ' and l.type = 4';
            }

            if(!empty($ms)){
                $date =" and DATE_FORMAT(l.add_time, '%Y-%c') = '$ms'";
            }else{
                $date = '';
            }

            $sql = "SELECT * FROM wld_brokerage_log AS l WHERE $condition $date $where ORDER BY l.id LIMIT $start,$limit;";
            $log = Db::query($sql);
            $phone = $this->getUserPhone($uid); //获取用户手机
            $str_phoone = cp_replace_phone($phone);
            if(!empty($log)){
                foreach ($log as $key => $item) {
                    if($item['type'] == 4 && $item['uid'] == $uid){ //判断任务收入显示内容
                        $title = Db::name('task')->where(['task_id' => $item['tid']])->value('title');
                        $log[$key]['html'] = '完成“'.$title.'”获得任务收入';
                        $log[$key]['show_money'] = $item['task_money'];
                    }elseif($item['type'] == 4 && $item['uid'] != $uid){    //判断推荐佣金显示内容
                        $log[$key]['html'] = '用户'.$str_phoone.'完成任务获得渠道佣金';
                    }elseif(in_array($item['type'],[1,2,3])){   //判断推荐会员显示内容
                        $log[$key]['html'] = '用户'.$str_phoone.'成为会员获得推荐佣金';
                    }
                    //获取显示金额
                    if ($item['uid_one'] == $uid) {
                        $log[$key]['show_money'] = $item['one_money'];

                    } elseif ($item['uid_two'] == $uid) {
                        $log[$key]['show_money'] = $item['two_money'];

                    } elseif ($item['serve_uid_one'] == $uid) {
                        $log[$key]['show_money'] = $item['serve_one_money'];

                    } elseif ($item['serve_uid_two'] == $uid) {
                        $log[$key]['show_money'] = $item['serve_two_money'];
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
        p($data);die;
        return json($this->outJson(1,'获取成功',$data));

    }

    /**
     * @return mixed
     * 昵称修改处理
     */
    public function setNickname(){
        try{
            $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
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
            $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
            $phone = trim($this->request->param('phone'));
            //获取填写推荐人uid
            $imvite_uid = Db::name('member')->where('phone',$phone)->value('uid');
            if(!$imvite_uid) return json($this->outJson(0,'推荐人不存在'));
            //获取当前用户是否有推荐人
            $former_uid = Db::name('member')->where('uid',$uid)->value('invite_uid');
            if($former_uid) return json($this->outJson(0,'已有推荐人'));
            //修改推荐人
            $mem = [
                'invite_uid' => $imvite_uid,
                'invite_time' => date('Y-m-d H:i:s')
            ];
            $data = Db::name('member')->where('uid',$uid)->update($mem);
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
            $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
            $model = new \app\api\model\Member();
            $data = $model->getuserInfo($uid);
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
                $picture = trim($this->request->param('picture'));
                if (!$picture) return json($this->outJson(0,'请求参数不完整'));
                //# dataURI base_64 编码上传 手机端常用方式
                $rootPath = './uploads/face/' . date('Ymd');
                $target = $rootPath . "/" . date('Ymd') . uniqid() . ".jpg" ;
                if (!file_exists($rootPath)) {
                    cp_directory($rootPath);
                }
                $img = base64_decode($picture);
                if (file_put_contents($target, $img)){
                    $face = substr($target,1);
                    $int = Db::name('member')->where(['uid' => $this->uid])->setField('face',$face);
                    if ($int) {
                        return json($this->outJson(1,'上传成功',['face' => $face]));
                    } else {
                        return json($this->outJson(0,'上传失败'));
                    }
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


    // 个人资料 (暂未调用)
    public function info()
    {
        try{
            if ($this->request->isPost()) {
                $status = $this->request->param('status');
                $data = [
                    'true_name' => $this->request->param('true_name',''),
                    'province' => $this->request->param('province',''),
                    'city' => $this->request->param('city',''),
                    'district' => $this->request->param('district',''),
                    'address' => $this->request->param('address',''),
                ];
                $model = new \app\api\model\Member();
                if ($status == 1) {
                    // 获取资料
                    $data = $model->getMemberInfo($this->uid);
                    return json($this->outJson(1,'获取成功',$data));
                } else {
                    // 保存
                    $bool = $model->setStoreMemberInfo($this->uid,$data);
                    if ($bool) {
                        return json($this->outJson(1,'操作成功'));
                    } else {
                        return json($this->outJson(1,'操作失败'));
                    }
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
     * 我的团队 - 获取下线会员级别数量 （未调用）
     * @return \think\response\Json
     */
    public function getUserChildTeams()
    {
        try{
            if ($this->request->isPost()) {
                $model = new \app\api\model\Member();
                $page = $this->request->param('page',1,'intval');
                $data = $model->getUserChildTeams($this->uid,$page,10);
                return json($data);
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
                $content = $this->request->param('content');
                if (!$phone || !$content) return json($this->outJson(0, '请求参数错误'));
                if (!cp_isMobile($phone)) return json($this->outJson(0, '手机号码格式错误'));
                if (!$name) return json($this->outJson(0, '申请人姓名不能为空'));
                if (!$content) return json($this->outJson(0, '申请的理由不能为空'));
                $check  = Db::name('member_serve')->where(['uid' => $this->uid])->value('id');
                if ($check) return json($this->outJson(0, '您已经申请过了,不要重复申请'));
                $insert = [
                    'uid' => $this->uid,
                    'phone' => $phone,
                    'name' => trim($name),
                    'content' => trim($content),
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
