<?php
namespace app\admin\controller;
use think\Db;
use think\Response;

class Sales extends AdminBase
{
    // 初始化日志信息写入参数
    protected $logMsg;

    // 设置分销
    public function index()
    {
        return $this->fetch('index',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'分销配置']
            ],
            'userLevel' => $this->userLevel(2),
            'business' => $this->getBusiness()
        ]);
    }

    /**
     * 异步获取分销配置列表数据
     * @return array
     */
    public function getIndexData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Sales();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'userLevel' => $this->request->param('userLevel',0),
                'type' => $this->request->param('type',0),
            ];
            $data = $model->getListData($model->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 批量删除分销配置
     * @return array
     */
    public function delIndexList()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Sales();
            $param = $this->request->param();
            $ids = isset($param['ids']) ? $param['ids'] : [];
            $log_ids = implode(',',$ids);
            if ($model->del($ids)) {
                return $this->outJson(0,'删除成功',[
                    'logMsg' => "删除了分销配置-id($log_ids)"
                ]);
            } else {
                return $this->outJson(1,$model->getError());
            }
        }
    }

    /**
     * 添加编辑配置
     * @return mixed
     */
    public function add()
    {
        $id = $this->request->param('id',0);
        $html = $id ? '编辑' : '添加';
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Sales();
            $res = $model->store($this->request->param());
            if ($res['code'] == 0) {
                $log_ids = $id ? $id : $res['data']['id'];
                return $this->outJson(0,'操作成功',[
                    'logMsg' => $html . "分销配置-id($log_ids)",
                    'url' => $this->entranceUrl . "/sales/index.html"
                ]);
            } else {
                return $res;
            }
        } else {
            // 初始化
            $result = [
                'allot_one' => '',
                'allot_two' => '',
                'allot_three' => '',
                'infinite' => '',
                'carry_index' => '',
                'charge_type' => 1,
                'user_level' => 1
            ];
            if ($id > 0) {
                $result = Db::name('allot_log')->where(['id' => $id])->find();
            }

            return $this->fetch('add',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/sales/index",'name'=>'分销配置'],
                    ['url' => '','name' => $html . '配置']
                ],
                'userLevel' => $this->userLevel(2),
                'business' => $this->getBusiness(),
                'data' => $result,
                'id' => $id
            ]);
        }
    }
}