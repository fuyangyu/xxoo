<?php
/**
 * 权限规则表 验证器
 * Created by PhpStorm.
 * User: chen
 * Date: 2016/12/2
 * Time: 13:45
 */
namespace app\admin\validate;
use think\Validate;
class AuthRule extends Validate
{
    protected $rule =   [
        'name'  => 'require',
        'title'  => 'require|min:2',
    ];

    protected $message  =   [
        'name.require'      => '权限规则不能为空',
        'title.require'     => '权限名称不能为空',
        'title.min'         => '权限名称不能小于2个字符',
    ];

    protected $scene = [
        'store' => ['name','title']
    ];

}