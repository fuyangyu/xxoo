<?php
namespace app\admin\controller;
use think\Db;
use think\Response;

class Gate extends AdminBase
{
    // 初始化日志信息写入参数
    protected $logMsg;

    // 轮播管理
    public function index()
    {
        return $this->fetch('index',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'轮播管理']
            ]
        ]);
    }

    /**
     * 异步获取分销配置列表数据
     * @return array
     */
    public function getIndexData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Banner();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords','')
            ];
            $data = $model->getListData($model->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 批量删除轮播图
     * @return array
     */
    public function delIndexList()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Banner();
            $param = $this->request->param();
            $ids = isset($param['ids']) ? $param['ids'] : [];
            $log_ids = implode(',',$ids);
            if ($model->del($ids)) {
                return $this->outJson(0,'删除成功',[
                    'logMsg' => "删除了轮播图-id($log_ids)"
                ]);
            } else {
                return $this->outJson(1,$model->getError());
            }
        }
    }

    /**
     * 上传轮播图片
     * @return \think\response\Json
     */
    public function uploads()
    {
        return json($this->fileUploads('files','banner'));
    }

    /**
     * 移除轮播图片
     * @return \think\response\Json
     */
    public function delUploads()
    {
        $file_url = $this->request->param('did');
        if (@unlink('.' . $file_url)) {
            return json($this->outJson(1,'移除成功!'));
        } else {
            return json($this->outJson(0,'移除失败!'));
        }
    }

    /**
     * 添加编辑轮播图
     * @return mixed
     */
    public function addBanner()
    {
        $id = $this->request->param('id',0);
        $html = $id ? '编辑' : '添加';
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Banner();
            $res = $model->store($this->request->param());
            if ($res['code'] == 0) {
                $log_ids = $id ? $id : $res['data']['id'];
                return $this->outJson(0,'操作成功',[
                    'logMsg' => $html . "轮播图-id($log_ids)",
                    'url' => $this->entranceUrl . "/gate/index.html"
                ]);
            } else {
                return $res;
            }
        } else {
            // 初始化
            $result = [
                'sort' => '',
                'url' => '',
                'is_show' => 1,
                'skip' => ''
            ];
            if ($id > 0) {
                $result = Db::name('banner')->where(['id' => $id])->find();
            }

            return $this->fetch('add_banner',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/gate/index",'name'=>'轮播图列表'],
                    ['url' => '','name' => $html . '轮播图']
                ],
                'data' => $result,
                'id' => $id
            ]);
        }
    }

    // 公告管理
    public function notice()
    {
        return $this->fetch('notice',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'公告管理']
            ]
        ]);
    }

    /**
     * 异步获取公告管理列表数据
     * @return array
     */
    public function getNoticeData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Notice();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords','')
            ];
            $data = $model->getListData($model->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    /**
     * 批量删除公告管理
     * @return array
     */
    public function delNoticeList()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Notice();
            $param = $this->request->param();
            $ids = isset($param['ids']) ? $param['ids'] : [];
            $log_ids = implode(',',$ids);
            if ($model->del($ids)) {
                return $this->outJson(0,'删除成功',[
                    'logMsg' => "删除了公告-id($log_ids)"
                ]);
            } else {
                return $this->outJson(1,$model->getError());
            }
        }
    }

    /**
     * 添加编辑公告
     * @return mixed
     */
    public function addNotice()
    {
        $id = $this->request->param('id',0);
        $html = $id ? '编辑' : '添加';
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Notice();
            $res = $model->store($this->request->param());
            if ($res['code'] == 0) {
                $log_ids = $id ? $id : $res['data']['id'];
                return $this->outJson(0,'操作成功',[
                    'logMsg' => $html . "公告-id($log_ids)",
                    'url' => $this->entranceUrl . "/gate/notice.html"
                ]);
            } else {
                return $res;
            }
        } else {
            // 初始化
            $result = [
                'title' => '',
                'content' => '',
                'is_index' => 0,
                'url' => '',
                'is_show' => 1
            ];
            if ($id > 0) {
                $result = Db::name('notice')->where(['id' => $id])->find();
            }

            return $this->fetch('add_notice',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/gate/notice",'name'=>'公告列表'],
                    ['url' => '','name' => $html . '公告']
                ],
                'data' => $result,
                'id' => $id
            ]);
        }
    }

    /**
     * 会员收费金额设置
     * @return mixed
     */
    public function setting()
    {
        $data = cp_getCacheFile('system');
        $data['service_mobile'] = isset($data['service_mobile']) ? $data['service_mobile'] : '';
        $data['investment_mobile'] = isset($data['investment_mobile']) ? $data['investment_mobile'] : '';
        $data['official_mobile'] = isset($data['official_mobile']) ? $data['official_mobile'] : '';
        $data['service_time'] = isset($data['service_time']) ? $data['service_time'] : '';
        $data['service_weixin'] = isset($data['service_weixin']) ? $data['service_weixin'] : '';
        $data['service_email'] = isset($data['service_email']) ? $data['service_email'] : '';
        if ($this->request->isAjax()) {
            $insert = array_merge($data,$this->request->param());
            cp_setCacheFile('system',$insert);
            return $this->outJson(0,'操作成功');
        } else {
            return $this->fetch('setting',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => '','name'=>'系统设置']
                ],
                'data' => $data
            ]);
        }
    }

    /**
     * 配置如何赚佣金
     * @return array|mixed
     */
    public function make()
    {
        $data = cp_getCacheFile('system');
        $data['make_des'] = isset($data['make_des']) ? $data['make_des'] : '';
        $data['make_img'] = isset($data['make_img']) ? $data['make_img'] : '';
        if ($this->request->isAjax()) {
            $insert = array_merge($data,$this->request->param());
            cp_setCacheFile('system',$insert);
            return $this->outJson(0,'操作成功');
        } else {
            return $this->fetch('make',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => '','name'=>'配置任务领取规则描述']
                ],
                'data' => $data
            ]);
        }
    }


    /**
     * 上传如何赚佣介绍图片
     * @return \think\response\Json
     */
    public function makeUploads()
    {
        return json($this->fileUploads('files','banner'));
    }

    /**
     * 移除如何赚佣介绍图片
     * @return \think\response\Json
     */
    public function makeDelUploads()
    {
        $file_url = $this->request->param('did');
        if (@unlink('.' . $file_url)) {
            return json($this->outJson(1,'移除成功!'));
        } else {
            return json($this->outJson(0,'移除失败!'));
        }
    }

}