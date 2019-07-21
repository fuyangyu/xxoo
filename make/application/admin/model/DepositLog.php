<?php
namespace app\admin\model;
use think\Db;
use think\Model;
use think\Request;
use think\Response;
class DepositLog extends Base
{
    //指定主键
    protected $pk = 'id';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    /**
     * 异步获取提现记录列表数据
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
                $uid_s[] = $v['uid'];
            }
            $temp_name_arr = [];
            $res = Db::name('bank_info')->where(['uid' => ['in',$uid_s]])->field('uid,bank_card_num')->select();
            foreach ($res as $k => $v) {
                $temp_name_arr[$v['uid']] = $v['bank_card_num'];
            }

            foreach ($data as $k => $v) {
                if (isset($temp_name_arr[$v['uid']])) {
                    $data[$k]['bank_info'] = $temp_name_arr[$v['uid']];
                } else {
                    $data[$k]['bank_info'] = '未绑定银行卡';
                }
                $data[$k]['check_name'] = $this->checkAttrName($v['is_check']);
            }

        }
        // 查询总记录数
        $total = $this->where($where)->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }


    protected function checkAttrName($value)
    {
        if ($value == 1) {
            return '审核成功';
        }
        if ($value == 2) {
            return '审核失败';
        }
        if ($value == 0) {
            return '待审核';
        }
    }

    protected function getAddTimeAttr($value)
    {
        if (strtotime($value) > 0) {
            return $value;
        } else {
            return '';
        }
    }

    protected function getCheckTimeAttr($value)
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
        $check = isset($where['check']) ? $where['check'] : 9;
        $where = [];
        if ($keywords) {
            $where['phone'] = ['like',"%{$keywords}%"];
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
        if ($check != 9) {
            $where['is_check']  = $check;
        }
        return $where;
    }
}
