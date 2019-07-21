<?php
namespace app\admin\model;
use think\Db;
use think\Request;
use think\Response;
use think\Session;

class Task extends Base
{
    //指定主键
    protected $pk = 'task_id';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    /**
     * 异步获取管理日志列表数据
     * @param array $where
     * @param int $limit
     * @return array
     */
    public function getListData($where = [],$limit=10)
    {
        $request = Request::instance();
        $page = $request->param('pageIndex',1,'int');
        $start = 0;
        if ($page != 1) {
            $start = ($page-1) * $limit;
        }
        $obj = $this->where($where)->limit($start,$limit)->order(['task_id'=>'desc'])->select();
        $data = $obj->toArray();
        $classify = [];
        if ($data) {
            foreach ($data as $k => $v) {
                $classify[] = $v['task_cid'];
            }
            $classData = Db::name('task_classify')->where(['task_cid' => ['in',$classify]])->field('task_cid,name')->select();
            $cate = [];
            if ($classData) {
                foreach ($classData as $k => $v) {
                    $cate[$v['task_cid']] = $v['name'];
                }
            }
            foreach ($data as $k => $v) {
                $data[$k]['id'] = $v['task_id'];
                if ($v['is_area'] == 0) {
                    $data[$k]['task_area'] = '全国';
                }
                if (isset($cate[$v['task_cid']])) {
                    $data[$k]['class_name'] = $cate[$v['task_cid']];
                } else {
                    $data[$k]['class_name'] = '';
                }
            }
        }
        // 查询总记录数
        $total = $this->where($where)->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }


    /**
     * 异步获取任务审核数据
     * @param array $where
     * @param int $limit
     * @param array $order
     * @return array
     */
    public function getDrawListData($where = [],$limit=10, $order = [])
    {
        $request = Request::instance();
        $page = $request->param('pageIndex',1,'int');
        $start = 0;
        if ($page != 1) {
            $start = ($page-1) * $limit;
        }
        $data = Db::name('send_task_log')->where($where)->limit($start,$limit)->order($order)->select();
        $classify = [];
        if ($data) {
            $uids = [];
            foreach ($data as $k => $v) {
                $classify[] = $v['task_cid'];
                $uids[] = $v['uid'];
            }
            $classData = Db::name('task_classify')->where(['task_cid' => ['in',$classify]])->field('task_cid,name')->select();
            $uData = Db::name('member')->where(['uid' => ['in',$uids]])->field('uid,phone')->select();
            $temp_uid_arr = [];
            if ($uData) {
                foreach ($uData as $k => $v) {
                    $temp_uid_arr[$v['uid']] = $v['phone'];
                }
            }
            $cate = [];
            if ($classData) {
                foreach ($classData as $k => $v) {
                    $cate[$v['task_cid']] = $v['name'];
                }
            }
            foreach ($data as $k => $v) {
                $data[$k]['task_user_level'] = $this->getTaskUserLevelAttr($v['task_user_level']);
                $data[$k]['check_status'] = $this->checkStatus($v['is_check']);
                $data[$k]['sub_time'] = $this->replaceTime($v['sub_time']);
                $data[$k]['check_time'] = $this->replaceTime($v['check_time']);
                if (isset($cate[$v['task_cid']])) {
                    $data[$k]['class_name'] = $cate[$v['task_cid']];
                } else {
                    $data[$k]['class_name'] = '';
                }

                if (isset($temp_uid_arr[$v['uid']])) {
                    $data[$k]['phone'] = $temp_uid_arr[$v['uid']];
                } else {
                    $data[$k]['phone'] = '';
                }
            }
        }
        // 查询总记录数
        $total = Db::name('send_task_log')->where($where)->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }

    /**
     * 任务状态
     * @param $value
     * @return string
     */
    protected function checkStatus($value)
    {
        $html = '';
        switch($value)
        {
            case 1:
                $html = '审核通过';
                break;
            case 0:
                $html = '已领取';
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

    protected function replaceTime($value)
    {
        if (strtotime($value) > 0) {
            return $value;
        } else {
            return '';
        }
    }

    /**
     * 审核时间
     * @param $value
     * @return bool|string
     */
    protected function checkTime($value)
    {
        if ($value) {
            return date('Y-m-d H:i:s',$value);
        } else {
            return '';
        }
    }

    // 创建时间 获取器
    protected function getAddTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i:s',$value);
        } else {
            return '';
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

    // 任务区域 获取器
    protected function getTaskAreaAttr($value)
    {
        if ($value) {
            $v = json_decode($value, true);
            return $v['prov'] . "_" . $v['city'] . "_" .  $v['dist'];
        } else {
            return '';
        }
    }

    // 更新时间 获取器
    protected function getUpdateTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i:s',$value);
        } else {
            return '';
        }
    }

    /*
     * 处理添加任务和编辑任务
     */
    public function store($data)
    {
        $id = $data['id'];
        $prov_id = Db::name('region')->where(['name' => trim($data['prov']),'level' => 1])->value('id');
        $city_id = Db::name('region')->where(['name' => trim($data['city']),'level' => 2])->value('id');
        $dist_id = Db::name('region')->where(['name' => trim($data['dist']),'level' => 3])->value('id');
        $task_area = [
            'prov' => trim($data['prov']),
            'city' => trim($data['city']),
            'dist' => trim($data['dist']),
            'prov_id' => $prov_id,
            'city_id' => $city_id,
            'dist_id' => $dist_id
        ];
        $is_area = isset($data['is_area']) ? 1 : 0;
        $init = [
            'title' => trim($data['title']),
            'content' => trim($data['content']),
            'is_user' => 2,
            'task_user_level' => implode(',',$data['task_user_level']),
            'task_money' => trim($data['task_money']),
            'task_type' => 1,
            'is_area' => $is_area,
            'limit_total_num' => trim($data['limit_total_num']),
//            'limit_user_num' => trim($data['limit_user_num']),
            'task_area' => json_encode($task_area),
            'task_cid' => trim($data['task_cid']),
            'img_url' => trim($data['img_url']),
            'issue_name' => Session::get('admin')['username'],
            'issue_uid' => Session::get('admin')['uid']
        ];
        if ($id > 0) {
            // 编辑
            $init['update_time'] = time();
            Db::name('task')->where(['task_id' => $id])->update($init);
            return $this->outJson(0,'编辑成功!');
        } else {
            // 新增 任务
            $init['add_time'] = time();
            $id = Db::name('task')->insertGetId($init);
            if ($id) {
                return $this->outJson(0,'发布成功!',['id' => $id]);
            }  else {
                return $this->outJson(1,'发布失败!');
            }
        }
    }

    /**
     * 检测并且过滤搜索条件
     * @param array $where
     * @return array
     */
    public function filtrationWhere($where = [])
    {
        if (!$where) return [];
        $start_time = isset($where['start_time']) ? $where['start_time'] : '';
        $end_time = isset($where['end_time']) ? $where['end_time'] : '';
        $keywords = isset($where['keywords']) ? $where['keywords'] : '';
        $userLevel = isset($where['userLevel']) ? $where['userLevel'] : 0;
        $cid = isset($where['cid']) ? $where['cid'] : 0;
        $where = [];
        if ($keywords) {
            if (is_numeric($keywords)) {
                $where['id'] = $keywords;
            } else {
                $where['title'] = ['like',"%{$keywords}%"];
            }
        }
        if ($start_time && !$end_time) {
            $where['add_time'] = ['>=',$start_time];
        }
        if (!$start_time && $end_time) {
            $where['add_time'] = ['<=',$end_time];
        }
        if ($start_time && $end_time) {
            $where['add_time'] = ['between',[$start_time,$end_time]];
        }
        if ($userLevel > 0) {
            $where['task_user_level'] = ['like',"%{$userLevel}%"];
        }
        if ($cid > 0) {
            $where['task_cid'] = $cid;
        }
        return $where;
    }


    /**
     * 移除任务分类
     * @param $ids
     * @return bool
     */
    public function del($ids)
    {
        // 启动事务
        $this->startTrans();
        try{
            $this->where(['task_id' => ['in',$ids]])->delete();
            // 提交事务
            $this->commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            $this->rollback();
            $this->error = '系统繁忙,稍后再试...';
            return false;
        }
    }
}
