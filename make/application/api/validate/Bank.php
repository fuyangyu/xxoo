<?php
namespace app\api\validate;
use think\Validate;
class Bank extends Validate
{
    protected $rule =   [
        'user_name'   => 'require',
        'bank_phone' => 'require|min:11|checkPhone',
        'bank_name'   => 'require',
//        'bank_branch_name'   => 'require',
        'bank_account' => 'require',
        'id_card' => 'require'
    ];

    protected $message  =   [
        'user_name.require' => '持卡人姓名不能为空',
        'phone.require' => '手机号码不能为空',
        'bank_phone.min'     => '手机号码不能少于11位字符',
//        'bank_name.require'     => '银行卡名称不能为空',
//        'bank_branch_name.require'     => '支行名称不能为空',
        'bank_account.require'     => '银行卡号不能为空',
        'id_card.require'     => '身份证号码不能为空',
    ];

    protected $scene = [
        'all' => ['user_name','bank_phone','bank_account','id_card'],
        'bank' => ['user_name','bank_name','bank_account','bank_card']
    ];

    // 自定义验证规则
    protected function checkPhone($value,$rule,$data)
    {
        return cp_isMobile($value) ? true : '手机号码格式错误';
    }
}