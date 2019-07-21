<?php
namespace app\api\behavior;
use think\Request;

class ApiSendLog
{

    public function run(&$params)
    {
        if (request()->isPost() || request()->isGet())
        {
            \app\api\data\Send::record($params);
        }
    }

}
