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

    /**
     *任务上下架
     * @return \think\response\Json
     */

    public function alterTaskStatus(){
        $task_id = $this->request->param('task_id');   //任务id
        $status = $this->request->param('status');    //任务状态 1:上架 2:下架
        if($status = 1){
            $limit_total_num = Db::name('task')->where(['task_id' => $task_id])->value('limit_total_num');
            if($limit_total_num > 0){
                $data = Db::name('task')->where(['task_id' => $task_id])->setField('status',$status);
                if($data){
                    return json($this->outJson('0','下架成功'));
                }
            }else{
                return json($this->outJson('1','无法上架数量为0的任务!'));
            }
        }
        $data = Db::name('task')->where(['task_id' => $task_id])->setField('status',$status);
        if($data){
            return json($this->outJson('0','下架成功'));
        }
    }

    // 任务审核列表
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
     * 获取任务审核详情
     * @return mixed
     */
    public function getTask(){
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


    /**
     * 审核任务
     * @return mixed
     */
    public function taskCheck()
    {
        $model = new \app\admin\model\Task();
        // 修改任务状态
        $id = $this->request->param('id');  //353
        $status = $this->request->param('status');
        $failure_msg = $this->request->param('failure_msg');
        $message = array();
        $data = array();
        // 启动事务
        Db::startTrans();
        try{
            $task_old_data = Db::name('send_task_log')
                ->where(['id' => $id])
                ->field('task_id,title,uid,task_money,uid,is_check')
                ->find();
            if ($status == 1 && $task_old_data['is_check'] ==2) {
                // 审核成功
                $up = Db::name('send_task_log')->where(['id' => $id])->update([
                    'is_check' => 1,
                    'check_time' => date('Y-m-d H:i:s'),
                    'check_admin_id' => Session::get('admin')['uid'],//355
                    'check_admin_name' => Session::get('admin')['username'] //admin
                ]);
//                -- 发放佣金开始 --
                // 启动事务
                $user = Db::name('member')->where(['uid'=>$task_old_data['uid']])->field('uid,phone,total_money,task_money,member_class,parent_level_1,parent_level_2,parent_level_3,invite_uid')->find();
                //获取分佣配置
                $allot = Db::name('allot_log')->where(['user_level'=>$user['member_class'],'charge_type'=>2])->find();
                if($user && $allot){
                    //直推分佣
                    if(!empty($user['invite_uid'])) {
                        $data['uid_one'] = $user['invite_uid'];
                        $data['one_money'] = $task_old_data['task_money'] * ($allot['allot_one'] / 100);
                        $status1 = Db::name('member')->where(['uid' => $user['invite_uid']])->setInc('channel_money', $data['one_money']);
                        //间推分佣
                        if(!empty($user['parent_level_2']) && $status1){
                            $data['uid_two'] = $user['parent_level_1'];
                            $data['two_money'] = $task_old_data['task_money'] * ($allot['allot_two']/100);
                            $status2 = Db::name('member')->where(['uid'=>$user['parent_level_2']])->setInc('channel_money', $data['two_money']);
                            //服务中心分佣
                            if(!empty($user['parent_level_3']) && $status2){
                                $data['serve_one_money'] = $task_old_data['task_money'] * ($allot['team_one']/100);   //第一个服务中心分佣金额
                                $data['serve_two_money'] = $task_old_data['task_money'] * ($allot['team_two']/100);   //第二个服务中心分佣金额\
                                $service = array();
                                $service = $model->recursionService($user['parent_level_3'],$service);
                                if(!empty($service[0])) {
                                    $data['serve_uid_one'] = $service[0];
                                    Db::name('member')->where(['uid' => $service[0]])->setInc('channel_money', $data['serve_one_money']);
                                }
                                if(!empty($service[1])) {
                                    $data['serve_uid_two'] = $service[1];
                                    Db::name('member')->where(['uid' => $service[1]])->setInc('channel_money', $data['serve_two_money']);
                                }
                                //写入分佣记录
                                $data['task_money'] = $task_old_data['task_money'];
                                $data['uid'] = $task_old_data['uid'];
                                $data['tid'] = $task_old_data['task_id'];
                                $data['type'] = 4;  //任务
                                $data['add_time'] = date('Y-m_d H:i:s');
                                if(!empty($data)){
                                    Db::name('brokerage_log')->insertGetId($data);
                                }
                            }
                        }
                    }
                    //用户总收入金额和任务佣金总收入
                    $member['task_money'] = $user['task_money'] + $task_old_data['task_money'];    //任务总收入佣金
                    Db::name('member')->where(['uid'=>$task_old_data['uid']])->update($member);
                    //更新整个平台已完成任务总金额
                    Db::name('earnings')->where(['send_id'=>666])->setInc('task_total_money', $task_old_data['task_money']);
                }
                //-- 分佣发放完毕 --

                if ($up) {
                    $phone = substr_replace($user['phone'],'****',3,4);

                    $message[0] = [  //完成任务用户
                        'uid' => $task_old_data['uid'],
                        'content' => '您已经完成“'.$task_old_data['title'].'”任务获得任务收如入'.$task_old_data['task_money'].'元',
                        'add_time' => date('Y-m-d H:i:s')
                    ];
                    $message[1] = [  //直推用户
                        'uid' => $user['invite_uid'],
                        'content' => '您的团队用户'.$phone.'完成“'.$task_old_data['title'].'”任务获得任务收如入'.$data['two_money'].'元',
                        'add_time' => date('Y-m-d H:i:s')
                    ];
                    $message[2] = [  //间退用户
                        'uid' => $user['parent_level_2'],
                        'content' => '您的团队用户'.$phone.'完成“'.$task_old_data['title'].'”任务获得任务收如入'.$data['one_money'].'元',
                        'add_time' => date('Y-m-d H:i:s')
                    ];
                    $message[3] = [  //第一服务中心
                        'uid' => $service[0],
                        'content' => '您的团队用户'.$phone.'完成“'.$task_old_data['title'].'”任务获得任务收如入'.$data['serve_one_money'].'元',
                        'add_time' => date('Y-m-d H:i:s')
                    ];
                    $message[4] = [  //第二服务中心
                        'uid' => $service[1],
                        'content' => '您的团队用户'.$phone.'完成“'.$task_old_data['title'].'”任务获得任务收如入'.$data['serve_two_money'].'元',
                        'add_time' => date('Y-m-d H:i:s')
                    ];
                    //消息记录
                    Db::name('message_log')->insertAll($message);
                    // 提交事务
                    Db::commit();
                    return json($this->outJson(0,'操作成功',[
                        'logMsg' => "审核任务成功-id($id)",
                        'url' => $this->entranceUrl . "/task/drawList.html"
                    ]));
                } else {
                    // 回滚事务
                    Db::rollback();
                    return json($this->outJson(1,'操作失败,编码001'));
                }

            } else {
                // 审核驳回
                $ups = Db::name('send_task_log')->where(['id' => $id])->update([
                    'is_check' => 3,
                    'failure_msg' => $failure_msg,
                    'check_time' => date('Y-m-d H:i:s'),
                    'check_admin_id' => Session::get('admin')['uid'],
                    'check_admin_name' => Session::get('admin')['username']
                ]);
                if ($ups) {
                    //消息存储
                    $this->insertMessage([
                        'uid' => $task_old_data['uid'],
                        'content' => '您提交的“'.$task_old_data['title'].'”任务因不符合要求被驳回，赶紧去重新提交吧！',
                        'add_time' => date('Y-m-d H:i:s')
                    ]);
                    // 提交事务
                    Db::commit();
                    return json($this->outJson(0,'操作成功',[
                        'logMsg' => "审核任务失败-id($id),原因：" . $failure_msg,
                        'url' => $this->entranceUrl . "/task/drawList.html"
                    ]));
                } else {
                    // 回滚事务
                    Db::rollback();
                    return json($this->outJson(1,'操作失败'));
                }
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json($this->outJson(1,'操作失败,debug:'. $e->getMessage()));
        }
    }

    //测试用
//    public function ccc(){
//        $message = array();
//        $phone = substr_replace(18986338986,'****',3,4);
//
//        $message[0] = [  //完成任务用户
//            'uid' => 374,
//            'content' => '您已经完成“新鲜潮州海鲜直供”任务获得任务收如入100',
//            'add_time' => date('Y-m-d H:i:s')
//        ];
//        $message[1] = [  //直推用户
//            'uid' => 105,
//            'content' => '您的团队用户'.$phone.'完成“新鲜潮州海鲜直供”任务获得任务收如入50',
//            'add_time' => date('Y-m-d H:i:s')
//        ];
//        $message[2] = [  //间退用户
//            'uid' => 83,
//            'content' => '您的团队用户'.$phone.'完成“新鲜潮州海鲜直供”任务获得任务收如入20',
//            'add_time' => date('Y-m-d H:i:s')
//        ];
//            //消息记录
//            Db::name('message_log')->insertAll($message);
//    }


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
        $result = array();
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
                //'dist' => '南山区',
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
//                $result['dist'] = $json_area['dist'];
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