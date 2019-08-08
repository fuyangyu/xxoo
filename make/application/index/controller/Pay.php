<?php
namespace app\index\controller;
use think\Db;
use think\Log;
class Pay
{
    protected $config;

    protected $fastPayConfig;

    /**
     * 初始化应用参数
     * Pay constructor.
     */
    public function __construct()
    {
        // 支付宝配置
        $this->config = [
            'app_id' => '2018111362152250',
            'rsaPrivateKey' => 'MIIEogIBAAKCAQEAx8nqYbhfom1FNKCkXZaKPCqiI+C5SUe2nFrK9R92n6Hk4YVDbO7zKp89F4rEhls1cgiF2D5a5hl6sQySpaRd8MRk3QVmcurAoJoTqceCRjDeosn5dL6DzOGnfBb2Sa1WdpGxnXdp05ET9NzLvDChHvsrbMLiCErgaUCUk+EU0OZwUg/B39GFSDRjJkgsmZmpG3cJwX4qzbl7jXa/WZDnNjnhivQmuhlwfG8o7drLthGtBRJn1opZ8tO+Np2xbpRnr6zPjGpXSkCcK/RWg0WUNuRmWH8cB8/CEbv5kzjJVbBxjhZMyzm6SKy445neGTgxee6smlwN0mH/XY+cTLl7zwIDAQABAoIBADxyBouSMLz/ulR11cTK6v/RFkUslGJrZABiJ82Ju2YIoSrqGsA2ezOGAgHBZjwQFFdv5K7MDsxXIRu35hZfaFyTzsBgBeL2y1jLxO81AWEjH/i4itSbX1z5WKbee51G6EXvRoGRw17TIeqPcsR5IgYxj63UOHax0LuHYmxrSNT6mVmbRzGqPzOuoHLUDk/tCORp5uqxTFhXcnG1TdPJhd29mRMC0RDTGodhuzn67nm8WeHzBdydOCAvV9iXnP7l0396N16AvkLTS2m0BCqCUs+WlKZps4pzrGcbW2E/f3JAXw90PaWG/5OdQfLrgQ/KYgnIYQ/nhJyTwofFkC3PmuECgYEA/5e0KrSsqygI5LhnHPfGj6QlXR9RdgfC7JJq6eth42WTlSm5xaHCHCqQpdrdKYMU291Na31tjtoIEyQHYsDf96VIy7zqMnUYYD7PeboRp4JYHhtpETyKyR2WbvSff7TbHkwVpXF3IkGCDFgFws1HeVUXTvVD/l4l+4Cz6gNRLX0CgYEAyBtwzk/yl1MrsuGA/P0jzwc3FhVJYY2sdwC69wlh92DY0lY6sCq5aPIDqYBLJUNs8P12E3AN6QhGvF/NVui/0fDocgKGBX1/Ob6Rf2F4ISap3wbUU1oglAFRqkUCvGkck8N7wlZyUxnZ4eQuXi9WLSraOJzFk/i/nao+58xUADsCgYBU/vv5H7A0EtIyTWhs57DuX6XVO+75E0etKFvJgm9BEaxsdD3FDS/h8f28SfY2MdMj8oombsWaNcNtqhOSsZVJY3u71Q0Ezo/WocyZLFlmnR/0kyTziHCWxPIb39mSvHAJuT+RjxQRHjvDIxp3V5CnNrbTmRiNZH25D2nFLmk2eQKBgDivdUwsX5c4a0eYE9cWyn8KFzO9QfVNkc6AOXZjhQnzuFgVLzjaUX2GGT1550+eAw1db4ZgFsCtpIQWS2/ULQqGyQIK7vY9L74m2saP0NzrO/G+2IToIhRrLzwhon3G9N5y4OsTW/1odE1GO1BY7nuLCRhCaMHbFETQhlNZkW4jAoGAP/JNM8D5NtNgM47DCvzMvyXIM7rgGJyHNNEmug2YorEwja5YHOwnYlc5fnNuzH1t9ZMilZm55X6r6han1LyG3q1lCqNVf/eKs/FdluBYyKkjMqiRM1FIBY6LZYHrmjvXFVws7kYxQbDdEPhvlmC2V9ZX0OLEnfmPJMfrWjFz2Vc=',
            'alipayrsaPublicKey' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAo1kn0uJT5EIkFYOxJ7YJQPvg1AfCMiWjgPiAEEe14ctqBHvBD3qXdwORXizuep7/oaY3pOIN65f7+Rgu3MeQtDQEySkmP7EJqPUV9rpSQxx9nBW2SBiXxYODpMz2oDll/cRs5Bbbr5IzwWWxp2U9zM4rhrbt2iNelC99dEVG1Qj2084gUWA0d7Bsk4hkq8maQZoHB20P7YcqBgcMxIjACHmhwh/DZhud+4HiI57epmckofy2X9+2LT1R0lgQD7cmBQ9IOJdGd60C/TtM2gVH3BJl6lqoJS34U1LXoL0wGH0ok+tJKZoHHsyVFtkHpbrQ+LC54bh314ggQHHdjmn5bwIDAQAB',
            'notify_url' => 'http://www.dotgomedia.com/index.php/pay/callback'
        ];

        // 快捷支付的配置
        $this->fastPayConfig = [
            'hmacVal' => 'e421d62950cc4f909a8aa6480dddb1f5',//商户秘钥
            'p1_MerchantNo' => '888105000002522',//商户号
            'p2_MerchantName' => '深圳市纵景电子商务有限公司',//商户名称
            'q6_ReturnUrl' => 'http://www.dotgomedia.com/index.php/index/showPay',//同步回调地址
            'q7_NotifyUrl' => 'http://www.dotgomedia.com/index.php/pay/shortcutPay', // 异步回调地址
        ];
    }


    /**
     * 支付宝创建app可以跳转的url
     * @param string $subject  商品标题
     * @param string $order_sn 订单号
     * @param float $total_amount 充值金额
     * @return string
     */
    public function create($subject, $order_sn, $total_amount)
    {
        //支付宝
        include_once ROOT_PATH . '/extend/alipaysdk/AopClient.php';
        include_once ROOT_PATH . '/extend/alipaysdk/request/AlipayTradeAppPayRequest.php';
        $config = [
            'appid' => $this->config['app_id'],
            'rsaPrivateKey' => $this->config['rsaPrivateKey'],//开发者私钥私钥
            'alipayrsaPublicKey' => $this->config[''],//支付宝公钥
            'charset' => strtolower('utf-8'),//编码
            'notify_url' => $this->config['notify_url'],//回调地址(支付宝支付成功后回调修改订单状态的地址)
            'payment_type' => 1,//(固定值)
            'seller_id' =>'',//收款商家账号
            'charset' => 'utf-8',//编码
            'sign_type' => 'RSA2',//签名方式
            'timestamp' => date("Y-m-d H:i:s"),
            'version'   =>"1.0",//固定值
            'url'       => 'https://openapi.alipay.com/gateway.do',//固定值
            'method'    => 'alipay.trade.app.pay',//固定值
        ];

        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $config['appid'];
        $aop->rsaPrivateKey = $config['rsaPrivateKey'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $config['alipayrsaPublicKey'];
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数

        $bizcontent = json_encode([
            'body'=>'**',
            'subject' => $subject,
            'out_trade_no' => $order_sn,//此订单号为商户唯一订单号
            'total_amount' => $total_amount,//保留两位小数
            'product_code' => 'QUICK_MSECURITY_PAY'
        ]);
        $request->setNotifyUrl($config['notify_url']);
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        return $response;//就是orderString 可以直接给客户端请求，无需再做处理。
    }

    /**
     * 支付宝支付 回调地址
     */
    public function callback()
    {
        include_once ROOT_PATH . '/extend/alipaysdk/AopClient.php';
        $aop = new \AopClient();
        $aop->alipayrsaPublicKey = $this->config['alipayrsaPublicKey'];
        //此处验签方式必须与下单时的签名方式一致
        $flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");
        //$log = ROOT_PATH . '/cache_data/pay_log.log';
        if ($flag) {
            // 验证成功
            // TODO 包括用户余额额度的增加 佣金状态 支付状态
            $out_trade_no = trim($_POST['out_trade_no']);
            // TODO 上线后需要将状态设置为false
            $data = $this->setOrderStatus($out_trade_no);
            if ($data['status'] == 1) {
                return 'success';
            } else {
                $msg = '支付失败,时间：' . date('Y-m-d H:i:s') . '具体的信息：' . $data['data']['debug'] . "\r\n";
                $file['time'] = date('Y-m-d H:i:s');
                $file['msg'] = $msg;
                $this->log($file,'zfb_pay.log');
                return 'error';
            }
        } else {
            $file['time'] = date('Y-m-d H:i:s');
            $file['msg'] = '支付失败,时间：' . date('Y-m-d H:i:s') . '信息：签名验证失败';
            $this->log($file,'zfb_pay.log');
            return 'error';
        }
    }

    /**
     * 微信支付回调地址
     */
    public function wxNotify()
    {
        //接收微信返回的数据数据,返回的xml格式
        $xmlData = file_get_contents('php://input');
        //将xml格式转换为数组
        $data = \wx\WxPay::instance()->FromXml($xmlData);
        //用日志记录检查数据是否接受成功，验证成功一次之后，可删除。
        //$log = ROOT_PATH . '/cache_data/wx_log.log';
        /*$file = fopen($log, 'a+');
        fwrite($file,var_export($data,true));*/
        //为了防止假数据，验证签名是否和返回的一样。
        //记录一下，返回回来的签名，生成签名的时候，必须剔除sign字段。
        $sign = $data['sign'];
        unset($data['sign']);
        if($sign == \wx\WxPay::instance()->getSign($data)){
            //签名验证成功后，判断返回微信返回的
            if ($data['result_code'] == 'SUCCESS') {
                //根据返回的订单号做业务逻辑
                // TODO 成功 修改订单状态 测试完成后 需要修改成false
                $res = $this->setOrderStatus($data['out_trade_no']);
                if ($res['status'] == 1) {
                    // 支付成功
                    return '<xml>
                              <return_code><![CDATA[SUCCESS]]></return_code>
                              <return_msg><![CDATA[OK]]></return_msg>
                              </xml>';
                } else {
                    $msg = '支付失败,时间：' . date('Y-m-d H:i:s') . '具体的信息：' . $data['data']['debug'] . "\r\n";
                    $file['time'] = date('Y-m-d H:i:s');
                    $file['msg'] = $msg;
                    $this->log($file,'wx_log.log');
                    return '<xml>
                    <return_code><![CDATA[FAIL]]></return_code>
                    <return_msg><![CDATA[ERROR]]></return_msg>
                    </xml>';
                }
            } else {
                //支付失败，输出错误信息
                $file['time'] = date('Y-m-d H:i:s');
                $file['msg'] = "错误信息：".$data['return_msg'];
                $this->log($file,'wx_log.log');
            }
        } else{
            $file['time'] = date('Y-m-d H:i:s');
            $file['msg'] = "错误信息：签名验证失败".date("Y-m-d H:i:s");
            $this->log($file,'wx_log.log');
        }
    }

    /**
     * 微信支付 生成预支付订单
     * @param string $subject 商品名称
     * @param string $order_sn 订单号
     * @param int $total_amount 商品金额
     * @return array
     */
    public function createWxPay($subject, $order_sn, $total_amount)
    {
        $data = \wx\WxPay::instance()->wxPay($subject, $order_sn, $total_amount);
        return $data;
    }

    /**
     * 修改订单状态
     * @param $out_trade_no
     * @param $status
     * @return array
     */
    protected function setOrderStatus($out_trade_no, $status = false)
    {
        $check = Db::name('pay_log')->where(['order_sn' => $out_trade_no,'pay_status' => 1])->find();
        if ($check) {
            // TODO 包括 佣金用户余额额度的增加 佣金状态 支付状态
            // TODO 新增记录业务分润收益记录 以及汇总业务分润金额
            // 启动事务
            Db::startTrans();
            try{
                /*// TODO 记录业务分润收益记录
                $c_data = [
                    'uid' => $check['uid'],
                    'task_log_id' => 0,
                    'order_sn' => $out_trade_no
                ];
                $init_earnings_log_data = createEarningsLog($check['money'],1,$check['uid'],$c_data);
                $eg_id = Db::name('earnings_log')->insertGetId($init_earnings_log_data);
                // TODO 汇总业务分润金额
                $earnings_data = Db::name('earnings')->where(['send_id' => 666])->find();
                if ($earnings_data) {
                    $earnings_id = Db::name('earnings')->where(['send_id' => 666])->update([
                        'terrace_total_money' => $init_earnings_log_data['terrace_money'] + $earnings_data['terrace_total_money'],
                        'static_total_money' => $init_earnings_log_data['static_money'] + $earnings_data['static_total_money'],
                        'fund_total_money' => $init_earnings_log_data['fund_money'] + $earnings_data['fund_total_money']
                    ]);
                } else {
                    $earnings_id = Db::name('earnings')->insertGetId([
                        'terrace_total_money' => $init_earnings_log_data['terrace_money'],
                        'static_total_money' => $init_earnings_log_data['static_money'],
                        'fund_total_money' => $init_earnings_log_data['fund_money']
                    ]);
                }*/
                // 修改支付状态
                $id_1 = Db::name('pay_log')->where(['id' => $check['id']])->update([
                    'pay_status' => 2,
                    'pay_time' => time()
                ]);
                if ($type = 1) {
                    // 会员充值
                    // 修改用户会员等级
                    if ($status) {
                        // TODO 测试状态 0.01
                        $member_class = 3;
                    } else {
                        $member_class = $this->getMoneyAttr($check['money']);
                    }
                    $id_2 = Db::name('member')->where(['uid' => $check['uid']])->setField('member_class',$member_class);
                } else {
                    // TODO 其他扩展
                    $id_2 = 1;
                }
                // 检测是否存在佣金
                $check_hire_log = Db::name('hire_log')
                        ->where(['order_sn' => $out_trade_no])
                        ->field('hire_money,uid,id')
                        ->select();
                if ($check_hire_log) {
                    $id_3 = 0;
                    $id_4 = 0;
                    // 存在佣金 发放佣金
                    foreach ($check_hire_log as $k => $v) {
                        // 给用户加佣金
                        $id_3 = Db::name('member')
                                ->where(['uid' => $v['uid']])
                                ->setInc('balance',$v['hire_money']);
                        // 修改佣金状态
                        $id_4 =Db::name('hire_log')->where(['id' => $v['id']])->update([
                            'is_check' => 1,
                            'check_time' => date('Y-m-d H:i:s')
                        ]);
                    }
                    if ($id_1 && $id_2 && $id_3 && $id_4) {
                        // 提交事务
                        Db::commit();
                        return $this->outJson(1,'操作成功');
                    } else {
                        // 回滚事务
                        Db::rollback();
                        return $this->outJson(0,'操作失败',['debug' => '操作失败，错误编码001,订单号：' . $out_trade_no .'[参数值1:' . $id_1 . '-参数值2:' . $id_2 . '-参数值3:' . $id_3 .'-参数值4:' . $id_4 . ']']);
                    }
                } else {
                    // 没有佣金的情况
                    if ($id_1 && $id_2) {
                        // 提交事务
                        Db::commit();
                        return $this->outJson(1,'操作成功');
                    } else {
                        // 回滚事务
                        Db::rollback();
                        return $this->outJson(0,'操作失败',['debug' => '操作失败，错误编码002,订单号：' . $out_trade_no .'[参数值1:' . $id_1 . '-参数值2:' . $id_2.']']);
                    }
                }
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->outJson(0,'操作失败',['debug' => '操作失败，错误编码003,订单号：' . $out_trade_no . '错误体debug：' . $e->getMessage()]);
            }
        } else {
            return $this->outJson(1,'操作成功');
        }
    }

    /**
     * 快捷支付【汇聚支付】-去支付
     * @param string $subject 商品名称
     * @param string $order_sn 订单号
     * @param int $total_amount 充值金额
     * @param array $bankInfo 用户银行卡信息
     * @param array $code 短信验证码
     * @return bool|mixed|string
     */
    public function createShortcutPay($subject, $order_sn, $total_amount,$bankInfo, $code)
    {
        $params = $this->createCommonPackageSubPay($subject, $order_sn, $total_amount,$bankInfo,$code);
        // 验证签名 方式 MD5
        $params['hmac'] = urlencode($this->hmacRequest($params,$this->fastPayConfig['hmacVal']));
        // 支付请求接口
        $url = 'https://www.joinpay.com/trade/fastpayPayApi.action';
        $result = $this->http_post($url,$params);
        $res = json_decode($result,true);
        $codes = [100,102];
        if (in_array($res['ra_Status'],$codes)) {
            return $this->outJson(1,'支付成功');
        } else {
            return $this->outJson(0,$res['rb_Msg']);
        }
    }

    /**
     * 快捷支付【汇聚支付】- 发送短信验证码
     * @param string $subject 商品名称
     * @param string $order_sn 订单号
     * @param int $total_amount 充值金额
     * @param array $bankInfo 用户银行卡信息
     * @return bool|mixed|string
     */
    public function createShortcutSms($subject, $order_sn, $total_amount,$bankInfo)
    {
        $params = $this->createPaySms($subject, $order_sn, $total_amount,$bankInfo);
        // 验证签名 方式 MD5
        $params['hmac'] = urlencode($this->hmacRequest($params,$this->fastPayConfig['hmacVal']));
        // 支付请求接口
        $url = 'https://www.joinpay.com/trade/fastpaySmsApi.action';
        $result = $this->http_post($url,$params);
        $res = json_decode($result,true);
        if ($res['ra_Status'] == 100) {
            return $this->outJson(1,'支付短信验证码发送成功');
        } else {
            return $this->outJson(0,$res['rb_Msg']);
        }
    }

    /**
     * 快捷支付异步回调地址
     */
    public function shortcutPay()
    {
        $data = request()->param();
        if ($data['r6_Status'] == 100 && $data['r1_MerchantNo'] == $this->fastPayConfig['p1_MerchantNo']) {
            // TODO 成功 修改订单状态 测试完成后 需要修改成false
            $res = $this->setOrderStatus($data['r2_OrderNo']);
            if ($res['status'] == 1) {
                return 'success';
            } else {
                $msg = '支付失败,时间：' . date('Y-m-d H:i:s') . '具体的信息：' . $data['data']['debug'] . "\r\n";
                $data['time'] = date('Y-m-d H:i:s');
                $data['msg'] = $msg;
                $this->log($data);
                return 'error';
            }
        } else {
            $data['send_time'] = date('Y-m-d H:i:s');
            $this->log($data);
            return 'error';
        }
    }

    /**
     * 快捷支付【发送验证短信码】异步回调地址
     */
    public function callSms()
    {
        return 'success';
    }

    protected function log($data, $file = 'pay.log'){
        $log_file = ROOT_PATH . '/cache_data/' . $file;
        $content = var_export($data,true);
        $content .= "\r\n\n";
        file_put_contents($log_file,$content, FILE_APPEND);
    }

    /**
     * 创建快捷支付公共的信息
     * @param string $subject 商品名称
     * @param string $order_sn 订单号
     * @param int $total_amount 充值金额
     * @param array $bankInfo 用户银行卡信息
     * @param string $code 短信验证码
     * @return mixed
     */
    protected function createCommonPackageSubPay($subject, $order_sn, $total_amount,$bankInfo,$code)
    {
        $params['p0_Version'] = '2.0';
        // 商户编号
        $params['p1_MerchantNo'] = $this->fastPayConfig['p1_MerchantNo'];
        // 商户名称
        $params['p2_MerchantName'] = $this->fastPayConfig['p2_MerchantName'];
        // 商品订单号
        $params['q1_OrderNo'] = $order_sn;
        // 订单金额
        $params['q2_Amount'] = $total_amount;
        // 交易币种
        $params['q3_Cur'] = 1;
        // 商品名称
        $params['q4_ProductName'] = $subject;
        // 页面通知地址
        $params['q6_ReturnUrl'] = $this->fastPayConfig['q6_ReturnUrl'];
        // 异步通知地址
        $params['q7_NotifyUrl'] = $this->fastPayConfig['q7_NotifyUrl'];
        // 银行编码
        $params['q8_FrpCode'] = 'FAST';
        // 支付人姓名
        $params['s1_PayerName'] = $bankInfo['user_name'];
        // 支付人证件类型
        $params['s2_PayerCardType'] = 1;
        // 支付人证件号
        $params['s3_PayerCardNo'] = $bankInfo['id_card_num'];
        // 支付人银行卡号
        $params['s4_PayerBankCardNo'] = $bankInfo['bank_card_num'];
        // 银行预留手机号
        $params['s7_BankMobile'] = $bankInfo['phone'];
        // 短信验证码 (新增)
        $params['t2_SmsCode'] = $code;
        return $params;
    }

    /**
     * 组装快捷支付发送短信验证码参数
     * @param $order_sn
     * @param $total_amount
     * @param $subject
     * @param $bankInfo
     * @return mixed
     */
    protected function createPaySms($subject, $order_sn, $total_amount,$bankInfo)
    {
        $params['p0_Version'] ='2.0';
        //2.商户编号
        $params['p1_MerchantNo'] = $this->fastPayConfig['p1_MerchantNo'];
        //3.商户名 称
        $params['p2_MerchantName'] = $this->fastPayConfig['p2_MerchantName'];
        //6.商品订 单号
        $params['q1_OrderNo'] = $order_sn;
        //7.订单金额
        $params['q2_Amount'] =  $total_amount;
        //8.交易币 种
        $params['q3_Cur'] = 1;
        //9.商品名称
        $params['q4_ProductName'] = $subject;
        //12.异步通知 地址
        $params['q7_NotifyUrl'] = $this->fastPayConfig['q7_NotifyUrl'];
        //13.银行编码
        $params['q8_FrpCode'] = 'FAST';
        //15.支付人姓 名
        $params['s1_PayerName'] = $bankInfo['user_name'];
        //16.支付人证 件类型
        $params['s2_PayerCardType'] = 1;
        //17.支付人证 件号
        $params['s3_PayerCardNo'] = $bankInfo['id_card_num'];
        //18.支付人银行卡号
        $params['s4_PayerBankCardNo'] = $bankInfo['bank_card_num'];
        //21.银行预留 手机号
        $params['s7_BankMobile'] = $bankInfo['phone'];
        return $params;
    }

    protected function getMoneyAttr($value)
    {
        $int = 1;
        $value = intval($value);
        switch($value)
        {
            case 200:
                $int = 2;
                break;
            case 1000:
                $int = 3;
                break;
        }
        return $int;
    }


    /**
     * 输出json数组
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected function outJson($code = 0, $msg = '', $data = [])
    {
        return [
            "status" => $code,
            "msg" =>  $msg,
            "data" => $data
        ];
    }

    /**
     * 生成签名
     * @param $params
     * @param $key
     * @param int $encryptType
     * @return string
     */
    protected function hmacRequest($params, $key, $encryptType = 1)
    {
        if ($encryptType == 1) {
            return md5(implode("", $params) . $key);
        } else {
            $private_key = openssl_pkey_get_private($key);
            $params = implode("", $params);
            openssl_sign($params, $sign, $private_key, OPENSSL_ALGO_MD5);
            openssl_free_key($private_key);
            $sign = base64_encode($sign);
            return $sign;
        }

    }

    /**
     * 模拟post请求
     * @param $url
     * @param $params
     * @param bool $contentType
     * @return bool|mixed|string
     */
    protected  function http_post($url, $params,$contentType=false)
    {

        if (function_exists('curl_init')) { // curl方式
            $oCurl = curl_init();
            if (stripos($url, 'https://') !== FALSE) {
                curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            }
            $string = $params;
            if (is_array($params)) {
                $aPOST = array();
                foreach ($params as $key => $val) {
                    $aPOST[] = $key . '=' . urlencode($val);
                }
                $string = join('&', $aPOST);
            }
            curl_setopt($oCurl, CURLOPT_URL, $url);
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($oCurl, CURLOPT_POST, TRUE);
            //$contentType json处理
            if($contentType){
                $headers = array(
                    "Content-type: application/json;charset='utf-8'",
                );

                curl_setopt($oCurl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($oCurl, CURLOPT_POSTFIELDS, json_encode($params));
            }else{
                curl_setopt($oCurl, CURLOPT_POSTFIELDS, $string);
            }
            $response = curl_exec($oCurl);
            curl_close($oCurl);
            return $response;
        } elseif (function_exists('stream_context_create')) { // php5.3以上
            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($params),
                )
            );
            $_opts = stream_context_get_params(stream_context_get_default());
            $context = stream_context_create(array_merge_recursive($_opts['options'], $opts));
            return file_get_contents($url, false, $context);
        } else {
            return FALSE;
        }
    }
}
