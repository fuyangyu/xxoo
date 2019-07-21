<?php
namespace app\admin\model;
use think\Db;
use think\Model;
use think\Request;
use think\Response;
class HireLog extends Base
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
        $obj = $this->where($where)->limit($start, $limit)->order(['add_time' => 'desc'])->select();
        $data = $obj->toArray();
        if ($data) {
            $uid_s = [];
            foreach ($data as $k => $v) {
                if ($v['lower_uid'] > 0) {
                    array_push($uid_s,$v['lower_uid']);
                }
                if ($v['uid'] > 0) {
                    array_push($uid_s,$v['uid']);
                }
            }
            $temp_name_arr = [];
            $uid_data = array_unique($uid_s);
            if ($uid_data) {
                $res = Db::name('member')->where(['uid' => ['in',$uid_data]])->field('uid,phone')->select();
                foreach ($res as $k => $v) {
                    $temp_name_arr[$v['uid']] = $v['phone'];
                }
            }

            foreach ($data as $k => $v) {

                if ($v['hire_type'] == '会员收费' && $v['order_sn']) {
                    $data[$k]['content'] = '充值订单号:' . $v['order_sn'];
                }
                if ($v['hire_type'] == '会员收费' && !$v['order_sn']) {
                    $data[$k]['content'] = '后台升级服务中心:线下充值';
                }
                if ($v['hire_type'] == '广告任务') {
                    $data[$k]['content'] = '任务日志ID:' . $v['type_log_id'];
                }
                if ($v['hire_type'] == '静态分佣') {
                    $data[$k]['content'] = '静态分佣:系统创建';
                }

                if (isset($temp_name_arr[$v['uid']])) {
                    $data[$k]['uid_phone'] = $temp_name_arr[$v['uid']];
                }

                if (isset($temp_name_arr[$v['lower_uid']])) {
                    $data[$k]['lower_uid_phone'] = $temp_name_arr[$v['lower_uid']];
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
    public function getUserLevelAttr($value)
    {
        $html = '';
        switch ($value) {
            case 1:
                $html = '普通会员';
                break;
            case 2:
                $html = '普通VIP';
                break;
            case 3:
                $html = '高级VIP';
                break;
            case 4:
                $html = '服务中心';
                break;
        }
        return $html;
    }

    // 获取器
    public function getHireTypeAttr($value)
    {
        $html = '';
        switch ($value) {
            case 1:
                $html = '会员收费';
                break;
            case 2:
                $html = '广告任务';
                break;
            case 3:
                $html = '静态分佣';
                break;
        }
        return $html;
    }

    protected function getIsCheckAttr($value)
    {
        if ($value > 0) {
            return '已发放';
        } else {
            return '待发放';
        }
    }


    protected function getAddTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i:s',$value);
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
        $userLevel = isset($where['userLevel']) ? $where['userLevel'] : 0;
        $type = isset($where['type']) ? $where['type'] : 0;
        $check = isset($where['check']) ? $where['check'] : 9;
        $where = [];
        if ($keywords) {
            $where['id'] = ['like',"%{$keywords}%"];
        }
        if ($start_time && !$end_time) {
            $where['add_time'] = ['>=',strtotime($start_time)];
        }
        if (!$start_time && $end_time) {
            $where['add_time'] = ['<=',strtotime($end_time)];
        }
        if ($start_time && $end_time) {
            $where['add_time'] = ['between',[strtotime($start_time),strtotime($end_time)]];
        }
        if ($userLevel > 0) {
            $where['user_level']  = $userLevel;
        }
        if ($type > 0) {
            $where['hire_type']  = $type;
        }
        if ($check != 9) {
            $where['is_check']  = $check;
        }
        return $where;
    }
}
