<?php
/**
 * 用户组表
 * Created by PhpStorm.
 * User: chen
 * Date: 2016/12/2
 * Time: 13:45
 */
namespace app\admin\validate;
use think\Validate;
class AuthGroup extends Validate
{
    //验证规则
    protected $rule =   [
        'title'  => 'require',
    ];

    //提示信息
    protected $message  =   [
        'title.require'   => '角色组名称不能为空',
    ];

    //验证场景
    protected $scene = [
        'insert' => ['title']
    ];

}