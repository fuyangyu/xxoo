<?php
/**
 * 后台菜单配置文件
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/7 0007
 * Time: 14:38
 */
$baseFile = \think\Request::instance()->baseFile();
return [
    'adminMenu' => [
        //首页
        ['module'=>'admin','line' => false,'controller'=>'Index','open'=>'active','close' =>'',
            'live_1'=> [
                [
                    'name'=>'首页',
                    'controller'=>'Index',
                    'url'=>"$baseFile/index/main.html",
                    'action'=>'',
                    'ico'=>'fa fa-home',
                    'live_2'=>[]
                ]
            ]
        ],
        // 系统管理
        ['module'=>'admin','line' => false,'controller'=>'Auth','open'=>'active','close' =>'',
            'live_1'=> [
                [
                    'name'=>'系统管理',
                    'controller'=>'Auth',
                    'url' => '#',
                    'action'=>'',
                    'ico'=>'fa fa-gears',
                    'live_2'=>[
                        [
                            'name'=>'管理员管理',
                            'controller'=>'Auth',
                            'action'=>'index',
                            'url'=>"$baseFile/auth/index.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'管理员日志',
                            'controller'=>'Auth',
                            'action'=>'logList',
                            'url'=>"$baseFile/auth/logList.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'角色组',
                            'controller'=>'Auth',
                            'action'=>'groupList',
                            'url'=>"$baseFile/auth/groupList.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'规则管理',
                            'controller'=>'Auth',
                            'action'=>'ruleList',
                            'url'=>"$baseFile/auth/ruleList.html",
                            'live_3' => []
                        ]
                    ]
                ]
            ]
        ],
        // 会员管理
        ['module'=>'admin','line' => false,'controller'=>'Member','open'=>'active','close' =>'',
            'live_1'=> [
                [
                    'name'=>'会员管理',
                    'controller'=>'Member',
                    'url' => '#',
                    'action'=>'',
                    'ico'=>'fa fa-users',
                    'live_2'=>[
                        [
                            'name'=>'会员列表',
                            'controller'=>'Member',
                            'action'=>'index',
                            'url'=>"$baseFile/Member/index.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'服务中心升级申请',
                            'controller'=>'Member',
                            'action'=>'serve',
                            'url'=>"$baseFile/Member/serve.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'会员收费金额设置',
                            'controller'=>'Member',
                            'action'=>'setting',
                            'url'=>"$baseFile/Member/setting.html",
                            'live_3' => []
                        ],
                    ]
                ]
            ]
        ],

        // 财务管理
        ['module'=>'admin','line' => false,'controller'=>'Finance','open'=>'active','close' =>'',
            'live_1'=> [
                [
                    'name'=>'财务管理',
                    'controller'=>'Finance',
                    'url' => '#',
                    'action'=>'',
                    'ico'=>'fa fa-money',
                    'live_2'=>[
                        [
                            'name'=>'充值记录',
                            'controller'=>'Member',
                            'action'=>'recharge',
                            'url'=>"$baseFile/Member/recharge.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'提现申请记录',
                            'controller'=>'Member',
                            'action'=>'withDraw',
                            'url'=>"$baseFile/Member/withDraw.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'佣金记录列表',
                            'controller'=>'Member',
                            'action'=>'brokerage',
                            'url'=>"$baseFile/Member/brokerage.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'分润收益明细',
                            'controller'=>'Member',
                            'action'=>'profit',
                            'url'=>"$baseFile/Member/profit.html",
                            'live_3' => []
                        ],
                    ]
                ]
            ]
        ],

        // 任务管理
        ['module'=>'admin','line' => false,'controller'=>'Task','open'=>'active','close' =>'',
            'live_1'=> [
                [
                    'name'=>'任务管理',
                    'controller'=>'Task',
                    'url' => '#',
                    'action'=>'',
                    'ico'=>'fa fa-map',
                    'live_2'=>[
                        [
                            'name'=>'任务列表',
                            'controller'=>'Task',
                            'action'=>'index',
                            'url'=>"$baseFile/Task/index.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'任务审核',
                            'controller'=>'Task',
                            'action'=>'drawList',
                            'url'=>"$baseFile/Task/drawList.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'任务领取记录',
                            'controller'=>'Task',
                            'action'=>'getDrawList',
                            'url'=>"$baseFile/Task/getDrawList.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'发布任务',
                            'controller'=>'Task',
                            'action'=>'add',
                            'url'=>"$baseFile/Task/add.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'任务分区',
                            'controller'=>'Task',
                            'action'=>'category',
                            'url'=>"$baseFile/Task/category.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'配置任务领取规则描述+图片',
                            'controller'=>'Task',
                            'action'=>'brokerageRule',
                            'url'=>"$baseFile/Task/brokerageRule.html",
                            'live_3' => []
                        ],
                    ]
                ]
            ]
        ],

        // 分销管理
        ['module'=>'admin','line' => false,'controller'=>'Sales','open'=>'active','close' =>'',
            'live_1'=> [
                [
                    'name'=>'分销管理',
                    'controller'=>'Sales',
                    'url' => '#',
                    'action'=>'',
                    'ico'=>'fa fa-indent',
                    'live_2'=>[
                        [
                            'name'=>'分销配置',
                            'controller'=>'Sales',
                            'action'=>'index',
                            'url'=>"$baseFile/Sales/index.html",
                            'live_3' => []
                        ],
                    ]
                ]
            ]
        ],

        // app首页管理
        ['module'=>'admin','line' => false,'controller'=>'Gate','open'=>'active','close' =>'',
            'live_1'=> [
                [
                    'name'=>'app首页管理',
                    'controller'=>'Gate',
                    'url' => '#',
                    'action'=>'',
                    'ico'=>'fa fa-institution',
                    'live_2'=>[
                        [
                            'name'=>'轮播管理',
                            'controller'=>'Gate',
                            'action'=>'index',
                            'url'=>"$baseFile/Gate/index.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'公告管理',
                            'controller'=>'Gate',
                            'action'=>'notice',
                            'url'=>"$baseFile/Gate/notice.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'配置如何赚佣',
                            'controller'=>'Gate',
                            'action'=>'make',
                            'url'=>"$baseFile/Gate/make.html",
                            'live_3' => []
                        ],
                        [
                            'name'=>'系统设置',
                            'controller'=>'Gate',
                            'action'=>'setting',
                            'url'=>"$baseFile/Gate/setting.html",
                            'live_3' => []
                        ],
                    ]
                ]
            ]
        ],
    ]
];
