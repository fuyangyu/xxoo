<?php
namespace wx;
class WxPay
{
    // 对象实例
    protected static $instance;

    // 官方文档 https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_1
    // 通用参数
    protected $wxConfig = [
        'appid' => 'wxc55d0202e187e851',// 应用ID
        'mch_id' => '1520276391',//'1520276391',// 商户号
        'notify_url' => 'http://www.diandonglife.com/index.php/pay/wxNotify',//异步通知地址
        // 注：key为商户平台设置的密钥key
        // key设置路径：微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置
        'key' => '791F7ACCCE92EB5EBF79C443BF6FF4C3'
    ];

    private function __construct($options)
    {

    }

    /**
     * 外部调用获取实列
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    // 传输给微信的参数要组装成xml格式发送,传如参数数组
    public function ToXml($data = [])
    {
        if(!is_array($data) || count($data) <= 0)
        {
            return '数组异常';
        }

        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }


    // 生成随机字符串,微信支付创建订单所需必须参数
    protected function rand_code(){
        //62个字符
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = str_shuffle($str);
        $str = substr($str,0,32);
        return  $str;
    }

    //这里是微信比较重要的一步了,这个方法会多次用到!生成签名
    public function getSign($params) {
        //将参数数组按照参数名ASCII码从小到大排序
        ksort($params);
        $newArr = [];
        foreach ($params as $key => $item) {
            //剔除参数值为空的参数
            if (!empty($item)) {
                // 整合新的参数数组
                $newArr[] = $key.'='.$item;
            }
        }
        //使用 & 符号连接参数
        $stringA = implode("&", $newArr);
        //拼接key 注意：key是在商户平台API安全里自己设置的
        $stringSignTemp = $stringA."&key=".$this->wxConfig['key'];
        //将字符串进行MD5加密
        $stringSignTemp = md5($stringSignTemp);
        //将所有字符转换为大写
        $sign = strtoupper($stringSignTemp);
        return $sign;
    }

    // 传递参数给微信,生成预支付订单! 接收微信返回的数据,在返给APP端,APP端调用支付接口,完成支付
    // APP端所需参数见微信文档:https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_12&index=2
    /**
     * 微信支付 生成预支付订单
     * @param string $subject 商品名称
     * @param string $order_sn 订单号
     * @param int $total_amount 商品金额
     * @return array
     */
    public function wxPay($subject, $order_sn, $total_amount) {

        $data['appid'] = $this->wxConfig['appid'];  //公众账号ID
        $data['mch_id'] = $this->wxConfig['mch_id'];    //商户号
        $data['body'] = $subject;       //商品描述
        $data['trade_type'] = 'MWEB';  //支付方式
        $data['nonce_str'] = $this->rand_code();    //随机字符串
        $data['out_trade_no'] = $order_sn;      //商户订单号
        $data['total_fee'] = 100 * $total_amount;   //标价金额
        $data['spbill_create_ip'] = $this->getClientIp();   //终端IP
        $data['scene_info'] = '{"h5_info": {"type":"Wap","wap_url": "http://www.diandonglife.com","wap_name": "点动充值"}}';
        $data['notify_url'] = $this->wxConfig['notify_url'];    //通知地址
//        $data['key'] = $this->wxConfig['key'];



/*        $signA ="appid=$appid&body=$body&mch_id=$mch_id&nonce_str=$nonce_str
                &notify_url=$notify_url&out_trade_no=$out_trade_no&spbill_create_ip=$spbill_create_ip
                &total_fee=$total_fee&trade_type=$trade_type";
        //3.拼接字符串
        $strSignTmp = $signA."&key=$key";
        //4.MD5加密后转换成大写
        $sign = strtoupper(MD5($strSignTmp));
        //5.拼接成所需XML格式
        $post_data = "<xml>
                       <appid>$appid</appid>
                       <body>$body</body>
                       <mch_id>$mch_id</mch_id>
                       <nonce_str>$nonce_str</nonce_str>
                       <notify_url>$notify_url</notify_url>
                       <out_trade_no>$out_trade_no</out_trade_no>
                       <spbill_create_ip>$spbill_create_ip</spbill_create_ip>
                       <total_fee>$total_fee</total_fee>
                       <trade_type>$trade_type</trade_type>
                       <sign>$sign</sign>
                   </xml>";*/

        $data['sign'] = $this->getSign($data);              //获取签名
        $xml = $this->ToXml($data);
        //curl 传递给微信方
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        header("Content-type:text/xml");
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        } else {
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        }
        //设置header
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        //传输文件
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            //返回成功,将xml数据转换为数组.
            $res = $this->FromXml($data);
            if($res['return_code'] != 'SUCCESS'){
                return $this->outJson(0,"微信预支付订单,签名失败！",$res);
            } else{
                //接收微信返回的数据,传给APP
//                $result = [
//                    'prepayid' => $res['prepay_id'],
//                    'appid' => $this->wxConfig['appid'],
//                    'partnerid' => $this->wxConfig['mch_id'],
//                    'mweb_url'  => $res['mweb_url'],
//                    'package'  => 'Sign=WXPay',
//                    'noncestr' => $data['nonce_str'],
//                    'timestamp' => time(),
//                ];
                //第二次生成签名
//                $sign = $this->getSign($result);
//                $result['sign'] = $sign;
                return $this->outJson(1,'微信预支付订单创建成功',$res);
            }
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return $this->outJson(0,"调用微信支付出错,curl错误码:$error");
        }
    }

    // 将xml数据转换为数组,接收微信返回数据时用到
    public function FromXml($xml)
    {
        if(!$xml){
            echo "xml数据异常！";
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    // 获取实际ip
    protected function getClientIp($type = 0,$adv=true) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
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

    private function __clone() {}
}