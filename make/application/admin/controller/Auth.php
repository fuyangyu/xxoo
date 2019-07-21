<?php
namespace app\admin\controller;
use think\Db;
use think\Response;

class Auth extends AdminBase
{
    // 初始化日志信息写入参数
    protected $logMsg;

    // 管理员管理
    public function index()
    {
        $keywords = $this->request->param('keywords','');
        $where = [];
        if ($keywords) {
            $where = (new \app\admin\model\Admin())->filtrationWhere($keywords);
        }
        $adminModel = new \app\admin\model\Admin();
        $data = $adminModel->getListData($where,[],15);
        return $this->fetch('index',[
            'res' => $data,
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'管理员管理列表']
            ],
        ]);
    }


    // 添加管理员
    public function addAdmin()
    {
        $adminModel = new \app\admin\model\Admin();
        if ($this->request->isAjax()) {
            if ($insert_id = $adminModel->insertAdmin()) {
                $id = $this->request->param('id',0);
                if ($id) {
                    $this->logMsg = "修改管理员-（id:$id）";
                } else {
                    $this->logMsg = "添加管理员-(id:$insert_id)";
                }
                return $this->outJson(0,'操作成功',[
                    'logMsg' => $this->logMsg,
                    'url' => $this->entranceUrl . "/auth/index.html"
                ]);
            } else {
                return $this->outJson(1,$adminModel->getError());
            }
        } else {
            $id = $this->request->param('id',0);
            // 获取角色数据
            $adminGroupModel = new \app\admin\model\AuthGroup();
            $obj = $adminGroupModel->all();
            $data = [];
            if ($obj) {
                $data = $obj->toArray();
            }
            if ($id) {
                $obj = $adminModel->get($id);
                $res = $obj->toArray();
                $res['is_lock'] = $obj->getData('is_lock');
                return $this->fetch('edit_admin',[
                    'data' => $data,
                    'res'  => $res,
                    'group_id' => $adminModel->getGroupId($id),
                    'crumbs'=>[
                        ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                        ['url' => $this->entranceUrl . "/auth/index",'name'=>'管理员管理列表'],
                        ['url' => '','name'=>'编辑管理员']
                    ],
                ]);
            } else {
                return $this->fetch('add_admin',[
                    'data' => $data,
                    'crumbs'=>[
                        ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                        ['url' => $this->entranceUrl . "/auth/index",'name'=>'管理员管理列表'],
                        ['url' => '','name'=>'添加管理员']
                    ],
                ]);
            }
        }
    }

    // 用户组列表
    public function groupList()
    {
        $model = new \app\admin\model\AuthGroup();
        $list = $model->getListData();
        return $this->fetch('group_list',[
            'res' => $list,
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'角色组列表']
            ],
        ]);
    }


    // 新增[更新]用户组权限
    public function addGroup()
    {
        $model = new \app\admin\model\AuthGroup();
        $authRuleModel = new \app\admin\model\AuthRule();
        if ($this->request->isAjax()) {
            $init_id = $this->request->param('init_id',0);
            if ($insert_id = $model->insertData()) {
                if ($init_id) {
                    $this->logMsg = "更新了角色组（id:{$init_id}）";
                } else {
                    $this->logMsg = "新增了角色组（id:{$insert_id}）";
                }
                return $this->outJson(0,'操作成功',[
                    'url' => $this->entranceUrl . "/auth/groupList.html",
                    'logMsg' => $this->logMsg
                ]);
            } else {
                return $this->outJson(1,$model->getError());
            }
        } else {
            $html = '添加';
            // 默认请求 渲染模板
            $id = $this->request->param('id',0);
            // 初始化参数
            $ids = [];
            $data = [
                'title' => '',
                'status' => '-1',
                'id' => 0
            ];
            if ($id) {
                $html = '修改';
                // 更新
                $obj = $model->get($id);
                if ($obj) {
                    $data = $obj->toArray();
                    $ids = explode(',',$data['rules']);
                    // 获取原始字段数据 不走模型获取器
                    $data['status'] = $obj->getData('status');
                }
            }

            return $this->fetch('add_group',[
                'group' => $authRuleModel->getTree(),
                'ids' => $ids,
                'data' => $data,
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/auth/groupList",'name'=>'用户组列表'],
                    ['url' => '','name'=> $html . '用户组权限']
                ],
            ]);
        }
    }

    // 移除角色
    public function delGroup()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->param('id',0);
            if (!$id) return $this->outJson(1,'请求id参数无值');
            $model = new \app\admin\model\AuthGroup();
            if ($model->groupDelete($id)) {
                return $this->outJson(0,'移除成功',[
                    'logMsg' => "移除角色组-（id:{$id}）"
                ]);
            } else {
                return $this->outJson(1,$model->getError());
            }
        }
    }

    // 查看角色-权限
    public function eye()
    {
        $id = $this->request->param('id',0);
        if(!$id) $this->redirect($this->entranceUrl . "/auth/groupList.html");
        $model = new \app\admin\model\AuthGroup();
        $authRuleModel = new \app\admin\model\AuthRule();
        $obj = $model->get($id);
        $ids = [];
        if ($obj) {
            $data = $obj->toArray();
            $ids = explode(',',$data['rules']);
        }
        return $this->fetch('eye',[
            'group' => $authRuleModel->getTree(),
            'ids' => $ids,
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => $this->entranceUrl . "/auth/groupList",'name'=>'用户组列表'],
                ['url' => '','name'=>'查看角色权限']
            ],
        ]);
    }

    /**
     * 权限管理列表
     * @return mixed
     */
    public function ruleList()
    {
        $authRuleModel = new \app\admin\model\AuthRule();
        $keywords = $this->request->param('keywords','');
        if ($keywords) {
            $result = $authRuleModel->getListData($authRuleModel->filtrationWhere($this->request->param()),true);
        } else {
            $result = $authRuleModel->getListData();
        }
        return $this->fetch('rule_list',[
            'result' => $result,
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'权限管理列表']
            ],
        ]);
    }

    /**
     * 添加|修改权限规则
     * @return mixed
     */
    public function addRule()
    {
        $model = new \app\admin\model\AuthRule();
        if ($this->request->isAjax()) {
            if ($insert_id = $model->insertData()) {
                $init_id = $this->request->param('init_id',0);
                if ($init_id) {
                    $this->logMsg = "更新了权限规则（id:{$init_id}）";
                } else {
                    $this->logMsg = "新增了权限规则（id:{$insert_id}）";
                }
                return $this->outJson(0,'操作成功',[
                    'url' => $this->entranceUrl . "/auth/ruleList.html",
                    'logMsg' => $this->logMsg
                ]);
            } else {
                return $this->outJson(1,$model->getError());
            }
        } else {
            $html = '添加';
            // 默认请求
            $id = $this->request->param('id',0);
            $level = $this->request->param('level',0,'int');
            $result = [
                'name' => '',
                'status' => '-1',
                'title' => '',
                'pid' => '-1'
            ];
            if ($id && !$level) {
                // 修改权限
                $result = $model::get($id)->toArray();
                $html = '修改';
            }
            // 添加子权限
            if ($id && $level) {
                $result['pid'] = $id;
                $id = 0;
            }
            $level_data = $model->field(['id','name','title','pid'])->select()->toArray();
            return $this->fetch('add_rule',[
                'result' => $result,
                'level_data' => \cocolait\helper\CpData::tree($level_data, 'title','id'),
                'init_id' => $id,
                'level_id' => $level,
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/auth/ruleList",'name'=>'权限管理列表'],
                    ['url' => '','name'=> $html . '权限规则']
                ],
            ]);
        }
    }

    /**
     * 删除权限规则
     * @return array|\think\response\Json
     */
    public function delRule()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\AuthRule();
            $id = $this->request->param('id',0,'int');
            if ($model->delRule($id)) {
                return $this->outJson(0,'移除成功',[
                    'logMsg' => "移除权限规则-（id:{$id}）"
                ]);
            } else {
                return $this->outJson(1,$model->getError());
            }
        }
    }

    // 管理员日志
    public function logList()
    {
        /*$adminLogModel = new \app\admin\model\AdminLog();
        $param = [
            'start_time' => $this->request->param('start_time',''),
            'end_time' => $this->request->param('end_time',''),
            'keywords' => $this->request->param('keywords','')
        ];
        // 系统自带的分页效果调用方式
        $data = $adminLogModel->getListData($adminLogModel->filtrationWhere($param),15);
        // 自定义分页扩展后的效果调用方式
        $data = $adminLogModel->getCustomListData($adminLogModel->filtrationWhere($param),15);*/
        return $this->fetch('log_list',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'管理员日志列表']
            ],
        ]);
    }

    /**
     * 异步获取管理日志列表数据
     * @return array
     */
    public function getLogListData()
    {
        if ($this->request->isAjax()) {
            $adminLogModel = new \app\admin\model\AdminLog();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords','')
            ];
            $data = $adminLogModel->getCustomNewListData($adminLogModel->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    // 管理员日志 查看详情
    public function logModal()
    {
        $adminLogModel = new \app\admin\model\AdminLog();
        $id = $this->request->param('id',0,'int');
        $data = $adminLogModel->get($id)->toArray();
        $t_body = [];
        foreach ($data as $k => $v) {
            switch ($k) {
                case 'admin_id':
                    $k = '管理员ID';
                    break;
                case 'username':
                    $k = '操作人';
                    break;
                case 'title':
                    $k = '操作行为';
                    break;
                case 'content':
                    $k = '操作内容';
                    $v = \cocolait\helper\CpMsubstr::msubstr($v,0,70);
                    break;
                case 'ip':
                    $k = '操作IP';
                    break;
                case 'useragent':
                    $k = '浏览器';
                    break;
                case 'create_time':
                    $k = '操作时间';
                    break;
            }
            $t_body[] = [$k,$v];
        }
        $table = [
            'title' => '详情',
            't_head' => ['标题','内容'],
            't_body' => $t_body,
        ];
        $style = "width:80px;";
        $html = \bootstrap\modal\Html::instance()->modalTableHtml($table,'table-striped',$style)->getContent();
        Response::create($html,'html')->send();
    }

    // 删除管理日志记录
    public function delLogList()
    {
        if ($this->request->isAjax()) {
            $adminLogModel = new \app\admin\model\AdminLog();
            $param = $this->request->param();
            $ids = isset($param['ids']) ? $param['ids'] : [];
            $log_ids = implode(',',$ids);
            if ($adminLogModel->del($ids)) {
                return $this->outJson(0,'删除成功',[
                    'logMsg' => "删除了日志记录-id($log_ids)"
                ]);
            } else {
                return $this->outJson(1,$adminLogModel->getError());
            }
        }
    }
}