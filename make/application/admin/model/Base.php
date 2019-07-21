<?php
namespace app\admin\model;
use think\Config;
use think\Model;
use think\Db;

/**
 * 基类模型
 * Class Base
 * @package app\common\model
 */
class Base extends Model
{
    //自定义初始化
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 输出json数组
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected function outJson($code = 0, $msg = '', $data = [])
    {
        return [
            "code" => $code,
            "msg" =>  $msg,
            "data" => $data
        ];

    }
}
