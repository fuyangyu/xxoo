<?php
namespace app\api\data;
use think\Response;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/5
 * Time: 22:45
 */
class Send
{
    protected static function outCheck()
    {
        //不参与验证的方法
        return [
            //登陆注册
            'api_login_login', 'api_login_register', 'api_login_findps', 'api_login_loginout', 'api_login_registration', 'api_login_logination',
            'api_login_findation', 'api_login_getarea', 'api_login_verifier',
            //首页
            'api_home_play','api_home_notice','api_home_noticeidfind','api_home_showtask','api_home_showtaskmore','api_home_official',
            'api_home_showcharge','api_home_qrcode','api_home_noticelist',
            //短信发送
            'api_index_getcode','api_index_rule',
            //用户信息
//            'api_member_index', 'api_member_userinfo', 'api_member_userinfoactio', 'api_member_nicknameactio', 'api_member_setnickname',
            'api_member_inviteactio', 'api_member_setinvite', 'api_member_upface',

            //我的
//            'api_member_user','api_member_userdirectrecord','api_member_userearnings','api_member_userearningslog','api_member_userearningspool',
//            'api_member_userteamnum','api_member_userteamrecord',
            //我的钱包
//            'api_pay_addalipay','api_pay_bankverify','api_pay_wallet','api_pay_withdraw','api_pay_withdrawlog',

            //会员充值
//            'api_pay_membercenter','api_pay_privilegetaskmore','api_pay_chargepay','api_pay_getbank',

            //邀请好友
//            'api_invit_index','api_invit_invitationregistershow',

            //任务
            'api_task_findtask', 'api_task_uptaskfile', 'api_task_subtask', 'api_task_task','api_task_taskmore','api_task_draw','api_task_tasknotice',
            'api_task_tasknoticemore','api_task_tasklist',

            //消息
            'api_home_messagestatus',
        ];
    }

    // 签名验证自动识别
    public static function record($params)
    {
        $key = 'MDAwMDAwMDAwML2icpiHp8tts';
        $param = $params->param();
        $action = request()->module() . "_" . strtolower(request()->controller()) . "_" . request()->action();
        if (in_array($action, self::outCheck())) {
            // 不进行验证和token验证
        } else {
            $token = isset($param['token']) ? $param['token'] : '';
            $check_token_info = \auth\Token::instance()->checkAccessToken($token);
            if ($check_token_info['status'] != 1) {
                Response::create($check_token_info,'json',200)->send();
                exit;
            }else{
                return json($check_token_info);
            }
            /*if (!$sign) {
                Response::create(['msg' => '缺少参数sign', 'status' => 0],'json',500)->send();
                exit;
            }
            if (isset($param['sign']) && empty(!$param['sign'])) {
                unset($param['sign']);
            }
            $encode = json_encode($param);
            $check_key = md5($encode . $key);
            if ($check_key != $sign) {
                Response::create(['msg' => '签名验证失败!', 'status' => 0],'json',500)->send();
                exit;
            }*/
        }
    }


    /**
     * 加密算法
     * @param $data
     * @param string $key
     * @param int $expire
     * @return mixed
     */
    protected static function  encrypt($data, $key = '', $expire = 0) {
        $key  = md5(empty($key) ? '' : $key);
        $data = base64_encode($data);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }
        $str = sprintf('%010d', $expire ? $expire + time():0);
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
        }
        return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
    }
}