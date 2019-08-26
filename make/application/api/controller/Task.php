<?php
namespace app\api\controller;
use think\Db;
class Task extends Base
{

    /**
     * 当前任务
     * is_start 是否开始 0 未开始 1 开始
     * @return \think\response\Json
     */
    public function task(){
        $data = array();
//        $task_cid = $this->request->param('task_cid');  //任务分区参数
//        if(empty($task_cid)){   //首次进入不传任务分区的参数
            //任务区
            $data['classify'] = Db::name('task_classify')
                ->order(['sort' => 'asc', 'task_cid' => 'desc'])
                ->field('task_cid,name')
                ->select();
            if($data['classify']){
                foreach($data['classify'] as $k =>$v){
                    //当前任务
                    $sql = "SELECT task_id,title,task_icon,is_area,task_user_level,task_area,start_time,task_money,(taks_fixation_num+get_task_num) as rap_num,(limit_total_num-get_task_num) as authentic_num,1 as is_start FROM wld_task
                            WHERE start_time < unix_timestamp(now()) AND status = 1 AND task_cid = {$v['task_cid']}
                            ORDER BY start_time DESC LIMIT 5;";
                    $data['task'][$v['task_cid']] = Db::query($sql);
                    foreach($data['task'][$v['task_cid']] as $key => &$value){
                        if($value['is_area'] == 1){
                            $value['task_area'] = json_decode($value['task_area'],true)['city'];
                        }
                        $value['task_icon'] = $this->request->domain().$value['task_icon'];
                        if($this->uid){
                            $logId = Db::name('send_task_log')->where(['uid'=>$this->uid,'task_id'=>$value['task_id']])->where('is_check','in',[0,2])->find();
                            $member_classs = Db::name('member')->where('uid',$this->uid)->value('member_class');
                            if($logId){
                                $data['task'][$v['task_cid']][$key]['status'] = 1;    //已经领取
                            }
                            $data['task'][$v['task_cid']][$key]['member_classs'] = $member_classs;
                        }
                    }
                    //任务预告
                    $noticeSql = "SELECT task_id,title,task_icon,is_area,task_area,start_time,task_money,0 as is_start FROM wld_task
                                  WHERE start_time > unix_timestamp(now()) AND status = 1 AND task_cid = {$v['task_cid']}
                                  ORDER BY start_time ASC LIMIT 4;";
                    $data['notice'][$v['task_cid']] = Db::query($noticeSql);
                    if(!empty($data['notice'][$v['task_cid']])){
                        foreach($data['notice'][$v['task_cid']] as &$vas){
                            if($vas['is_area'] == 1){
                                $vas['task_area'] = json_decode($vas['task_area'],true)['city'];
                            }
                            $vas['task_icon'] = $this->request->domain().$vas['task_icon'];
                        }
                    }
                }
            }


        //收入榜
        $moneySql = "SELECT (member_brokerage_money+task_money+channel_money+static_money) as moeny_sum,face,nick_name FROM wld_member ORDER BY (member_brokerage_money+task_money+channel_money+static_money) DESC LIMIT 5;";
        $data['money'] = Db::query($moneySql);
        if($data['money']){
            foreach($data['money'] as &$item){
                $item['face'] = $this->request->domain().$item['face'];
            }
        }
        return json($this->outJson(1,'成功',$data));
    }

    /**
     * 当前任务更多请求
     * @return \think\response\Json
     */
    public function taskMore(){
        if ($this->request->isPost()) {
            $data = array();
            $page = $this->request->param('page',1); //页数
            $task_cid = $this->request->param('task_cid');  //任务分区参数
            $limit = 10;    //每页数量
            $start = 0;     //开始位置
            if ($page > 1) {
                $start = ($page-1) * $limit;
            }
            $sql = "SELECT task_id,title,task_icon,is_area,task_area,start_time,task_money,(taks_fixation_num+get_task_num) as rap_num,(limit_total_num-get_task_num) as authentic_num,1 as is_start FROM wld_task
                    WHERE start_time < unix_timestamp(now()) AND status = 1 AND task_cid = {$task_cid}
                    ORDER BY start_time DESC LIMIT {$start},{$limit};";
            $task = Db::query($sql);
            if(!empty($task)){
                foreach($task as $k => &$v){
                    if($v['is_area'] == 1){
                        $v['task_area'] = json_decode($v['task_area'],true)['city'];
                    }
                    $v['task_icon'] = $this->request->domain().$v['task_icon'];
                    if($this->uid){
                        $logId = Db::name('send_task_log')->where('uid',$this->uid)->find();
                        $member_classs = Db::name('member')->where('uid',$this->uid)->value('member_class');
                        if($logId){
                            $task[$k]['status'] = 1;    //已经领取
                        }
                        $task[$k]['member_classs'] = $member_classs;
                    }
                }
            }
            $data['task'] = $task;
            return json($this->outJson(1,'成功',$data));
        } else {
            return json($this->outJson(500,'非法操作'));
        }
    }


    /**
     * 任务预告
     * is_start 是否开始 0 未开始 1 开始
     *  @return \think\response\Json
     */
/*    public function taskNotice(){

        $data = array();
        //当天任务
        $sql = "SELECT task_id,title,task_icon,is_area,task_area,start_time,task_money,0 as is_start FROM wld_task
                WHERE start_time > unix_timestamp(now()) AND status = 1
                ORDER BY start_time ASC LIMIT 4;";
        $data = Db::query($sql);
        if(!empty($data)){
            foreach($data as &$v){
                if($v['is_area'] == 1){
                    $v['task_area'] = json_decode($v['task_area'],true)['city'];
                }
                $v['task_icon'] = $this->request->domain().$v['task_icon'];
            }
        }
        return json($this->outJson(1,'成功',$data));
    }*/

    /**
     * 任务预告更多请求
     * is_start 是否开始 0 未开始 1 开始
     * @return \think\response\Json
     */

    public function taskNoticeMore(){
        $data = array();
        $page = $this->request->param('page',1); //页数
        $task_cid = $this->request->param('task_cid');  //任务分区参数
        $limit = 10;    //每页数量
        $start = 0;     //开始位置
        if ($page > 1) {
            $start = ($page-1) * $limit;
        }
        $sql = "SELECT task_id,title,task_icon,is_area,task_area,start_time,task_money,0 as is_start FROM wld_task
                    WHERE start_time > unix_timestamp(now()) AND status = 1 AND task_cid = {$task_cid}
                    ORDER BY start_time ASC LIMIT {$start},{$limit};";
        $data = Db::query($sql);
        if(!empty($data)){
            foreach($data as &$v){
                if($v['is_area'] == 1){
                    $v['task_area'] = json_decode($v['task_area'],true)['city'];
                }
                $v['task_icon'] = $this->request->domain().$v['task_icon'];
            }
        }
        return json($this->outJson(1,'成功',$data));
    }


    /**
     * 任务列表
     * @return \think\response\Json
     */
    public function taskList(){
        $data = array();
        $page = $this->request->param('page',1); //页数
        $uid = $this->request->param('uid');
        $is_check = $this->request->param('is_check',-1); // 1：审核通过 0：领取  2：待审核 3：审核失败',
        if($is_check == 0){ //待提交
            $where = ' and l.is_check = '.$is_check;
        }elseif($is_check == 2){    //审核中
            $where = ' and l.is_check ='.$is_check;
        }elseif($is_check == 3){    //审核失败
            $where = ' and l.is_check ='.$is_check;
        }elseif($is_check == 1){    //已完成
            $where = ' and l.is_check ='.$is_check;
        }else{
            $where = ''; //全部
        }
        $limit = 10;    //每页数量
        $start = 0;     //开始位置
        if ($page > 1) {
            $start = ($page-1) * $limit;
        }
        $sql = "SELECT t.task_icon,l.title,l.task_money,l.add_time,l.sub_time,l.is_check,l.failure_msg FROM
                wld_send_task_log as l LEFT JOIN wld_task t ON l.task_id =  t.task_id WHERE l.uid = $uid $where
                ORDER BY l.id DESC LIMIT {$start},{$limit};";
        $data = Db::query($sql);
        p($data);die;
        return json($this->outJson(1,'成功',$data));
    }


    /**
     * 获取任务分区 (暂不用)
     * @return \think\response\Json
     */
    public function taskNtice()
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
     * 任务大厅-获取某个指定会员等级的任务列表数据(暂不用)
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
     * 获取任务详细信息
     * @return \think\response\Json
     */
    public function findTask()
    {
        try{
            $tid = $this->request->param('tid',0,'intval');
            if (!$tid) return json($this->outJson(0,'请求参数不完整'));
            $model = new \app\api\model\Task();
            $data = $model->getFindData($tid,$this->uid);
            return json($this->outJson(1,'获取成功',$data));

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
                $uid = $this->request->param('uid');//374;
                $task_id = $this->request->param('task_id',0); //23;
                if (!$task_id || !$uid) return json($this->outJson(0,'请求参数不完整'));

                $model = new \app\api\model\Task();
                $data = $model->drawTask($uid, $task_id);
                return json($data);
            }
            else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 提交任务大厅-获取某个指定会员等级的任务列表数据（暂未调用）
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
     * 提交任务大厅-获取指定任务的详细信息(暂未调用)
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
     * 提交任务截图上传
     * @return \think\response\Json
     */
    public function upTaskFile()
    {
        try{
            if ($this->request->isPost()) {
                $task_screenshot = $this->request->param('task_screenshot');
                if (!$task_screenshot) return json($this->outJson(0,'请求参数不完整'));
                //# dataURI base_64 编码上传 手机端常用方式
                $rootPath = './uploads/assignment/' . date('Ymd');
                $target = $rootPath . "/" . date('Ymd') . uniqid() . ".jpg" ;
                if (!file_exists($rootPath)) {
                    cp_directory($rootPath);
                }
                $img = base64_decode($task_screenshot);
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
                $task_id = $this->request->param('task_id',0,'intval'); //23;
                $task_screenshot = $this->request->param('task_screenshot');    //'/uploads/assignment/20190119/201901195c42c50bf15eb.jpg';
                $model = new \app\api\model\Task();
                $data = $model->subTask($this->uid, $task_id, $task_screenshot);
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
     * 获取用户领取任务的历史记录(未调用)
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
     * 获取任务规则描述+图片（未调用）
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
