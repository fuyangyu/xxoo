<?php
namespace app\admin\validate;
use think\Validate;
class Task extends Validate
{
    protected $rule =   [
        'task_cid'  => 'require',
        'img_url'   => 'require',
        'title'   => 'require',
        'content'   => 'require',
        'task_money'   => 'require',
        'limit_total_num'      => 'require',
    ];

    protected $message  =   [
        'task_cid.require' => '请选择所属任务分区',
        'title.require' => '标题不能为空',
        'content.require' => '文案内容不能为空',
        'img_url.require'     => '必须上传图片',
        'task_money.require'   => '任务赏金不能为空',
        'limit_total_num.require'  => '任务数量不能为空',
    ];

    protected $scene = [
        'all' => ['task_cid','img_url','title','content','task_money','limit_total_num']
    ];

}