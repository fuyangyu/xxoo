<?php
namespace app\index\controller;
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
}
