<?php
/**
 * 登录
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/7
 * Time: 13:33
 */
namespace app\api\controller;
use app\api\controller;
use think\Db;
class Login extends Base
{
    /**
     * 登录
     * @return \think\response\Json
     */
    public function send()
    {
        try{
            if ($this->request->isPost()) {
                $validate = new \app\api\validate\Member();
                $data = $this->request->param();
                if (!$vdata = $validate->scene('login')->check($data)) {
                    return json($this->outJson(0,$validate->getError()));
                }
                $model = new \app\api\model\Member();
                $result = $model->sendLogin($data);
                return json($result);
            } else {
                return json($this->outJson(500,'非法请求'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 用户注册
     * @return \think\response\Json
     */
    public function register()
    {
        try{
            if ($this->request->isPost()) {
                $validate = new \app\api\validate\Member();
                $data = $this->request->param();
                if (!$vdata = $validate->scene('register')->check($data)) {
                    return json($this->outJson(0,$validate->getError()));
                }
                // 验证邀请码
                $infinity_code = config('code.invite_code') ? config('code.invite_code') : '';
                if ($data['invite_code'] == $infinity_code) {
                    // TODO 定义了万能验证码 不进行验证 测试阶段用
                } else {
                    $invite_code = strtolower($data['invite_code']);
                    $check = Db::name('member')->where(['invite_code' => $invite_code])->value('uid');
                    if (!$check) return json($this->outJson(0,'邀请码错误'));
                }

                // 验证该号码是否已经被注册
                $checkPhone = Db::name('member')->where(['phone' => trim($data['phone'])])->value('uid');
                if ($checkPhone) return json($this->outJson(0,'该手机号码已被注册'));

                // 验证短信验证码
                $bool = $this->checkPhoneCode(trim($data['phone']), trim($data['code']), 'register');
                if (!$bool) return json($this->outJson(0,'短信验证码错误'));


                // 验证通过后 写库
                $model = new \app\api\model\Member();
                $result = $model->sendReg($data);
                if ($this->debug) {
                    return json($result);
                } else {
                    if (isset($result['data']['debug'])) {
                        unset($result['data']['debug']);
                    }
                    return json($result);
                }
            } else {
                return json($this->outJson(500,'非法请求'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 找回密码
     * @return \think\response\Json
     */
    public function findPs()
    {
        try{
            if ($this->request->isPost()) {
                $validate = new \app\api\validate\Member();
                $data = $this->request->param();
                if (!$vdata = $validate->scene('find')->check($data)) {
                    return json($this->outJson(0,$validate->getError()));
                }
                // 验证短信验证码
                $bool = $this->checkPhoneCode(trim($data['phone']), trim($data['code']), 'find');
                if (!$bool) return json($this->outJson(0,'短信验证码错误'));

                $model = new \app\api\model\Member();
                $result = $model->sendFind($data);
                return json($result);
            } else {
                return json($this->outJson(500,'非法请求'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }


    /**
     * 退出登录
     * @return \think\response\Json
     */
    public function loginOut()
    {
        try{
            $token = $this->request->param('token');
            $res = \auth\Token::instance()->rmAccessToken($token);
            return json($res);
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }
}