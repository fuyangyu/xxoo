<?php
namespace app\admin\validate;
use think\Validate;
class Admin extends Validate
{
    protected $rule =   [
        'group_id'   => 'require',
        'username'   => 'require|max:10',
        'password'   => 'require|min:6',
        'nickname'   => 'require|max:30',
        'email'      => 'require|email',
        'sex'        => 'require',
        'signature'  => 'require',
        'old_password' => 'require|min:6',
        'new_password' => 'require|min:6',
        're_password' => 'require|confirm:password',
    ];

    protected $message  =   [
        'group_id.require' => '请选择所属角色',
        'username.require' => '用户名不能为空',
        'username.max'     => '用户名最多不能超过10个字符',
        'password.require'   => '密码不能为空',
        'password.min'  => '密码不能小于6个字符',
        'nickname.require' => '昵称不能为空',
        'nickname.max'     => '昵称最多不能超过30个字符',
        'email.require' => '邮箱不能为空',
        'email.email'     => '邮箱格式错误',
        're_password.require' => '确认密码不能为空',
        're_password.confirm' => '密码和确认密码不一致',
    ];

    protected $scene = [
        'login' => ['username','password'],
        'insert' => ['group_id','username','password','re_password'],
        'edit' => ['group_id','username'],
    ];

}