<?php
namespace app\admin\controller;
use think\Db;
use think\Response;

class Member extends AdminBase
{
    // 初始化日志信息写入参数
    protected $logMsg;

    // 会员列表
    public function index()
    {
        return $this->fetch('index',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'会员列表']
            ],
            'userLevel' => $this->userLevel(2)
        ]);
    }

    // 下线用户列表
    public function downLine()
    {
        $id = $this->request->param('id');
        return $this->fetch('down_line',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => $this->entranceUrl . "/member/index",'name'=>'会员列表'],
                ['url' => '','name'=>'下线用户列表']
            ],
            'userLevel' => $this->userLevel(2),
            'uid' => $id,
        ]);
    }


    /**
     * 异步获取会员列表数据
     * @return array
     */
    public function getDownLineData()
    {
        if ($this->request->isAjax()) {
            $memberModel = new \app\admin\model\Member();
            $uid = $this->request->param('uid');
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'userLevel' => $this->request->param('userLevel',''),
            ];
            $wh = $memberModel->filtrationWhere($param);
            $data = $memberModel->getDownLineListData($wh,$uid,$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 异步获取会员列表数据
     * @return array
     */
    public function getIndexData()
    {
        if ($this->request->isAjax()) {
            $memberModel = new \app\admin\model\Member();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'userLevel' => $this->request->param('userLevel','')
            ];
            $data = $memberModel->getListData($memberModel->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 服务中心升级申请
     * @return mixed
     */
    public function serve()
    {
        return $this->fetch('serve',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'服务中心升级申请']
            ]
        ]);
    }

    /**
     * 异步获取会员列表数据
     * @return array
     */
    public function getServeData()
    {
        if ($this->request->isAjax()) {
            $memberModel = new \app\admin\model\Member();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
            ];
            $data = $memberModel->getServeData($memberModel->filtrationServeWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 服务中心审核通过/驳回
     * @return \think\response\Json
     */
    public function setServeStatus(){
        $id = $this->request->param('id');  //审核id
        $status = $this->request->param('status');  //审核状态 1通过 3驳回
        $uid = $this->request->param('uid');    //用户uid
        if(!$id || !$status || !$uid) return json($this->outJson(0,'参数错误'));

        if($status == 3){
            $sid = Db::name('member_serve')->where('id',$id)->setField('status',3);
            if($sid){
                return json($this->outJson(1,'驳回成功',$sid));
            }else{
                return json($this->outJson(0,'驳回失败',$sid));
            }
        }
        try{
            // 启动事务
            Db::startTrans();
            //获取用户信息
            $user = Db::name('member')->where(['uid' => $uid])->field('uid,phone,vip_end_time,total_money,member_brokerage_money,member_class,parent_level_1,parent_level_2,parent_level_3,invite_uid')->find();
            if (!$user) return json($this->outJson(0, '操作失败', ['debug' => '操作失败，错误编码002,用户信息获取失败']));
            $serve = Db::name('member_serve')->where('id',$id)->find();
            if(!$serve) return json($this->outJson(0,'获取申请服务中心信息失败'));

            if($status == 1){
                $member = array();
                $brokerage = array();
                $message = array();
                $html_title = '';
                $money_arr = cp_getCacheFile('system'); //获取充值金额
                $common_money = isset($money_arr['serve_money']) ? $money_arr['serve_money'] : 10000;
                $sid = Db::name('member_serve')->where('id',$id)->setField('status',2);
                if($user['member_class'] == 1){
                    $html_title = '普通用户升级为服务中心';
                    $member['vip_end_time'] = date('Y-m_d H:i:s', strtotime("+1 year"));
                }elseif($user['member_class'] == 2){
                    $html_title = 'VIP升级为服务中心补差价'.$serve['amount'];
                }elseif($user['member_class'] == 3){
                    $html_title = 'SVIP升级为服务中心补差价'.$serve['amount'];
                }elseif($user['member_class'] == 4){
                    $html_title = '服务中心续费'.$serve['amount'];
                    $member['vip_end_time'] = date('Y-m_d H:i:s', strtotime("+1 year 1months", strtotime($user['vip_end_time'])));
                }
                $pay_log = [
                    'uid' => $uid,
                    'money' => $common_money,
                    'actual_money' => $serve['amount'],
                    'type' => 3, //充值类型 1：充值 2：续费 3：升级'
                    'pay_mode' => 0, //支付方式 2：支付宝 1：微信支付  3：快捷支付
                    'add_time' => time(),
                    'order_sn' => date('YmdHis'),
                    'goods_name' => $html_title,
                    'vip' => 4,
                    'member_class' => $user['member_class'],//会员充值前等级 2：VIP 3：svip 4:服务中心'
                    'pay_status' => 2 // 支付状态 1：待支付  2：已支付 3：支付失败'
                ];
                $pay_log_id = Db::name('pay_log')->insertGetId($pay_log);
                if($sid && $pay_log_id){
                    $member['member_class'] = 4;
                    $member['vip_start_time'] = date('Y-m_d H:i:s');
                    $content = '升级为服务中心';
                    $member_id = Db::name('member')->where(['uid' => $uid])->update($member);
                    if ($member_id) {
                        $phone = substr_replace($user['phone'],'****',3,4);
                        //直推分佣
                        $oneData = Db::name('member')->where('uid',$user['invite_uid'])->field('uid,member_class,phone')->find();
                        if($oneData) {
                            $allot_one = Db::name('allot_log')->where(['user_level' => $oneData['member_class'], 'charge_type' => 1])->value('allot_one');
                            if (!empty($allot_one)) {
                                $one_money = $serve['amount'] * ($allot_one / 100);
                                //获取分佣用户信息
                                $brokerage[0]['uid'] = $oneData['uid'];
                                $brokerage[0]['money'] = $one_money;
                                $brokerage[0]['member_class'] = $oneData['member_class'];
                                $brokerage[0]['phone'] = $oneData['phone'];
                                $brokerage[0]['tid'] = $pay_log_id;
                                $brokerage[0]['sid'] = $user['uid'];
                                $brokerage[0]['type'] = $pay_log['type'];  //充值类型 1：充值 2：续费 3：升级
                                $brokerage[0]['brokerage_type'] = 1;  //佣金类型：1推荐佣金 2任务佣金 3渠道佣金
                                $brokerage[0]['add_time'] = date('Y-m_d H:i:s');

                                $message[0] = [  //直推用户
                                    'uid' => $user['invite_uid'],
                                    'content' => '您的团队用户' . $phone . $content . '，获得推荐佣金' . $one_money . '元',
                                    'add_time' => date('Y-m-d H:i:s')
                                ];

                                Db::name('member')->where(['uid' => $user['invite_uid']])->setInc('member_brokerage_money', $one_money);
                            }
                            //间推分佣
                            $twoData = Db::name('member')->where('uid', $user['parent_level_2'])->field('uid,member_class,phone')->find();
                            if ($twoData) {
                                $allot_two = Db::name('allot_log')->where(['user_level' => $twoData['member_class'], 'charge_type' => 1])->value('allot_two');
                                if (!empty($allot_two)) {
                                    $two_money = $serve['amount'] * ($allot_two / 100);
                                    //获取分佣用户信息
                                    $brokerage[1]['uid'] = $twoData['uid'];
                                    $brokerage[1]['money'] = $two_money;
                                    $brokerage[1]['member_class'] = $twoData['member_class'];
                                    $brokerage[1]['phone'] = $twoData['phone'];
                                    $brokerage[1]['tid'] = $pay_log_id;
                                    $brokerage[1]['sid'] = $user['uid'];
                                    $brokerage[1]['type'] = $pay_log['type'];  //充值类型 1：充值 2：续费 3：升级
                                    $brokerage[1]['brokerage_type'] = 1;  //佣金类型：1推荐佣金 2任务佣金 3渠道佣金
                                    $brokerage[1]['add_time'] = date('Y-m_d H:i:s');

                                    $message[1] = [  //间推用户
                                        'uid' => $user['parent_level_2'],
                                        'content' => '您的团队用户' . $phone . $content . '，获得推荐佣金' . $two_money . '元',
                                        'add_time' => date('Y-m-d H:i:s')
                                    ];

                                    Db::name('member')->where(['uid' => $user['parent_level_2']])->setInc('member_brokerage_money', $two_money);
                                }
                                //服务中心分佣
                                if (!empty($user['parent_level_3'])) {
                                    $service = array();
                                    $model = new \app\admin\model\Task();
                                    $service = $model->recursionService($user['parent_level_3'], $service);
                                    if (!empty($service)) {
                                        if (!empty($service[0])) {
                                            $one_serve = Db::name('member')->where('uid', $service[0])->field('uid,member_class,phone')->find();
                                            if ($one_serve) {
                                                $team_one = Db::name('allot_log')->where(['user_level' => $one_serve['member_class'], 'charge_type' => 1])->value('team_one');
                                                if (!empty($team_one)) {
                                                    //获取分佣用户信息
                                                    $serve_one_money = $serve['amount'] * ($team_one / 100);   //第一个服务中心分佣金额
                                                    $brokerage[2]['uid'] = $one_serve['uid'];
                                                    $brokerage[2]['money'] = $serve_one_money;
                                                    $brokerage[2]['member_class'] = $one_serve['member_class'];
                                                    $brokerage[2]['phone'] = $one_serve['phone'];
                                                    $brokerage[2]['tid'] = $pay_log_id;
                                                    $brokerage[2]['sid'] = $user['uid'];
                                                    $brokerage[2]['type'] = $pay_log['type'];  //充值类型 1：充值 2：续费 3：升级
                                                    $brokerage[2]['brokerage_type'] = 1;  //佣金类型：1推荐佣金 2任务佣金 3渠道佣金
                                                    $brokerage[2]['add_time'] = date('Y-m_d H:i:s');

                                                    $message[2] = [  //第一服务中心
                                                        'uid' => $service[0],
                                                        'content' => '您的团队用户' . $phone . $content . '，获得推荐佣金' . $serve_one_money . '元',
                                                        'add_time' => date('Y-m-d H:i:s')
                                                    ];

                                                    Db::name('member')->where(['uid' => $service[0]])->setInc('member_brokerage_money', $serve_one_money);
                                                }
                                            }
                                            if (isset($service[1])) {
                                                $two_serve = Db::name('member')->where('uid', $service[1])->field('uid,member_class,phone')->find();
                                                if ($two_serve) {
                                                    $team_two = Db::name('allot_log')->where(['user_level' => $two_serve['member_class'], 'charge_type' => 1])->value('team_two');
                                                    if (!empty($team_two)) {
                                                        //获取分佣用户信息
                                                        $serve_two_money = $serve['amount'] * ($team_two / 100);   //第二个服务中心分佣金额
                                                        $brokerage[3]['uid'] = $two_serve['uid'];
                                                        $brokerage[3]['money'] = $serve_two_money;
                                                        $brokerage[3]['member_class'] = $two_serve['member_class'];
                                                        $brokerage[3]['phone'] = $two_serve['phone'];
                                                        $brokerage[3]['tid'] = $pay_log_id;
                                                        $brokerage[3]['sid'] = $user['uid'];
                                                        $brokerage[3]['type'] = $pay_log['type'];  //充值类型 1：充值 2：续费 3：升级
                                                        $brokerage[3]['brokerage_type'] = 1;  //佣金类型：1推荐佣金 2任务佣金 3渠道佣金
                                                        $brokerage[3]['add_time'] = date('Y-m_d H:i:s');

                                                        $message[3] = [  //第二服务中心
                                                            'uid' => $service[0],
                                                            'content' => '您的团队用户' . $phone . $content . '，获得推荐佣金' . $serve_two_money . '元',
                                                            'add_time' => date('Y-m-d H:i:s')
                                                        ];

                                                        Db::name('member')->where(['uid' => $service[1]])->setInc('member_brokerage_money', $serve_two_money);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (!empty($brokerage)) {
                            Db::name('brokerage_log')->insertAll($brokerage);
                        }
                        //更新整个平台已完成任务总金额
                        Db::name('earnings')->where(['send_id' => 666])->setInc('member_total_money', $pay_log['money']);
                        //消息记录
                        Db::name('message_log')->insertAll($message);
                        // 提交事务
                        Db::commit();
                        return json($this->outJson(1, '操作成功'));
                    } else {
                        // 回滚事务
                        Db::rollback();
                        return json($this->outJson(0,'操作失败',['debug' => '操作失败，错误编码008错误体debug：修改订单状态或会员状态失败' ]));
                    }
                }

            }
        }catch (\Exception $e){
            // 回滚事务
            Db::rollback();
            $this->log('操作失败，错误编码007错误体debug：' . $e->getMessage(),'brokerage.log');
            return json($this->outJson(0,'操作失败',['debug' => '服务中心升级失败，错误编码007错误体debug：' . $e->getMessage()]));
        }

    }



    /**
     * 会员提现账户管理
     * @return array
     */
    public function depositAccount(){
        $memberModel = new \app\admin\model\Member();
        $param = [
            'keywords' => $this->request->param('keywords',''),
        ];
        $data = $memberModel->getDepositData($memberModel->filtrationDepositWhere($param),$this->request->param('limit',15));
        return $data;
    }

    /**
     * 编辑会员
     * @return mixed
     */
    public function edit()
    {
        $id = $this->request->param('id',0);
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Member();
            $res = $model->store($this->request->param());
            if ($res['code'] == 0) {
                $log_ids = $id ? $id : $res['data']['id'];
                return $this->outJson(0,'操作成功',[
                    'logMsg' => "编辑会员等级成功-id($log_ids)",
                    'url' => $this->entranceUrl . "/member/index.html"
                ]);
            } else {
                return $res;
            }
        } else {
            $result = Db::name('member')->where(['uid' => $id])->field('phone,member_class')->find();
            return $this->fetch('edit',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/gate/notice",'name'=>'会员列表'],
                    ['url' => '','name' => '编辑会员']
                ],
                'data' => $result,
                'userLevel' => $this->userLevel(2),
                'id' => $id
            ]);
        }
    }

    // 佣金记录表
    public function brokerage()
    {
        return $this->fetch('brokerage',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'佣金记录列表']
            ],
            'userLevel' => $this->userLevel(2),
            'business' => $this->getBusiness()
        ]);
    }

    /**
     * 异步获取佣金记录列表数据
     * @return array
     */
    public function getBrokerageData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\BrokerageLog();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'userLevel' => $this->request->param('userLevel',''),
                'type' => $this->request->param('type',0),
                'check' => $this->request->param('check')
            ];
            $data = $model->getListData($param,$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 充值记录列表
     * @return mixed
     */
    public function recharge()
    {
        return $this->fetch('recharge',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'充值记录列表']
            ],
            'userLevel' => $this->userLevel(2),
            'business' => $this->gerEcharge()
        ]);
    }

    /**
     * 异步获取充值记录列表数据
     * @return array
     */
    public function getRechargeData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\PayLog();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'pay_status' => $this->request->param('pay_status',''),
                'vip' => $this->request->param('vip',''),
            ];
            $data = $model->getListData($model->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 提现记录列表
     * @return mixed
     */
    public function withdraw()
    {
        return $this->fetch('withdraw',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'提现记录列表']
            ]
        ]);
    }


    /**
     * 异步获取提现记录列表数据
     * @return array
     */
    public function getWithdrawData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\DepositLog();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'check' => $this->request->param('check',9)
            ];
            $data = $model->getListData($model->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    // 分润收益明细
    public function profit()
    {
        return $this->fetch('profit',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'分润收益明细']
            ],
            'business' => $this->getBusiness()
        ]);
    }

    /**
     * 异步获取分润收益明细记录列表数据
     * @return array
     */
    public function getProfitData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\EarningsLog();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'type' => $this->request->param('type',0)
            ];
            $data = $model->getListData($model->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 提现审核
     * @return mixed
     */
    public function checkWithdraw()
    {
        if ($this->request->isAjax()) {
            // 审核失败 退换提现金额
            $status = $this->request->param('status');  //审核状态 1成功 2失败
            $check_msg = $this->request->param('check_msg');    //审核失败原因
            $id = $this->request->param('id');
            if($status == 1){   //成功
                $check = Db::name('deposit_log')->where('id',$id)->update(['is_check',1,'check_time'=>date('Y-m-d H:i:s')]);
                if($check){
                    return json($this->outJson(1,'审核成功',$id));
                }
            }elseif($status == 2){
                if(cp_mbs_strlen($check_msg) < 15){
                    $check = Db::name('deposit_log')->where('id',$id)->update(['is_check'=>2,'check_msg'=>$check_msg,'check_time'=>date('Y-m-d H:i:s')]);
                    if($check){
                        return json($this->outJson(1,'审核驳回成功',$id));
                    }else{
                        return json($this->outJson(0,'审核驳回失败',$id));
                    }
                }else{
                    return json($this->outJson(0,'审核驳回理由太多了'));
                }
            }

        }
    }

    /**
     * 提现申请确认打款成功
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function affirmRemit(){
        if($this->request->isAjax()){
            $id = $this->request->param('id');
            if(!$id) return json($this->outJson(0,'参数错误'));

            $status = Db::name('deposit_log')->where('id',$id)->update(['depos_status'=>2,'is_check'=>1,'check_time'=>date('Y-m-d H:i:s')]);
            if($status){
                return json($this->outJson(1,'确认打款成功'));
            }else{
                return json($this->outJson(0,'确认打款失败'));
            }
        }
    }

    /**
     * 会员收费金额设置
     * @return mixed
     */
    public function setting()
    {
        $data = cp_getCacheFile('system');
        $data['common_money'] = isset($data['common_money']) ? $data['common_money'] : '';
        $data['expert_money'] = isset($data['expert_money']) ? $data['expert_money'] : '';
        $data['serve_money'] = isset($data['serve_money']) ? $data['serve_money'] : '';
        $data['member_img'] = isset($data['member_img']) ? $data['member_img'] : '';
        if ($this->request->isAjax()) {
            $insert = array_merge($data,$this->request->param());
            cp_setCacheFile('system',$insert);
            return $this->outJson(0,'操作成功');
        } else {
            return $this->fetch('setting',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => '','name'=>'会员收费金额设置']
                ],
                'data' => $data
            ]);
        }
    }

    /**
     * 上传app会员充值图片
     * @return \think\response\Json
     */
    public function uploads()
    {
        return json($this->fileUploads('files','member'));
    }

    /**
     * 移除app会员充值图片
     * @return \think\response\Json
     */
    public function delUploads()
    {
        $file_url = $this->request->param('did');
        if (@unlink('.' . $file_url)) {
            return json($this->outJson(1,'移除成功!'));
        } else {
            return json($this->outJson(0,'移除失败!'));
        }
    }
}