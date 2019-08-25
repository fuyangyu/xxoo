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


    // 用户登陆页
    public function logination()
    {
        if($this->request->param('code')){  //验证码登陆
            return $this->fetch('demo2');
        }else {
            return $this->fetch('demo');
        }
    }


    /**
     * 登录
     * @return \think\response\Json
     */
    public function login()
    {
        try{
            if ($this->request->isPost()) {
                $validate = new \app\api\validate\Member();
                $data = $this->request->param();
                //区分是否验证码登陆进行不同验证
                $code = !empty($data['code'])?'VerifyLogin':'login';
                //规则验证
                if (!$vdata = $validate->scene($code)->check($data)) {
                    return json($this->outJson(0,$validate->getError()));
                }
                $checkPhone = Db::name('member')->where(['phone' => trim($data['phone'])])->field('uid,nick_name,vip_end_time')->find();
                if (!$checkPhone) return json($this->outJson(0,'该手机号还未注册'));
                //不同登陆流程
                if(!empty($data['code'])){
                    //验证短信验证码
                    $bool = $this->checkPhoneCode(trim($data['phone']), trim($data['code']), 'login');
                    if (!$bool){
                        return json($this->outJson(0,'短信验证码错误'));
                    }else {
                        $token = \auth\Token::instance()->getAccessToken($checkPhone['uid'],$checkPhone['nick_name']);
                        //验证会员到期时间存储消息
                        $expire = 60*60*24*30;
                        $vip_end = strtotime($checkPhone['vip_end_time']) - time();
                        if($expire < $vip_end){
                            $end_time = sprintf('%d', floor($vip_end / 86400));
                            $this->insertMessage([
                                'uid' => $checkPhone['uid'],
                                'content' => '您的点动生活会员只有'.$end_time.'天就要过期了，赶紧去续费吧！',
                                'add_time' => date('Y-m-d H:i:s')
                            ]);
                        }
                        return json($this->outJson(1, '登录成功', $token['data']));
                    }
                }else {
                    $model = new \app\api\model\Member();
                    $result = $model->sendLogin($data);
                    if($result['status'] == 1){
                        //验证会员到期时间存储消息
                        $expire = 60*60*24*30;
                        $vip_end = strtotime($checkPhone['vip_end_time']) - time();
                        if($expire < $vip_end){
                            $end_time = sprintf('%d', floor($vip_end / 86400));
                            $this->insertMessage([
                                'uid' => $checkPhone['uid'],
                                'content' => '您的点动生活会员只有'.$end_time.'天就要过期了，赶紧去续费吧！',
                                'add_time' => date('Y-m-d H:i:s')
                            ]);
                        }
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
     * 用户注册
     * @return \think\response\Json
     */
    public function register()
    {
        try{
            if ($this->request->isPost()) {
                $validate = new \app\api\validate\Member();
//                $data = ['phone'=>'18986338986','password'=>123456,'invite_phone'=>'15502725551','province'=>1964,'city'=>1988];
                $data = $this->request->param();
                if (!$vdata = $validate->scene('register')->check($data)) {
                    return json($this->outJson(0,$validate->getError()));
                }
                // 验证邀请码(用户手机号) 1:invite_phone指注册时邀请人号码
                if (empty($data['invite_phone'])) {
                    $data['invite_uid'] = $data['invite_phone'] = 0;
                    $data['invite_time'] = 0;
                }else {
                    $data['invite_uid'] = $check = Db::name('member')->where(['phone' => trim($data['invite_phone'])])->value('uid');
                    if (!$check) return json($this->outJson(0,'推荐人不存在哦！'));
                    $data['invite_time'] = date('Y-m-d H:i:s');
                }

                // 验证该号码是否已经被注册
                $checkPhone = Db::name('member')->where(['phone' => trim($data['phone'])])->find();
                if ($checkPhone) return json($this->outJson(0,'该手机号码已被注册'));

                //验证地址
                if(empty($data['province']) || empty($data['city'])){
                    return json($this->outJson(0,'请选择所在地！'));
                }

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
     * 验证短信验证码
     * @return \think\response\Json
     */
    public function verifier(){
        $validate = new \app\api\validate\Member();
        $data = $this->request->param();
        if (!$vdata = $validate->scene('VerifyLogin')->check($data)) {
            return json($this->outJson(0,$validate->getError()));
        }
        //验证短信验证码
        $bool = $this->checkPhoneCode(trim($data['phone']), trim($data['code']),trim($data['scene']));
        if (!$bool){
            return json($this->outJson(0,'短信验证码错误'));
        }
        return json($this->outJson(1,'短信验证成功'));
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

    /**
     * @return \think\response\Json
     * 获取省市
     * id 父类id
     * level 等级 1省 2市 3区
     */
    public function getarea()
    {
        try{
            if ($this->request->isPost()) {
                $data['province'] = \app\api\data\Area::instance()->getProvinceData();
                $data['city'] = \app\api\data\Area::instance()->getCityAllData();
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }
}