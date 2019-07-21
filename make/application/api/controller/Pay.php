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
}
