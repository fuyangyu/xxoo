<?php
namespace app\admin\validate;
use think\Validate;
class Users extends Validate
{
    protected $rule =   [
        'c_name'   => 'require',
        'c_type'   => 'require',
    ];

    protected $message  =   [
        'c_name.require' => '名称不能为空',
        'c_type.require' => '请选择标签类型',
    ];

    protected $scene = [
        'userLabel_add' => ['c_name',"c_type"],
    ];

}
