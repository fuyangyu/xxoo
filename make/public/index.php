<?php
namespace think;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

// 定义配置文件目录和应用目录同级
define('CONF_PATH', __DIR__.'/../config/');

// 绑定到指定模块
define('BIND_MODULE','index');

// 加载框架基础文件
require __DIR__ . '/../framework/base.php';

// 执行应用
App::run()->send();