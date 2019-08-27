<?php
namespace app\api\controller;
use think\Controller;
use think\Db;

class Index extends Controller
{
    /**
     * 发送短信
     * @return \think\response\Json
     */
    public function getCode()
    {
        try{
            $phone = trim($this->request->param('phone'));
            $scene = trim($this->request->param('scene'));
            if (!$phone || !$scene) return json($this->outJson(0,'请求参数缺失'));
            if (!cp_isMobile($phone)) return json($this->outJson(0,'手机号码格式错误'));

            $checkPhone = Db::name('member')->where(['phone' => trim($phone)])->find();

            $check_item = '';
            $randCode = \cocolait\helper\CpMsubstr::rand_string(4,1);
            $display_name = '';
            switch($scene)
            {
                case 'register':
                    if ($checkPhone) return json($this->outJson(0,'该手机号已被注册'));
                    $check_item = $phone . '_' . $randCode . '_' . $scene;
                    $display_name = '用户注册';
                    break;
                case 'find':
                    if (!$checkPhone) return json($this->outJson(0,'该手机号还未注册'));
                    $check_item = $phone . '_' . $randCode . '_' . $scene;
                    $display_name = '找回密码';
                    break;
                case 'bank':
                    $check_item = $phone . '_' . $randCode . '_' . $scene;
                    $display_name = '绑定银行卡';
                    break;
                case 'login':
                    if (!$checkPhone) return json($this->outJson(0,'该手机号还未注册'));
                    $check_item = $phone . '_' . $randCode . '_' . $scene;
                    $display_name = '验证码登陆';
                    break;
                case 'alipay':
                    if (!$checkPhone) return json($this->outJson(0,'该手机号还未注册'));
                    $check_item = $phone . '_' . $randCode . '_' . $scene;
                    $display_name = '添加支付宝账号';
                    break;
                case 'withdraw':
                    if (!$checkPhone) return json($this->outJson(0,'该手机号还未注册'));
                    $check_item = $phone . '_' . $randCode . '_' . $scene;
                    $display_name = '设置提现密码';
                    break;
            }
            if (!$check_item) return json($this->outJson(0,'短信场景不存在'));
            $url = "http://v.juhe.cn/sms/send";
            $params = array(
                'key' => 'c43b5e383b9977eaf7d14576cbb9374c', //您申请的APPKEY
                'mobile' => $phone, //接受短信的用户手机号码
                'tpl_id' => '112467', //您申请的短信模板ID，根据实际情况修改
                'tpl_value' => "#code#={$randCode}" //您设置的模板变量，根据实际情况修改
            );
            $paramstring = http_build_query($params);
            $content = self::juheCurl($url, $paramstring);
            $result = json_decode($content, true);
            if ($result) {
                if (isset($result['error_code']) && $result['error_code'] == 0) {
                    // 发送成功
                    Db::name('check_phone')->insert([
                        'phone' => $phone,
                        'sub_time' => time(),
                        'code' => $randCode,
                        'check_item' => $check_item,
                        'note_display_name' => $display_name
                    ]);
                    return json($this->outJson(1,'发送成功'));
                } else {
                    return json($this->outJson(0,'发送失败'));
                }
            } else {
                return json($this->outJson(0,'短信发送异常'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
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
     * 请求接口返回内容
     * @param  string $url [请求的URL地址]
     * @param  string $params [请求的参数]
     * @param  int $ipost [是否采用POST形式]
     * @return  string
     */
    protected static function juheCurl($url, $params = false, $ispost = 0)
    {
        $httpInfo = array();
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'JuheData');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url.'?'.$params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        $response = curl_exec($ch);
        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }

    /**
     * 规则
     * @return \think\response\Json
     */
    public function rule(){
        $data = array();
        //推荐佣金规则
        $data['recommend'] = Db::name('allot_log')->where('charge_type',1)->select();
        //渠道佣金规则
        $data['channel'] = Db::name('allot_log')->where('charge_type',2)->select();
        $fileData = cp_getCacheFile('system');
        $data['service_mobile'] = isset($fileData['service_mobile']) ? $fileData['service_mobile'] : '';

        return json($this->outJson(1,'获取成功',$data));
    }
}
