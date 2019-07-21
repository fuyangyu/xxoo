<?php
namespace app\admin\controller;
use think\Db;
use think\Response;
use think\Session;

class Task extends AdminBase
{
    // 初始化日志信息写入参数
    protected $logMsg;

    // 任务列表
    public function index()
    {
        $taskCategoryModel = new \app\admin\model\TaskCategory();
        return $this->fetch('index',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'任务列表']
            ],
            'task_cate' => $taskCategoryModel->getHtmlList(),
            'userLevel' => $this->userLevel(2),
        ]);
    }

    /**
     * 异步获取任务列表数据
     * @return array
     */
    public function getIndexData()
    {
        if ($this->request->isAjax()) {
            $memberModel = new \app\admin\model\Task();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'userLevel' => $this->request->param('userLevel',0),
                'cid' => $this->request->param('cid',0)
            ];
            $data = $memberModel->getListData($memberModel->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }

    // 任务审核
    public function drawList()
    {
        $taskCategoryModel = new \app\admin\model\TaskCategory();
        return $this->fetch('draw_list',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'任务审核']
            ],
            'task_cate' => $taskCategoryModel->getHtmlList(),
            'userLevel' => $this->userLevel(),
        ]);
    }

    /**
     * 异步获取任务审核
     * @return array
     */
    public function getDrawListData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Task();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'userLevel' => $this->request->param('userLevel',0),
                'cid' => $this->request->param('cid',0)
            ];
            $where = $model->filtrationWhere($param);
            $check_status = $this->request->param('check_status');
            if ($check_status != 9) {
                $where['is_check'] = $check_status;
            }
            $where['is_check'] = ['in',[1,2,3]];
            $data = $model->getDrawListData($where,$this->request->param('limit',15),['sub_time' => 'desc']);
            return $data;
        }
    }

    // 任务领取记录
    public function getDrawList()
    {
        $taskCategoryModel = new \app\admin\model\TaskCategory();
        return $this->fetch('get_draw_list',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'任务记录列表']
            ],
            'task_cate' => $taskCategoryModel->getHtmlList(),
            'userLevel' => $this->userLevel(),
        ]);
    }

    /**
     * 异步获取任务领取记录列表数据
     * @return array
     */
    public function getDrawListHtmlData()
    {
        if ($this->request->isAjax()) {
            $model = new \app\admin\model\Task();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords',''),
                'userLevel' => $this->request->param('userLevel',0),
                'cid' => $this->request->param('cid',0)
            ];
            $where = $model->filtrationWhere($param);
            $where['is_check'] = 0;
            $data = $model->getDrawListData($where,$this->request->param('limit',15),['add_time' => 'desc']);
            return $data;
        }
    }

    /**
     * 审核广告任务
     * @return mixed
     */
    public function taskCheck()
    {
        if ($this->request->isAjax()) {
            // 修改任务状态
            $id = $this->request->param('id');
            $status = $this->request->param('status');
            $failure_msg = $this->request->param('failure_msg');
            // 启动事务
            Db::startTrans();
            try{
                if ($status == 1) {
                    // 审核成功
                    $up = Db::name('send_task_log')->where(['id' => $id])->update([
                        'is_check' => 1,
                        'check_time' => date('Y-m-d H:i:s'),
                        'check_admin_id' => Session::get('admin')['uid'],
                        'check_admin_name' => Session::get('admin')['username']
                    ]);
                    $task_old_data = Db::name('send_task_log')
                                    ->where(['id' => $id])
                                    ->field('task_id,task_money,uid')
                                    ->find();
                    // TODO 记录业务分润收益记录
                    $c_data = [
                        'uid' => $task_old_data['uid'],
                        'task_log_id' => $id,
                        'order_sn' => ''
                    ];
                    $init_earnings_log_data = createEarningsLog($task_old_data['task_money'],2,$task_old_data['task_id'],$c_data);
                    $eg_id = Db::name('earnings_log')->insertGetId($init_earnings_log_data);
                    // TODO 汇总业务分润金额
                    $earnings_data = Db::name('earnings')->where(['send_id' => 666])->find();
                    if ($earnings_data) {
                        $earnings_id = Db::name('earnings')->where(['send_id' => 666])->update([
                            'terrace_total_money' => $init_earnings_log_data['terrace_money'] + $earnings_data['terrace_total_money'],
                            'static_total_money' => $init_earnings_log_data['static_money'] + $earnings_data['static_total_money'],
                            'fund_total_money' => $init_earnings_log_data['fund_money'] + $earnings_data['fund_total_money']
                        ]);
                    } else {
                        $earnings_id = Db::name('earnings')->insertGetId([
                            'terrace_total_money' => $init_earnings_log_data['terrace_money'],
                            'static_total_money' => $init_earnings_log_data['static_money'],
                            'fund_total_money' => $init_earnings_log_data['fund_money']
                        ]);
                    }

                    // 佣金发放
                    $check = Db::name('hire_log')->where(['type_log_id' => $id])->field('uid,hire_money')->select();
                    if ($check) {
                        // 存在佣金更新
                        $check_up = Db::name('hire_log')->where(['type_log_id' => $id])->update([
                            'is_check' => 1,
                            'check_time' => date('Y-m-d H:i:s')
                        ]);
                        // 给用户加佣金
                        $uid_s = [];
                        $money_arr =[];
                        foreach ($check as $k => $v) {
                            $uid_s[] = $v['uid'];
                            $money_arr[$v['uid']] = $v['hire_money'];
                        }
                        $bl = false;
                        $bl_one = 0;
                        if ($uid_s) {
                            foreach ($uid_s as $uid) {
                                $bl = true;
                                $bl_one = Db::name('member')->where('uid','=',$uid)->setInc('balance',$money_arr[$uid]);
                            }
                        }

                        if ($bl) {
                            if ($up && $check_up && $bl_one && $eg_id && $earnings_id) {
                                // 提交事务
                                Db::commit();
                                return $this->outJson(0,'操作成功',[
                                    'logMsg' => "审核任务成功-id($id)",
                                    'url' => $this->entranceUrl . "/task/drawList.html"
                                ]);
                            } else {
                                // 回滚事务
                                Db::rollback();
                                return $this->outJson(1,'操作失败,编码001,up:' . $up . "-check_up:" . $check_up . '-bl_one:' . $bl_one . '-eg_id:' . $eg_id . "-earnings_id:" .$earnings_id );
                            }
                        } else {
                            if ($up && $check_up && $eg_id && $earnings_id) {
                                // 提交事务
                                Db::commit();
                                return $this->outJson(0,'操作成功',[
                                    'logMsg' => "审核任务成功-id($id)",
                                    'url' => $this->entranceUrl . "/task/drawList.html"
                                ]);
                            } else {
                                // 回滚事务
                                Db::rollback();
                                return $this->outJson(1,'操作失败,编码001');
                            }
                        }

                    } else {
                        // 不存在直接跳过
                        if ($up && $eg_id && $earnings_id) {
                            // 提交事务
                            Db::commit();
                            return $this->outJson(0,'操作成功',[
                                'logMsg' => "审核任务成功-id($id)",
                                'url' => $this->entranceUrl . "/task/drawList.html"
                            ]);
                        } else {
                            // 回滚事务
                            Db::rollback();
                            return $this->outJson(1,'操作失败');
                        }
                    }

                } else {
                    // 审核失败
                    $up = Db::name('send_task_log')->where(['id' => $id])->update([
                        'is_check' => 3,
                        'failure_msg' => $failure_msg,
                        'check_time' => date('Y-m-d H:i:s'),
                        'check_admin_id' => Session::get('admin')['uid'],
                        'check_admin_name' => Session::get('admin')['username']
                    ]);
                    if ($up) {
                        // 提交事务
                        Db::commit();
                        return $this->outJson(0,'操作成功',[
                            'logMsg' => "审核任务失败-id($id),原因：" . $failure_msg,
                            'url' => $this->entranceUrl . "/task/drawList.html"
                        ]);
                    } else {
                        // 回滚事务
                        Db::rollback();
                        return $this->outJson(1,'操作失败');
                    }
                }
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $this->outJson(1,'操作失败,debug:'. $e->getMessage());
            }
        } else {
            $id = $this->request->param('id');
            $data = Db::name('send_task_log')->where(['id' => $id])->find();
            $data['phone'] = Db::name('member')->where(['uid' => $data['uid']])->value('phone');
            return $this->fetch('task_check',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/task/drawList",'name'=>'任务记录列表'],
                    ['url' => '','name'=> '审核任务']
                ],
                'id' => $id,
                'data' => $data
            ]);
        }
    }


    /**
     * 批量删除任务
     * @return array
     */
    public function delTaskIndexList()
    {
        if ($this->request->isAjax()) {
            $TaskModel = new \app\admin\model\Task();
            $param = $this->request->param();
            $ids = isset($param['ids']) ? $param['ids'] : [];
            $log_ids = implode(',',$ids);
            if ($TaskModel->del($ids)) {
                return $this->outJson(0,'删除成功',[
                    'logMsg' => "删除了任务-id($log_ids)"
                ]);
            } else {
                return $this->outJson(1,$TaskModel->getError());
            }
        }
    }

    /**
     * 发布任务
     * @return mixed
     */
    public function add()
    {
        $id = $this->request->param('id',0);
        $html = $id ? '编辑' : '发布';
        if ($this->request->isAjax()) {
            // 处理添加
            $validate = new \app\admin\validate\Task();
            $data = $this->request->param();
            if (!$vdata = $validate->scene('all')->check($data)) {
                return $this->outJson(1,$validate->getError());
            }
            $model = new \app\admin\model\Task();
            $res = $model->store($data);
            if ($res['code'] == 0) {
                $log_ids = $id ? $id : $res['data']['id'];
                return $this->outJson(0,'操作成功',[
                    'logMsg' => $html . "任务-id($log_ids)",
                    'url' => $this->entranceUrl . "/task/index.html"
                ]);
            } else {
                return $res;
            }
        } else {
            $taskCategoryModel = new \app\admin\model\TaskCategory();
            $result = [
                'task_cid' => 0,
                'task_user_level' => [1],
                'img_url' => '',
                'title' => '',
                'content' => '',
                'task_money' => '',
                'limit_total_num' => '',
                'prov' => '广东省',
                'city' => "深圳市",
                'dist' => '南山区',
                'limit_user_num' => '',
                'is_area' => 0,
                'img' => []
            ];
            if ($id > 0) {
                $result = Db::name('task')->where(['task_id' => $id])->find();
                $json_area = json_decode($result['task_area'],true);
                $result['task_user_level'] = explode(',',$result['task_user_level']);
                $result['prov'] = $json_area['prov'];
                $result['city'] = $json_area['city'];
                $result['dist'] = $json_area['dist'];
                $result['img'] = explode('@',$result['img_url']);
            }
            return $this->fetch('add',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/task/index",'name'=>'任务列表'],
                    ['url' => '','name'=> $html . '任务']
                ],
                'task_cate' => $taskCategoryModel->getHtmlList(),
                'userLevel' => $this->userLevel(2),
                'id' => $id,
                'res' => $result
            ]);
        }
    }

    /**
     * 上传广告图片
     * @return \think\response\Json
     */
    public function uploads()
    {
        return json($this->fileUploads('files'));
    }

    /**
     * 移除广告图片
     * @return \think\response\Json
     */
    public function delUploads()
    {
        $file_url = $this->request->param('did');
        $img_url = $this->request->param('img_url');
        if (strrpos($img_url,'@') === false) {
            $img = '';
        } else {
            $img = explode('@',$img_url);
        }
        if (@unlink('.' . $file_url)) {
            if (is_array($img)) {
                foreach ($img as $k => $v) {
                    if ($file_url == $v) {
                        unset($img[$k]);
                    }
                }
                if ($img) {
                    $img = implode('@',$img);
                } else {
                    $img = '';
                }
            }
            return json($this->outJson(1,'移除成功!',['img_url' => $img]));
        } else {
            return json($this->outJson(0,'移除失败!'));
        }
    }

    /**
     * 任务分类列表
     * @return mixed
     */
    public function category()
    {
        return $this->fetch('category',[
            'crumbs'=>[
                ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                ['url' => '','name'=>'任务分区']
            ],
        ]);
    }

    /**
     * 添加|编辑任务分类
     * @return mixed
     */
    public function addCategory()
    {
        $model = new \app\admin\model\TaskCategory();
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $res = $model->store($param);
            if ($res) {
                $ht = $param['task_cid'] ? '编辑': '新增';
                $log_ids = $param['task_cid'] ? $param['task_cid'] : $res;
                return $this->outJson(0,'操作成功',[
                    'logMsg' => $ht . "了日志记录-id($log_ids)",
                    'url' => $this->entranceUrl . "/task/category.html"
                ]);
            } else {
                return $this->outJson(1,$model->getError());
            }
        } else {
            $id = $this->request->param('id',0);
            $html = $id ? '编辑分类' : '添加分类';
            $result = [
                'name' => '',
                'sort' => '',
            ];
            if ($id) {
                $result = $model->get($id);
            }
            return $this->fetch('add_category',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => $this->entranceUrl . "/task/category",'name'=>'任务分类列表'],
                    ['url' => '','name'=> $html]
                ],
                'title' => $html,
                'result' => $result,
                'id' => $id
            ]);
        }
    }

    /**
     * 异步获取任务分类列表数据
     * @return array
     */
    public function getTaskCategoryListData()
    {
        if ($this->request->isAjax()) {
            $TaskCategoryModel = new \app\admin\model\TaskCategory();
            $param = [
                'start_time' => $this->request->param('startTime',''),
                'end_time' => $this->request->param('endTime',''),
                'keywords' => $this->request->param('keywords','')
            ];
            $data = $TaskCategoryModel->getListData($TaskCategoryModel->filtrationWhere($param),$this->request->param('limit',15));
            return $data;
        }
    }


    /**
     * 批量删除任务分类列表
     * @return array
     */
    public function delTaskCategoryList()
    {
        if ($this->request->isAjax()) {
            $TaskCategoryModel = new \app\admin\model\TaskCategory();
            $param = $this->request->param();
            $ids = isset($param['ids']) ? $param['ids'] : [];
            $log_ids = implode(',',$ids);
            if ($TaskCategoryModel->del($ids)) {
                return $this->outJson(0,'删除成功',[
                    'logMsg' => "删除了任务分类-id($log_ids)"
                ]);
            } else {
                return $this->outJson(1,$TaskCategoryModel->getError());
            }
        }
    }

    /**
     * 配置任务领取规则描述+图片
     * @return array|mixed
     */
    public function brokerageRule()
    {
        $data = cp_getCacheFile('system');
        $data['task_rule_des'] = isset($data['task_rule_des']) ? $data['task_rule_des'] : '';
        $data['task_rule_img'] = isset($data['task_rule_img']) ? $data['task_rule_img'] : '';
        if ($this->request->isAjax()) {
            $add = $this->request->param();
            $add['task_rule_des'] = str_replace(PHP_EOL, '', $add['task_rule_des']);
            $insert = array_merge($data,$add);
            cp_setCacheFile('system',$insert);
            return $this->outJson(0,'操作成功');
        } else {
            return $this->fetch('bg_rule',[
                'crumbs'=>[
                    ['url' => $this->entranceUrl . "/index/main",'name'=>'首页'],
                    ['url' => '','name'=>'配置任务领取规则描述']
                ],
                'data' => $data
            ]);
        }
    }

    /**
     * 上传任务规则图片
     * @return \think\response\Json
     */
    public function bgUploads()
    {
        return json($this->fileUploads('files','banner'));
    }

    /**
     * 移除任务规则图片
     * @return \think\response\Json
     */
    public function bgDelUploads()
    {
        $file_url = $this->request->param('did');
        if (@unlink('.' . $file_url)) {
            return json($this->outJson(1,'移除成功!'));
        } else {
            return json($this->outJson(0,'移除失败!'));
        }
    }
}