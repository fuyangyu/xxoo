<?php
namespace app\api\validate;
use think\Validate;
class Member extends Validate
{
    protected $rule =   [
        'phone'   => 'require|min:11|checkPhone',
        'code'   => 'require|min:4',
        'password'   => 'require|min:6',
        'confirm_password' => 'require|confirm:password',
        'invite_code'   => 'require|min:6',
        'region'    => 'require',
    ];

    protected $message  =   [
        'phone.require' => '手机号码不能为空',
        'phone.min'     => '手机号码不能少于11位字符',
        'code.require'     => '短信验证不能为空',
        'code.min'     => '短信验证码不能少于4个字符',
//        'invite_phone.require' => '邀请码不能为空',
//        'invite_phone.min' => '邀请码不能少于6个字符',
        'password.require'   => '密码不能为空',
        'password.min'  => '密码不能小于6个字符',
        'confirm_password.require' => '确认密码不能为空',
        'confirm_password.confirm' => '密码和确认密码输入不一致',
        'province' => '请选择所在地！',
        'city' => '请选择所在地！',
    ];

    protected $scene = [
        'login' => ['phone','password'], //默认登陆
        'VerifyLogin' => ['phone'], //验证码登陆
        'find' => ['phone','password'],
        'register' => ['phone','code','password','province','city'],
//        'register' => ['phone','code','password','confirm_password','invite_phone'],
    ];

    // 自定义验证规则
    protected function checkPhone($value,$rule,$data)
    {
        return cp_isMobile($value) ? true : '手机号码格式错误';
    }
}