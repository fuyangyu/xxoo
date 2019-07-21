<?php
namespace app\api\controller;
use think\Db;
class Task extends Base
{
    /**
     * 获取任务分区
     * @return \think\response\Json
     */
    public function taskList()
    {
        try{
            if ($this->request->isPost()) {
                $data = Db::name('task_classify')
                    ->order(['sort' => 'asc', 'task_cid' => 'desc'])
                    ->field('task_cid,name')
                    ->select();
                $data = $data ? $data : [];
                $temp = [];
                if ($data) {
                    foreach ($data as $k => $v) {
                        $temp[$k]['cid'] = $v['task_cid'];
                        $temp[$k]['name'] = $v['name'];
                        $old = Db::name('task')
                            ->where(['task_cid' => $v['task_cid']])
                            ->field('task_id,task_money,title,limit_total_num,get_task_num')
                            ->order('task_id','desc')
                            ->limit(5)
                            ->select();
                        $new = [];
                        if ($old) {
                            foreach ($old as $k2 => $v2) {
                                if ($v2['limit_total_num'] != $v2['get_task_num']) {
                                    $new[$k2]['tid'] = $v2['task_id'];
                                    $new[$k2]['money'] = $v2['task_money'];
                                    $new[$k2]['title'] = $v2['title'];
                                }
                            }
                        }
                        $temp[$k]['child'] = array_values($new);
                    }
                }
                return json($this->outJson(1,'获取成功',$temp));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }


    /**
     * 任务大厅-获取某个指定会员等级的任务列表数据
     * @return \think\response\Json
     */
    public function appointTask()
    {
        try{
            if ($this->request->isPost()) {
                $cid = $this->request->param('cid',0,'intval');
                if (!$cid) return json($this->outJson(0,'请求参数不完整'));
                $model = new \app\api\model\Task();
                $data = $model->getIdData($this->uid, $cid);
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 获取指定任务的详细信息
     * @return \think\response\Json
     */
    public function findTask()
    {
        try{
            if ($this->request->isPost()) {
                $tid = $this->request->param('tid',0,'intval');
                if (!$tid) return json($this->outJson(0,'请求参数不完整'));
                $model = new \app\api\model\Task();
                $data = $model->getFindData($tid);
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 领取任务
     * @return \think\response\Json
     */
    public function draw()
    {
        try{
            if ($this->request->isPost()) {
                $tid = $this->request->param('tid',0,'intval');
                if (!$tid) return json($this->outJson(0,'请求参数不完整'));
                $model = new \app\api\model\Task();
                $data = $model->drawTask($this->uid, $tid);
                return json($data);
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 提交任务大厅-获取某个指定会员等级的任务列表数据
     * @return \think\response\Json
     */
    public function subAppointTask()
    {
        try{
            if ($this->request->isPost()) {
                $cid = $this->request->param('cid',0,'intval');
                if (!$cid) return json($this->outJson(0,'请求参数不完整'));
                $model = new \app\api\model\Task();
                $data = $model->getSubAppointTaskData($this->uid, $cid);
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 提交任务大厅-获取指定任务的详细信息
     * @return \think\response\Json
     */
    public function subFindTask()
    {
        try{
            if ($this->request->isPost()) {
                $id = $this->request->param('id',0,'intval');
                if (!$id) return json($this->outJson(0,'请求参数不完整'));
                $model = new \app\api\model\Task();
                $data = $model->getSubFindTaskData($id);
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 提交任务截图上传 【已废弃】
     * @return \think\response\Json
     */
    public function upTaskFile()
    {
        try{
            if ($this->request->isPost()) {
                $base64 = $this->request->param('base64');
                if (!$base64) return json($this->outJson(0,'请求参数不完整'));
                //# dataURI base_64 编码上传 手机端常用方式
                $rootPath = './uploads/assignment/' . date('Ymd');
                $target = $rootPath . "/" . date('Ymd') . uniqid() . ".jpg" ;
                if (!file_exists($rootPath)) {
                    cp_directory($rootPath);
                }
                $img = base64_decode($base64);
                if (file_put_contents($target, $img)){
                    $file_img = substr($target,1);
                    return json($this->outJson(1,'上传成功',['img' => $file_img]));
                } else {
                    return json($this->outJson(0,'上传失败'));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }


    /**
     * 提交任务
     * @return \think\response\Json
     */
    public function subTask()
    {
        try{
            if ($this->request->isPost()) {
                $id = $this->request->param('id',0,'intval');
                $base64 = $this->request->param('base64');
                if (!$id || !$base64) return json($this->outJson(0,'请求参数不完整'));
                //# dataURI base_64 编码上传 手机端常用方式
                $rootPath = './uploads/assignment/' . date('Ymd');
                $target = $rootPath . "/" . date('Ymd') . uniqid() . ".jpg" ;
                if (!file_exists($rootPath)) {
                    cp_directory($rootPath);
                }
                $img = base64_decode($base64);
                if (file_put_contents($target, $img)){
                    $file_img = substr($target,1);
                } else {
                    return json($this->outJson(0,'图片上传失败'));
                }
                $model = new \app\api\model\AllotLog();
                $data = $model->subTask($this->uid, $id, $file_img);
                return json($data);
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 初始化缓存会员数据缓存 TODO 测试用
     */
    public function ceshi()
    {
        $model = new \app\api\model\AllotLog();
        return $model->initMemberCacheData();
    }

    /**
     * 获取用户领取任务的历史记录
     * @return \think\response\Json
     */
    public function historyLog()
    {
        try{
            if ($this->request->isPost()) {
                $status = $this->request->param('status',1);
                $page = $this->request->param('page',1);
                if (!in_array($status,[1,2,3,4])) return json($this->outJson(0,'参数不合法'));
                $where['uid'] = $this->uid;
                $limit = 10;
                $start = 0;
                if ($page != 1) {
                    $start = ($page-1) * $limit;
                }
                $order = [];
                if ($status == 1) {
                    $where['is_check'] = 0;
                    $order = ['add_time' => 'desc'];
                }
                if ($status == 2) {
                    $where['is_check'] = 2;
                    $order = ['sub_time' => 'desc'];
                }
                if ($status == 3) {
                    $where['is_check'] = 1;
                    $order = ['check_time' => 'desc'];
                }
                if ($status == 4) {
                    $where['is_check'] = 3;
                    $order = ['check_time' => 'desc'];
                }
                $data = Db::name('send_task_log')
                        ->where($where)
                        ->order($order)
                        ->limit($start,$limit)
                        ->select();
                $total = Db::name('send_task_log')->where($where)->count();
                $pageNum = 0;
                // 计算总页数
                if ($total > 0) {
                    $pageNum = ceil($total / $limit);
                }
                $res = [];
                if ($data) {
                    foreach ($data as $k => $v) {
                        $res[$k]['id'] = $v['id'];
                        $res[$k]['tid'] = $v['task_id'];
                        $res[$k]['title'] = $v['title'];
                        $res[$k]['money'] = $v['task_money'];
                        $res[$k]['level_name'] = $this->getTaskUserLevelAttr($v['task_user_level']);
                        $res[$k]['status_name'] = $this->getStatusNameAttr($v['is_check']);
                    }
                }
                return json($this->outJson(1,'获取成功',['result' => $res, 'page_total' => $pageNum]));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 获取任务规则描述+图片
     * @return \think\response\Json
     */
    public function ruleDes()
    {
        try{
            if ($this->request->isPost()) {
                $fileData = cp_getCacheFile('system');
                $rule = isset($fileData['task_rule_des']) ? $fileData['task_rule_des'] : '';
                $res = [
                    'des' => $rule,
                    'img' => isset($fileData['task_rule_img']) ? $fileData['task_rule_img'] : ''
                ];
                return json($this->outJson(1,'获取成功',$res));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }


    /**
     * 会员等级 获取器
     * @param $value
     * @return string
     */
    protected function getTaskUserLevelAttr($value)
    {
        $html = '';
        if (strrpos($value,',') === false) {
            // 未发现
            switch($value)
            {
                case 1:
                    $html = '普通';
                    break;
                case 2:
                    $html = 'VIP';
                    break;
                case 3:
                    $html = '高级VIP';
                    break;
                case 4:
                    $html = '服务中心';
                    break;
            }
            return $html;
        } else {
            // 存在多个的情况
            $exp = explode(',',$value);
            $arr = [1 => '普通',2 => 'VIP',3 => '高级VIP', 4 => '服务中心'];
            foreach ($exp as $v) {
                if (isset($arr[$v])) {
                    $html .=  $arr[$v] . ",";
                }
            }
            return rtrim($html,',');
        }
    }

    protected function getStatusNameAttr($value)
    {
        $html = '';
        switch($value)
        {
            case 0:
                $html = '已领取';
                break;
            case 1:
                $html = '已完结';
                break;
            case 2:
                $html = '待审核';
                break;
            case 3:
                $html = '审核失败';
                break;
        }
        return $html;
    }
}
