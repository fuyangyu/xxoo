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
            $model = new \app\admin\model\HireLog();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'userLevel' => $this->request->param('userLevel',''),
                'type' => $this->request->param('type',0),
                'check' => $this->request->param('check')
            ];
            $data = $model->getListData($model->filtrationWhere($param),$this->request->param('limit',15));
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
            'business' => $this->getBusiness()
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
                'pay_status' => $this->request->param('pay_status','')
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
        $id = $this->request->param('id');
        if ($this->request->isAjax()) {
            // 审核失败 退换提现金额
            $status = $this->request->param('status');
            $check_msg = $this->request->param('check_msg');
            $uid = $this->request->param('uid');
            $money = $this->request->param('money');
            Db::startTrans();
            try{
                $bl = 1;
                if ($status == 2) {
                    // 审核失败 退换用户提现金额
                    if (cp_mbs_strlen($check_msg) > 20) {
                        return $this->outJson(1,'审核失败原因不能超过20个字符！');
                    }
                    $up = Db::name('deposit_log')->where(['id' => $id])->update([
                        'is_check' => 2,
                        'check_msg' => $check_msg,
                        'is_down' => 1,
                        'check_time' => date('Y-m-d H:i:s')
                    ]);
                    $bl = Db::name('member')->where(['uid' => $uid])->setInc('balance',$money);
                } else {
                    $check = Db::name('bank_info')->where(['uid' => $uid])->find();
                    if (!$check) {
                        return $this->outJson(1,'未绑定银行卡无法进行提现操作！');
                    }
                    $up = Db::name('deposit_log')->where(['id' => $id])->update([
                        'is_check' => 1,
                        'is_down' => 1,
                        'check_time' => date('Y-m-d H:i:s')
                    ]);
                }
                if ($bl && $up) {
                    Db::commit();
                    return $this->outJson(0,'操作成功',[
                        'logMsg' => "提现审核成功-id($id)",
                        'url' => $this->entranceUrl . "/member/withdraw.html"
                    ]);
                } else {
                    Db::rollback();
                    return $this->outJson(1,'操作失败');
                }
            } catch(\Exception $e) {
                Db::rollback();
                return $this->outJson(1,'操作失败,debug:'. $e->getMessage());
            }
        } else {
            $old = Db::name('deposit_log')->where(['id' => $id])->find();
            $bankInfo = Db::name('bank_info')->where(['uid' => $old['uid']])->find();
            $result = [
                'phone' => $old['phone'],
                'money' => $old['money'],
                'uid' => $old['uid'],
                'user_name' => isset($bankInfo['user_name']) ? $bankInfo['user_name'] : '',
                'bank_name' => isset($bankInfo['bank_name']) ? $bankInfo['bank_name'] : '',
                'bank_branch_name' => isset($bankInfo['bank_branch_name']) ? $bankInfo['bank_branch_name'] : '',
                'bank_card_num' => isset($bankInfo['bank_card_num']) ? $bankInfo['bank_card_num'] : '',
            ];
            return $this->fetch('check_withdraw',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/member/withdraw",'name'=>'提现记录列表'],
                    ['url' => '','name'=>'提现审核']
                ],
                'data' => $result,
                'id' => $id
            ]);
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