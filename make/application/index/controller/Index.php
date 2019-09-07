<?php
namespace app\index\controller;
use think\Cache;
use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch('demo');
    }

    /**
     * 快捷支付同步通知页面
     * @return string
     */
    public function showPay()
    {
        $data = $this->request->param();
        $order_sn = isset($data['r3_OrderNo']) ? '订单号:' . $data['r3_OrderNo'] : '未知订单号:';
        return $order_sn . '-支付成功 ^_^';
    }

    // 下载界面
    public function down()
    {
        $base = $this->request->domain();
        return $this->fetch('down',[
            //安卓下载地址
            'azUrl' => $base . "/index.php/index/downApk",
            //安卓下载二维码图片
            'azUrlImg' => '/qrcode/apk.png',
            //苹果下载地址
            'pgUrl' => 'https://www.cw.pub/shq4',
            //苹果二维码下载地址
            'pgUrlImg' => '/qrcode/ios.png',
        ]);
    }

    // 安卓文件下载
    public function downApk()
    {
        $file = './app/ddV1.0.apk';
        \cocolait\helper\CpDownload::downApp($file);
    }

    /**
     * H5端注册
     * @return mixed
     */
    public function register()
    {
        $invite_code = $this->request->param('code','');
        $base = $this->request->domain();
        $down_url = $base . "/index.php/index/down";
        return $this->fetch('register',[
            'invite_code' => $invite_code,
            'down_url' => $down_url
        ]);
    }
    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => 'wxc55d0202e187e851',
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return json($signPackage);
    }

    public function getJsApiTicket() {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $jsapi_ticket = Cache::get('jsapiTicket');
        if(isset($jsapi_ticket)){
            return $jsapi_ticket;
        }else {
            $accessToken = $this->getWXAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->curl_get($url));
            if ($res->errcode == 0) {
                Cache::set('jsapiTicket',$res->ticket,$res->expires_in);
                return $res->ticket;
            } else {
                return $ticket = '';
            }
        }
    }

    public function getWXAccessToken(){
        $accessToken = Cache::get('accessToken');
        if(isset($accessToken)){
            return $accessToken['accessToken'];
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxc55d0202e187e851&secret=a1161f1d4e7e37d87b76b9f0f038a57c";
            $access = $this->curl_get($url);
            $res = json_decode($access,true);
            if(!empty($res['access_token'])){
                Cache::set('accessToken',$res,$res['expires_in']);
                return $res['access_token'];
            }else{
                return json(0,'获取失败');
            }
        }
    }
    public function curl_get($url) {
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 20);
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}
