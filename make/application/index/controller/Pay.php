<?php
namespace app\index\controller;
use think\Db;
use think\Log;
use think\Controller;
class Pay extends Controller
{
    protected $config;

    protected $fastPayConfig;

    /**
     * 初始化应用参数
     * Pay constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // 支付宝配置
        $this->config = [
//            'app_id' => '2018111362152250',
//            'rsaPrivateKey' => 'MIIEogIBAAKCAQEAx8nqYbhfom1FNKCkXZaKPCqiI+C5SUe2nFrK9R92n6Hk4YVDbO7zKp89F4rEhls1cgiF2D5a5hl6sQySpaRd8MRk3QVmcurAoJoTqceCRjDeosn5dL6DzOGnfBb2Sa1WdpGxnXdp05ET9NzLvDChHvsrbMLiCErgaUCUk+EU0OZwUg/B39GFSDRjJkgsmZmpG3cJwX4qzbl7jXa/WZDnNjnhivQmuhlwfG8o7drLthGtBRJn1opZ8tO+Np2xbpRnr6zPjGpXSkCcK/RWg0WUNuRmWH8cB8/CEbv5kzjJVbBxjhZMyzm6SKy445neGTgxee6smlwN0mH/XY+cTLl7zwIDAQABAoIBADxyBouSMLz/ulR11cTK6v/RFkUslGJrZABiJ82Ju2YIoSrqGsA2ezOGAgHBZjwQFFdv5K7MDsxXIRu35hZfaFyTzsBgBeL2y1jLxO81AWEjH/i4itSbX1z5WKbee51G6EXvRoGRw17TIeqPcsR5IgYxj63UOHax0LuHYmxrSNT6mVmbRzGqPzOuoHLUDk/tCORp5uqxTFhXcnG1TdPJhd29mRMC0RDTGodhuzn67nm8WeHzBdydOCAvV9iXnP7l0396N16AvkLTS2m0BCqCUs+WlKZps4pzrGcbW2E/f3JAXw90PaWG/5OdQfLrgQ/KYgnIYQ/nhJyTwofFkC3PmuECgYEA/5e0KrSsqygI5LhnHPfGj6QlXR9RdgfC7JJq6eth42WTlSm5xaHCHCqQpdrdKYMU291Na31tjtoIEyQHYsDf96VIy7zqMnUYYD7PeboRp4JYHhtpETyKyR2WbvSff7TbHkwVpXF3IkGCDFgFws1HeVUXTvVD/l4l+4Cz6gNRLX0CgYEAyBtwzk/yl1MrsuGA/P0jzwc3FhVJYY2sdwC69wlh92DY0lY6sCq5aPIDqYBLJUNs8P12E3AN6QhGvF/NVui/0fDocgKGBX1/Ob6Rf2F4ISap3wbUU1oglAFRqkUCvGkck8N7wlZyUxnZ4eQuXi9WLSraOJzFk/i/nao+58xUADsCgYBU/vv5H7A0EtIyTWhs57DuX6XVO+75E0etKFvJgm9BEaxsdD3FDS/h8f28SfY2MdMj8oombsWaNcNtqhOSsZVJY3u71Q0Ezo/WocyZLFlmnR/0kyTziHCWxPIb39mSvHAJuT+RjxQRHjvDIxp3V5CnNrbTmRiNZH25D2nFLmk2eQKBgDivdUwsX5c4a0eYE9cWyn8KFzO9QfVNkc6AOXZjhQnzuFgVLzjaUX2GGT1550+eAw1db4ZgFsCtpIQWS2/ULQqGyQIK7vY9L74m2saP0NzrO/G+2IToIhRrLzwhon3G9N5y4OsTW/1odE1GO1BY7nuLCRhCaMHbFETQhlNZkW4jAoGAP/JNM8D5NtNgM47DCvzMvyXIM7rgGJyHNNEmug2YorEwja5YHOwnYlc5fnNuzH1t9ZMilZm55X6r6han1LyG3q1lCqNVf/eKs/FdluBYyKkjMqiRM1FIBY6LZYHrmjvXFVws7kYxQbDdEPhvlmC2V9ZX0OLEnfmPJMfrWjFz2Vc=',
//            'alipayrsaPublicKey' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAo1kn0uJT5EIkFYOxJ7YJQPvg1AfCMiWjgPiAEEe14ctqBHvBD3qXdwORXizuep7/oaY3pOIN65f7+Rgu3MeQtDQEySkmP7EJqPUV9rpSQxx9nBW2SBiXxYODpMz2oDll/cRs5Bbbr5IzwWWxp2U9zM4rhrbt2iNelC99dEVG1Qj2084gUWA0d7Bsk4hkq8maQZoHB20P7YcqBgcMxIjACHmhwh/DZhud+4HiI57epmckofy2X9+2LT1R0lgQD7cmBQ9IOJdGd60C/TtM2gVH3BJl6lqoJS34U1LXoL0wGH0ok+tJKZoHHsyVFtkHpbrQ+LC54bh314ggQHHdjmn5bwIDAQAB',
//            'notify_url' => 'http://www.diandonglife.com/index.php/pay/callback'
            //应用ID,您的APPID。
            'app_id' => "2018111362152250",
            //商户私钥，您的原始格式RSA私钥
            'merchant_private_key' => "MIIEpQIBAAKCAQEAtUH45lBssBil2JLlJsN6hB5A2sW0gAwlxW9XjwWD1jbW1VUXv+NjRcazeZY7J648rmgCF1pX8ScVjVdYgf3dyChn/tFCSADo0HczK3I1ShFKebQGg5Guk0dB2/vAVjfWPP/pt3kma6NXyp9R9tcTtpGa8ezxKwbfi4VO2MmF9UMA8b+ltXa9EuA+I0l5Yo7ClKpk8TMyqeAUC8YKvJ5M5QaTBd4ZekIOJuFiR+mhoP4PYMIGLKvx7TNgtZF6b7eS9NWf5V75l24NLbZHcDzfXicUZTENmULkEG9K6l9H9ZF1Q0fITHpAPV5srnSV6xsvJ+AbGiOHMC4o4A5/Bm/nMwIDAQABAoIBAEOejf1V6YY0W8KU4nn4mP8qziUPdowCfCDQrciEVS+YG8NQUGDcso84VoI4gm8GOEsUMBuIL6CeZRLqj/FGxPND57APXvu/oxsKLQO7QpgUJUWL3JY+xfLZtX8cxx8jC4CMNCOnRacIM9s6XniIuij03un76+iSUtkY7VZAsAHTz9rmLnC5LPCQoMvtUnhuPPDvenAgrQ506cu0bzmFApKfemecaUh19zKtUitFdAdMpNvjDPhho16zDGu8kdauGGQSKDbU3uYx/KQSLi9f0/vHTdti9P5SXo7ONCyRtfWHVW9TKQV9hzNMZqGy7b/ahY8PczeDJ4I9dBgdcadTmgECgYEA+TA16Dw/lj+wIXYHOfamdrxVJ4JTVEAWL95FQYQSBUHGoPcRg2aCwRfITj55Bsak+ya2etgmZbowhI1+1J6esOvCxQh3HftcaY3kkkgcT4ARm1TU/1N4G0iwA8FA6IiUv1hFjfJ/TcnGocvdQoiW1hBcX7ins8cJD+Lb780w1QECgYEAujZkOvIgIUSHNEL6qlSqU4oMjvPEtKeGT28UQdWQtT/nDGFiVHVHql4oSJADpJKIR5SQ3sG8aBpbzCjQ/WxTv7iRjw37OJxCMaDbjtafXqthoXPy95GuNfe6DaL1MjXdrPVmY0O0AsROTIMk5XmyShoh1ngWEhL95Ds4gmDdeDMCgYEAxMoaCGlsHg/13LcFRfVPyP06kpUNkb96xhrWvsK6KISlhIEZx5exMyTA/2m+0mcV63HLMoB48mVz43qK6wbJdBb8HfZte7QCaymvlQZ1tSMCxJTeETWt6H4i4xQ/WmRidCoV49/aQWhUAXqqJd0QocUR7lY5unQ4597UqjB1nAECgYEAiyeAaWhtSE8ctppjFgylKD6euelDEzmprgy1V6lQNZJmiCLyR2lJP/CTK/6rKj3yp4NHa5/duvIPrZbG7ssYHsq/w+bP2PM0qD+sM6cBe86Y6/1pEUcFqADTQcOIdpg4azsL45xBllu6o4TReschzCyRIuOkoqccooT66ruWZW8CgYEA6VSJ2yoL4fFUKelWoNriIPdQevtL9pVEvbmNVZLK7Cyywlhk4FTZCs4YRjJEo2QNNXO3jXPPMA8SOUF7G6VVQRRjxfTJAcO6VtEFFFZgKSVYlxKqGCTnh+5c3BgE7N1R2flEwxhyoGjFfNHehXZ2nKUzADu7yKE/LUl3ED31J9o=",
            //异步通知地址
            'notify_url' => "http://www.diandonglife.com/index.php/pay/callback",
            //同步跳转
            'return_url' => "http://www.diandonglife.com/index.php/pay/return_url",
            //编码格式
            'charset' => "UTF-8",
            //参数返回格式，只支持 json
            'FORMAT'  => 'json',
            //签名方式
            'sign_type'=>"RSA2",
            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAo1kn0uJT5EIkFYOxJ7YJQPvg1AfCMiWjgPiAEEe14ctqBHvBD3qXdwORXizuep7/oaY3pOIN65f7+Rgu3MeQtDQEySkmP7EJqPUV9rpSQxx9nBW2SBiXxYODpMz2oDll/cRs5Bbbr5IzwWWxp2U9zM4rhrbt2iNelC99dEVG1Qj2084gUWA0d7Bsk4hkq8maQZoHB20P7YcqBgcMxIjACHmhwh/DZhud+4HiI57epmckofy2X9+2LT1R0lgQD7cmBQ9IOJdGd60C/TtM2gVH3BJl6lqoJS34U1LXoL0wGH0ok+tJKZoHHsyVFtkHpbrQ+LC54bh314ggQHHdjmn5bwIDAQAB"
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
     * 支付宝同步回调
     * @return mixed
     */
    public function return_url()
    {
        require_once ROOT_PATH . '/extend/alipay/wappay/service/AlipayTradeService.php';
        $arr = $_GET;
        $alipaySevice = new \AlipayTradeService($this->config);
        $result = $alipaySevice->check($arr);
        if ($result) {//验证成功
            $out_trade_no = htmlspecialchars($_GET['out_trade_no']);
            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);
            $total_amount = htmlspecialchars($_GET['total_amount']);
            $timestamp = htmlspecialchars($_GET['timestamp']);
            return $this->fetch('paysuccess', [
                'out_trade_no' => $out_trade_no,
                'trade_no' => $trade_no,
                'total_amount' => $total_amount,
                'timestamp' => $timestamp,
            ]);
        } else {
            //验证失败
            echo "验证失败";
        }

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
        require_once ROOT_PATH . '/extend/alipay/wappay/service/AlipayTradeService.php';
        require_once ROOT_PATH . '/extend/alipay/wappay/buildermodel/AlipayTradeWapPayContentBuilder.php';
            //超时时间
            $timeout_express="1m";
            $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
            $payRequestBuilder->setSubject($subject);
            $payRequestBuilder->setOutTradeNo($order_sn);
            $payRequestBuilder->setTotalAmount($total_amount);
            $payRequestBuilder->setTimeExpress($timeout_express);
            $payResponse = new \AlipayTradeService($this->config);
            $result=$payResponse->wapPay($payRequestBuilder,$this->config['return_url'],$this->config['notify_url']);

            return $result;
    }


    /**
     * 支付宝支付 回调地址
     */
    public function callback()
    {
        $arr=$_POST;
        require_once ROOT_PATH . '/extend/alipay/wappay/service/AlipayTradeService.php';
        $alipaySevice = new \AlipayTradeService($this->config);
        $result = $alipaySevice->check($arr);
        //$log = ROOT_PATH . '/cache_data/pay_log.log';
        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if($result) {//验证成功
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            //交易状态
            //$trade_status = $_POST['trade_status'];
            if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //佣金发放
                $res = $this->setOrderStatus($out_trade_no,$trade_no);
                if($res['status'] != 1){
                    $msg = '分佣失败,时间：' . date('Y-m-d H:i:s') . '具体的信息：' . $res['data']['debug'] . "\r\n";
                    $file['time'] = date('Y-m-d H:i:s');
                    $file['msg'] = $msg;
                    $this->log($file,'zfb_pay.log');
                    return 'error';
                }
                echo "success";		//请不要修改或删除
            }
        }else {
            $file['time'] = date('Y-m-d H:i:s');
            $file['msg'] = '支付失败,时间：' . date('Y-m-d H:i:s') . '信息：签名验证失败';
            $this->log($file,'zfb_pay.log');
            return 'error';
        }
            //验证失败
            echo "fail";	//请不要修改或删除


//            if ($flag) {
//            // 验证成功
//            // TODO 包括用户余额额度的增加 佣金状态 支付状态
//            $out_trade_no = trim($_POST['out_trade_no']);
//            // TODO 上线后需要将状态设置为false
//            $data = $this->setOrderStatus($out_trade_no);
//            if ($data['status'] == 1) {
//                return 'success';
//            } else {
//                $msg = '支付失败,时间：' . date('Y-m-d H:i:s') . '具体的信息：' . $data['data']['debug'] . "\r\n";
//                $file['time'] = date('Y-m-d H:i:s');
//                $file['msg'] = $msg;
//                $this->log($file,'zfb_pay.log');
//                return 'error';
//            }
//        } else {
//            $file['time'] = date('Y-m-d H:i:s');
//            $file['msg'] = '支付失败,时间：' . date('Y-m-d H:i:s') . '信息：签名验证失败';
//            $this->log($file,'zfb_pay.log');
//            return 'error';
//        }
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
                $res = $this->setOrderStatus($data['out_trade_no'],$data['transaction_id']);
                if ($res['status'] != 1) {
                    // 分佣失败
                    $msg = '分佣失败,时间：' . date('Y-m-d H:i:s') . '具体的信息：' . $data['data']['debug'] . "\r\n";
                    $file['time'] = date('Y-m-d H:i:s');
                    $file['msg'] = $msg;
                    $this->log($file, 'wx_log.log');
                }
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
     * 修改订单状态 用户会员等级和到期时间 佣金发放
     * @param $out_trade_no
     * @param $trade_no
     * @return array
     */
    public function setOrderStatus($out_trade_no = '',$trade_no = '')
    {
            try {
            // 启动事务
            Db::startTrans();
            $pay_log = Db::name('pay_log')->where(['order_sn' => $out_trade_no, 'pay_status' => 1])->find();

            if (!$pay_log) return $this->outJson(0, '操作失败', ['debug' => '操作失败，错误编码001,未找到订单']);
            //获取用户信息
            $user = Db::name('member')->where(['uid' => $pay_log['uid']])->field('uid,phone,vip_end_time,total_money,member_brokerage_money,member_class,parent_level_1,parent_level_2,parent_level_3,invite_uid')->find();
            if (!$user) return $this->outJson(0, '操作失败', ['debug' => '操作失败，错误编码002,用户信息获取失败']);
            // 修改支付状态
            $pay_log_id = Db::name('pay_log')->where(['id' => $pay_log['id']])->update([
                'pay_status' => 2,
                'pay_time' => time(),
                'trade_no' =>$trade_no,
            ]);
            $member = array();
            $brokerage = array();
            $message = array();
            $content = '';
            //计算出会员升级等级和修改会员过期时间
            if ($pay_log['type'] == 1 && $user['member_class'] == 1) {   //type:1 充值 2 续费 3 升级 ; member_class:会员原等级 ：普通用户
                switch ($pay_log['vip']) {
                    case 2:
                        $member['member_class'] = 2;  //vip
                        $member['vip_start_time'] = date('Y-m_d H:i:s');
                        $member['vip_end_time'] = date('Y-m_d H:i:s', strtotime("+1 year"));
                        $content = '加入点动生活VIP会员';
                        break;
                    case 3:
                        $member['member_class'] = 3;  //svip
                        $member['vip_start_time'] = date('Y-m_d H:i:s');
                        $member['vip_end_time'] = date('Y-m_d H:i:s', strtotime("+1 year"));
                        $content = '加入点动生活SVIP会员';
                        break;
                }
            }
            if ($pay_log['type'] == 2 && $user['vip_end_time']) { //续费 会员到期时间增加一年
                $member['vip_end_time'] = date('Y-m_d H:i:s', strtotime("+1 year 1months", strtotime($user['vip_end_time'])));
                $content = '续费点动生活会员';
            }
            if ($pay_log['type'] == 3 && $user['member_class'] == 2) {    //升级只能vip升级svip 只修改会员等级 不休改到期时间
                $member['member_class'] = 3;  //svip
                $content = '升级为点动生活SVIP会员';
            }
            //修改会员状态和过期时间
            $member_id = Db::name('member')->where(['uid' => $pay_log['uid']])->update($member);
            if ($pay_log_id && $member_id) {
                $phone = substr_replace($user['phone'],'****',3,4);
                //直推分佣
                $oneData = Db::name('member')->where('uid',$user['invite_uid'])->field('uid,member_class,phone')->find();
                if($oneData) {
                    $allot_one = Db::name('allot_log')->where(['user_level' => $oneData['member_class'], 'charge_type' => 1])->value('allot_one');
                    if (!empty($allot_one)) {
                        $one_money = $pay_log['money'] * ($allot_one / 100);
                        //获取分佣用户信息
                        $brokerage[0]['uid'] = $oneData['uid'];
                        $brokerage[0]['money'] = $one_money;
                        $brokerage[0]['member_class'] = $oneData['member_class'];
                        $brokerage[0]['phone'] = $oneData['phone'];
                        $brokerage[0]['tid'] = $pay_log['id'];
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
                            $two_money = $pay_log['money'] * ($allot_two / 100);
                            //获取分佣用户信息
                            $brokerage[1]['uid'] = $twoData['uid'];
                            $brokerage[1]['money'] = $two_money;
                            $brokerage[1]['member_class'] = $twoData['member_class'];
                            $brokerage[1]['phone'] = $twoData['phone'];
                            $brokerage[1]['tid'] = $pay_log['id'];
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
                                            $serve_one_money = $pay_log['money'] * ($team_one / 100);   //第一个服务中心分佣金额
                                            $brokerage[2]['uid'] = $one_serve['uid'];
                                            $brokerage[2]['money'] = $serve_one_money;
                                            $brokerage[2]['member_class'] = $one_serve['member_class'];
                                            $brokerage[2]['phone'] = $one_serve['phone'];
                                            $brokerage[2]['tid'] = $pay_log['id'];
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
                                                $serve_two_money = $pay_log['money'] * ($team_two / 100);   //第二个服务中心分佣金额
                                                $brokerage[3]['uid'] = $two_serve['uid'];
                                                $brokerage[3]['money'] = $serve_two_money;
                                                $brokerage[3]['member_class'] = $two_serve['member_class'];
                                                $brokerage[3]['phone'] = $two_serve['phone'];
                                                $brokerage[3]['tid'] = $pay_log['id'];
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
                return json($this->outJson(0,'操作失败',['debug' => '操作失败，错误编码008,订单号：' . $out_trade_no . '错误体debug：修改订单状态或会员状态失败' ]));
            }
        }catch (\Exception $e){
            // 回滚事务
            Db::rollback();
            $this->log('操作失败，错误编码007,订单号：' . $out_trade_no . '错误体debug：' . $e->getMessage(),'brokerage.log');
            return json($this->outJson(0,'操作失败',['debug' => '操作失败，错误编码007,订单号：' . $out_trade_no . '错误体debug：' . $e->getMessage()]));
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
    protected function  outJson($code = 0, $msg = '', $data = [])
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

    /**
     * 静态佣金发放脚本
     * @throws \think\Exception
     */
    public function staticBrokerage(){
        set_time_limit(0);
        ini_set("memory_limit","500M");
        $channel_time = $this->request->param('channel_time');
        if(!$channel_time) return json($this->outJson(0,'参数错误'));
        $sql = "select * from wld_channel_log WHERE channel_time = from_unixtime({$channel_time},'%Y-%m') limit 1;";
        $channelData = Db::query($sql);
        if($channelData) return json($this->outJson(0,'发放失败,已存在该月份发放记录'));
        $vip_messageLog = $svip_messageLog = $serve_messageLog = array();
        //获取各等级用户数量
        $data = cp_getCacheFile('system');
        $vip_num = isset($data['vip_num']) ? $data['vip_num'] : '1000';
        $svip_num = isset($data['svip_num']) ? $data['svip_num'] : '500';
        $serve_num = isset($data['serve_num']) ? $data['serve_num'] : '100';
        //本月会员收入
        $currentMemberSql = "SELECT SUM(money) as member_money FROM wld_pay_log WHERE pay_status = 2 and from_unixtime(pay_time,'%Y-%m') = from_unixtime({$channel_time},'%Y-%m');";
        $member = Db::query($currentMemberSql);
        $member_money = !empty($member[0]['member_money'])?$member[0]['member_money']:0;
        //本月任务收入
        $currentTaskSql = "SELECT SUM(task_money) as task_money FROM wld_send_task_log WHERE is_check = 1 AND date_format(check_time, '%Y-%m') = from_unixtime({$channel_time},'%Y-%m');";
        $task = Db::query($currentTaskSql);
        $task_money = !empty($task[0]['task_money'])?$task[0]['task_money']:0;
        //计算静态各等级静态收益 36+1.5
        $vip_money = sprintf("%.2f",(($member_money*0.03 + $task_money*0.05)*0.8/0.06*1)/$vip_num);
        $svip_money = sprintf("%.2f",(($member_money*0.03 + $task_money*0.05)*0.8/0.06*2)/$svip_num);
        $serve_money = sprintf("%.2f",(($member_money*0.03 + $task_money*0.05)*0.8/0.06*3)/$serve_num);
        //等级为VIP的会员
        if($vip_money > 0) {
            //获取VIP会员用户
            $vipData = Db::name('member')->where('member_class', 2)->field('uid,phone')->select();
            if ($vipData) {
                foreach ($vipData as $k => $item) {
                    Db::name('member')->where('uid', $item['uid'])->setInc('static_money', $vip_money);
                    $vip_channel = [
                        'uid' => $item['uid'], 'phone' => $item['phone'], 'member_class' => 2, 'channel_money' => $vip_money,
                        'channel_time' => date('Y-m',$channel_time), 'add_time' => date('Y-m-d H:i:s'),
                    ];
                    //静态佣金记录
                    $vip_id = Db::name('channel_log')->insertGetId($vip_channel);
                    //静态分佣记录数据拼装
                    $vip_messageLog[$k] = [
                        'uid' => $item['uid'], 'did' => $vip_id, 'content' => date('Y年m月') . '会员静态收益', 'type' => 2,
                        'status' => 1, 'add_time' => date('Y-m-d H:i:s'),
                    ];
                }
                Db::name('message_log')->insertAll($vip_messageLog);
            }
        }
        if($svip_money > 0) {
            //获取SVIP会员用户
            $svipData = Db::name('member')->where('member_class', 3)->field('uid,phone')->select();
            if ($svipData) {
                foreach ($svipData as $k => $v) {
                    Db::name('member')->where('uid', $v['uid'])->setInc('static_money', $svip_money);
                    $svip_channel = [
                        'uid' => $v['uid'], 'phone' => $v['phone'], 'member_class' => 3, 'channel_money' => $svip_money,
                        'channel_time' => date('Y-m',$channel_time), 'add_time' => date('Y-m-d H:i:s'),
                    ];
                    //静态佣金记录
                    $svip_id = Db::name('channel_log')->insertGetId($svip_channel);
                    //静态分佣记录数据拼装
                    $svip_messageLog[$k] = [
                        'uid' => $v['uid'], 'did' => $svip_id, 'content' => date('Y年m月') . '会员静态收益', 'type' => 2,
                        'status' => 1, 'add_time' => date('Y-m-d H:i:s'),
                    ];
                }
                Db::name('message_log')->insertAll($svip_messageLog);
            }
        }
        if($serve_money > 0) {
            //获取服务中心会员用户
            $serveData = Db::name('member')->where('member_class', 4)->field('uid,phone')->select();
            if ($serveData) {
                foreach ($serveData as $k => $value) {
                    Db::name('member')->where('uid', $value['uid'])->setInc('static_money', $serve_money);
                    $serve_channel = [
                        'uid' => $value['uid'], 'phone' => $value['phone'], 'member_class' => 4, 'channel_money' => $serve_money,
                        'channel_time' => date('Y-m',$channel_time), 'add_time' => date('Y-m-d H:i:s'),
                    ];
                    //静态佣金记录
                    $serve_id = Db::name('channel_log')->insertGetId($serve_channel);
                    //静态分佣记录数据拼装
                    $serve_messageLog[$k] = [
                        'uid' => $value['uid'], 'did' => $serve_id, 'content' => date('Y年m月') . '会员静态收益', 'type' => 2,
                        'status' => 1, 'add_time' => date('Y-m-d H:i:s'),
                    ];
                }
                Db::name('message_log')->insertAll($serve_messageLog);
            }
        }
        return json($this->outJson(1,'静态佣金发放成功'));
    }
}
