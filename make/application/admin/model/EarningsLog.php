<?php
namespace app\admin\model;
use think\Db;
use think\Model;
use think\Request;
use think\Response;
class EarningsLog extends Base
{
    //指定主键
    protected $pk = 'id';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    /**
     * 异步获取佣金记录列表数据
     * @param array $where
     * @param int $limit
     * @return array
     */
    public function getListData($where = [], $limit = 10)
    {
        $request = Request::instance();
        $page = $request->param('pageIndex', 1, 'int');
        $start = 0;
        if ($page != 1) {
            $start = ($page - 1) * $limit;
        }
        $obj = $this->where($where)->limit($start, $limit)->order(['id' => 'desc'])->select();
        $data = $obj->toArray();
        if ($data) {
            $uid_s = [];
            foreach ($data as $k => $v) {
                if ($v['uid'] > 0) {
                    array_push($uid_s,$v['uid']);
                }
            }
            $temp_name_arr = [];
            if ($uid_s) {
                $res = Db::name('member')->where(['uid' => ['in',$uid_s]])->field('uid,phone')->select();
                foreach ($res as $k => $v) {
                    $temp_name_arr[$v['uid']] = $v['phone'];
                }
            }

            foreach ($data as $k => $v) {
                $data[$k]['type_name'] = $this->getTypeNameAttr($v['type']);
                if ($v['type'] == 1) {
                    $data[$k]['content'] = '充值订单号:' . $v['order_sn'];
                } else {
                    $data[$k]['content'] = '任务日志ID-' . $v['task_log_id'];
                }
                if (isset($temp_name_arr[$v['uid']])) {
                    $data[$k]['uid_phone'] = $temp_name_arr[$v['uid']];
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



    // 获取器
    public function getTypeNameAttr($value)
    {
        $html = '';
        switch ($value) {
            case 1:
                $html = '会员收费';
                break;
            case 2:
                $html = '广告任务';
                break;
        }
        return $html;
    }

    protected function getAddTimeAttr($value)
    {
        if (strtotime($value) > 0) {
            return $value;
        } else {
            return '';
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
        $type = isset($where['type']) ? $where['type'] : 0;
        $where = [];
        if ($keywords) {
            $where['id'] = ['like',"%{$keywords}%"];
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
        if ($type > 0) {
            $where['type']  = $type;
        }
        return $where;
    }
}
