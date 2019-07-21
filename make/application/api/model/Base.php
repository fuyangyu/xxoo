<?php
namespace app\api\model;
use think\Model;

/**
 * 基类模型
 * Class Base
 * @package app\common\model
 */
class Base extends Model
{
    // app 默认输出的用户头像
    protected $appDefaultFace = "/uploads/qrcode/dp_logo.jpg";

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
            "status" => $code,
            "msg" =>  $msg,
            "data" => $data
        ];
    }
}
