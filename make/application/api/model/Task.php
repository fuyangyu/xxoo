<?php
namespace app\api\model;
use think\Db;
use think\Response;
class Task extends Base
{
    //指定主键
    protected $pk = 'task_id';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    // 领取任务的间隔
    protected $incision = 24;

    /**
     * 任务大厅-获取任务列表
     * @param $uid
     * @param $cid
     * @return array
     */
    public function getIdData($uid, $cid)
    {
        $data  = Db::name('task')
                    ->where(['task_cid' => $cid])
                    ->field('task_id,title,limit_total_num,get_task_num,task_user_level,task_money')
                    ->order('task_id','desc')
                    ->select();
        $temp = [];
        if ($data) {
            $tid_s = [];
            foreach ($data as $k => $v) {
                $tid_s[] = $v['task_id'];
            }
            // 检测当前用户是否已经领取了 该项任务
            $task_log_data = Db::name('send_task_log')->where(['uid' => $uid, 'task_id' => ['in',$tid_s]])->field('task_id')->select();
            $task_log_arr = [];
            if ($task_log_data) {
                // 存在数据 进行重新赋值
                foreach ($task_log_data as $k => $v) {
                    $task_log_arr[$v['task_id']] = 1;
                }
            }
            // 判断是否有领取 + 是否已领取完毕
            foreach ($data as $k => $v) {
                $temp[$k]['tid'] = $v['task_id'];
                $temp[$k]['title'] = $v['title'];
                $temp[$k]['limit_total_num'] = $v['limit_total_num'];
                $temp[$k]['get_task_num'] = $v['get_task_num'];
                $temp[$k]['task_money'] = $v['task_money'];
                $temp[$k]['user_level_name'] = $this->getTaskUserLevelAttr($v['task_user_level']);
                // 判断是否已领取完毕
                if ($v['limit_total_num'] == $v['get_task_num']) {
                    // 已领取完
                    $temp[$k]['get_total_status'] = 1;
                } else {
                    // 未完
                    $temp[$k]['get_total_status'] = 0;
                }
                // 判断是否有领取
                if ($task_log_arr) {
                    if (isset($task_log_arr[$v['task_id']])) {
                        // 当前用户已领取
                        $temp[$k]['is_get'] = 1;
                    } else {
                        $temp[$k]['is_get'] = 0;
                    }
                } else {
                    $temp[$k]['is_get'] = 0;
                }
            }
        }
        return $temp;
    }


    /**
     * 提交任务大厅-获取某个指定会员等级的任务列表数据
     * @param $uid
     * @param $cid
     * @return array
     */
    public function getSubAppointTaskData($uid, $cid)
    {
        $data  = Db::name('send_task_log')
            ->where(['task_cid' => $cid, 'uid' => $uid,'is_check' => 0])
            ->field('id,task_id,title,task_user_level,task_money')
            ->order('id','desc')
            ->select();
        $temp = [];
        if ($data) {
            foreach ($data as $k => $v) {
                $temp[$k]['id'] = $v['id'];
                $temp[$k]['tid'] = $v['task_id'];
                $temp[$k]['title'] = $v['title'];
                $temp[$k]['user_level_name'] = $this->getTaskUserLevelAttr($v['task_user_level']);
                $temp[$k]['task_money'] = $v['task_money'];
            }
        }
        return $temp;
    }

    /**
     * 提交任务大厅 - 获取任务单条记录(暂未调用)
     * @param $id
     * @return array
     */
    public function getSubFindTaskData($id)
    {
        $data = Db::name('send_task_log')
            ->alias('s')
            ->join('wld_task w','w.task_id = s.task_id')
            ->where(['s.id' => $id])
            ->field('s.id,s.task_id,s.title,w.content,w.img_url')
            ->find();
        $temp = [];
        if ($data) {
            $temp['id'] = $data['id'];
            $temp['tid'] = $data['task_id'];
            $temp['title'] = $data['title'];
            $temp['content'] = $data['content'];
            $temp['img'] = explode('@',$data['img_url']);
        }
        return $temp;
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

    /**
     * 获取任务单条记录
     * @param $tid
     * @return array
     */
    public function getFindData($tid,$uid=0)
    {
        $data = Db::name('task')
                ->where(['task_id' => $tid])
                ->field('task_id,title,content,img_url,task_money,task_step')
                ->find();
        $temp = [];
        if ($data) {
            $temp['tid'] = $data['task_id'];
            $temp['title'] = $data['title'];
            $temp['content'] = $data['content'];
            $temp['img'] = explode('@',$data['img_url']);
            $temp['money'] = $data['task_money'];
            if(!empty($data['task_step'])){
                $temp['step'] = $data['task_step']; //上传的任务规则
            }else{
                //获取统一任务规则
                $rule = cp_getCacheFile('system');
                $temp['task_rule_des'] = isset($rule['task_rule_des']) ? $rule['task_rule_des'] : '';   //规则描述
                $temp['task_rule_img'] = isset($rule['task_rule_img']) ? $rule['task_rule_img'] : '';   //规则图片流程介绍
            }
        }
        //任务驳回原因
        $task_log = Db::name('send_task_log')->where(['task_id'=>$tid,'uid'=>$uid])->field('failure_msg,id')->find();
        $temp['failure_msg'] = $task_log['failure_msg'];
        $temp['task_log_id'] = $task_log['id'];

        return $temp;
    }



    /**
     * 用户领取任务
     * @param $uid
     * @param $tid
     * @return array
     */
    public function drawTask($uid, $tid)
    {
        $check = Db::name('task')
                ->where(['task_id' => $tid,'status' => 1])
                ->field('task_id,title,limit_total_num,task_user_level,task_area,get_task_num,task_money,task_cid,task_user_level,is_area')
                ->find();

        // 判断任务库存
        if ($check['limit_total_num']  <= 0) {
            return $this->outJson(0,'呀！任务被抢光了！');
        }

        // 1.检测该任务的会员等级要求 匹配该用户的会员等级是否满足要求
        $user_level = Db::name('member')->where(['uid' => $uid])->value('member_class');
        $task_user_level = explode(',',$check['task_user_level']);
        if (!in_array($user_level,$task_user_level)) {
            return $this->outJson(0,'您的会员等级与该任务不匹配，无法进行领取!');
        }

        // 3.如果数据中存在区域数据 那么匹配该会员的区域是否属于与该任务的所规定的区域是否匹配
        if ($check['is_area'] == 1) {
            // 开启区域限制
            $info = Db::name('member_info')->where(['uid' => $uid])->field('province,city')->find();
            if (!$info) {
                return $this->outJson(0,'您的所属区域与该任务不匹配，无法进行领取!');
            } else {
                if ($check['task_area']) {
                    $area = json_decode($check['task_area'],true);
                    // 验证是否在同一个区域
                    if ($area['prov_id'] != $info['province'] && ($area['city_id'] != $info['city']) ){
                        return $this->outJson(0,'您的所属区域与该任务不匹配，无法进行领取!');
                    }
                }
            }
        }
        // 是否已领取该任务未完成
        $checkTaskLogData = Db::name('send_task_log')
            ->where(['task_id' => $tid, 'uid' => $uid])
            ->where('is_check','not in','1')
//            ->whereTime('add_time','today')
//            ->order('id','desc')
            ->find();
        if ($checkTaskLogData) {
            // 3-1）必须要审核通过
//            if ($checkTaskLogData['is_check'] != 1) {
                return $this->outJson(0,"该任务当天您已领取,等待平台审核中");
//            }
            // 3-2）必须要与审核通过该条任务间隔24个小
//            $pop_arr = array_pop($checkTaskLogData);
//            $incisionTime = $checkTaskLogData['add_time']+$this->incision * 3600;;
//            if (time() < $incisionTime) {   // 少于24个小时
//                return $this->outJson(0,"当日任务领取必须间隔{$this->incision}小时");
//            }
        }
        // 写库
        $init_insert = [
            'task_id' => $tid,
            'title' => $check['title'],
            'uid' => $uid,
            'task_money' => $check['task_money'],
            'add_time' => date('Y-m-d H:i:s'),
            'task_cid' => $check['task_cid'],
            'task_user_level' => $check['task_user_level'],
            'is_check' => 0 //0:领取 1：审核通过 2：待审核 3：审核失败
        ];

        // 启动事务
        Db::startTrans();
        try{
            $id = Db::name('send_task_log')->insertGetId($init_insert);
            //库存减一 领取任务数量加一
            $sql = "UPDATE wld_task SET get_task_num = get_task_num+1,limit_total_num=limit_total_num-1 WHERE task_id={$tid};";
            $bool = Db::execute($sql);
            if ($id && $bool) {
                // 提交事务
                Db::commit();
                return $this->outJson(1,"领取成功",['task_id'=>$tid]);
            } else {
                // 回滚事务
                Db::rollback();
                return $this->outJson(0,"领取失败");
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->outJson(0,"领取失败");
        }
    }

    /**
     *
     * @param $old
     * @param $info
     * @return array|bool
     */
    protected function diffArea($old, $info)
    {
        $province_id = Db::name('region')->where(['name' => trim($info['province']), 'level' => 1])->value('id');
        $city_id = Db::name('region')->where(['name' => trim($info['city']), 'level' => 2])->value('id');
        $district_id = Db::name('region')->where(['name' => trim($info['district']), 'level' => 3])->value('id');
        $new_province_id = $old['prov_id'];
        $new_city_id = $old['city_id'];
        $new_district_id = $old['dist_id'];
        if ($province_id != $new_province_id) {
            return $this->outJson(0,'您的所属区域与该任务不匹配，无法进行领取!');
        }
        if ($province_id == $new_province_id && $city_id != $new_city_id) {
            return $this->outJson(0,'您的所属区域与该任务不匹配，无法进行领取!');
        }
//        if ($province_id == $new_province_id && $city_id == $new_city_id && $district_id != $new_district_id) {
//            return $this->outJson(0,'您的所属区域与该任务不匹配，无法进行领取!');
//        }
        return true;
    }




    /**
     * 提交任务
     * @param $uid
     * @param $id
     * @param $img
     * @return array
     */
    public function subTask($id,$uid, $task_id, $img)
    {
        $old = Db::name('send_task_log')->where(['id'=>$id,'task_id' => $task_id, 'uid' => $uid])->where('is_check','in',[0,3])->field('id,is_check,add_time')->find();
        if (!$old) return $this->outJson(0, '参数不合法');
//        if($old['add_time']+86400 > time()) return $this->$this->outJson(0,'需要提供24小时以后的截图');
//        if ($old['is_check'] == 2) return $this->outJson(0, '该任务已经提交,耐心等待平台审核');
        if (!file_exists("." . $img)) return $this->outJson(0, '图片上传失败');
        // 修改任务数据
        $update = [
            'img' => $img,
            'is_check' => 2,
            'sub_time' => date('Y-m-d H:i:s')
        ];

        $offer = Db::name('send_task_log')->where(['task_id' => $task_id,'uid' =>$uid])->update($update);
        if ($offer) {
            return $this->outJson(1, '提交成功！我们会尽快审核，审核通过之后，任务收入会自动发放哦！');
        } else {
            return $this->outJson(0, '操作失败');
        }
    }
}
