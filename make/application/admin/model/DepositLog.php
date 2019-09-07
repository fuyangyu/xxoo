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
        $data = Db::name('deposit_log')->where($where)->limit($start, $limit)->order(['id' => 'desc'])->select();
        if ($data) {
            foreach ($data as $k => &$v) {
                if($v['type'] == 1){    //银行卡
                    $bank = Db::name('bank_info')->where('uid',$v['uid'])->field('bank_account,user_name,bank_name')->find();
                    $data[$k]['account'] = $bank['bank_account'];
                    $data[$k]['name'] = $bank['user_name'];
                    $data[$k]['account_name'] = $bank['bank_name'];
                    $v['type'] = '银行卡';
                    $v['is_check'] = $this->checkAttrName($v['is_check']);
                    $v['depos_status'] = $this->deposStatus($v['depos_status']);

                }
                if($v['type'] == 2){    //支付宝
                   $alipay = Db::name('alipay_info')->where('uid',$v['uid'])->field('alipay,real_name')->find();
                    $data[$k]['account'] = $alipay['alipay'];
                    $data[$k]['name'] = $alipay['alipay'];
                    $data[$k]['account_name'] = '支付宝';
                    $v['type'] = '支付宝';
                    $v['is_check'] = $this->checkAttrName($v['is_check']);
                    $v['depos_status'] = $this->deposStatus($v['depos_status']);
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

    /**审核状态
     * @param $value
     * @return string
     */
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

    public function deposStatus($value){
        if ($value == 1) {
            return '提现中';
        }
        if ($value == 2) {
            return '提现成功';
        }
        if ($value == 3) {
            return '提现失败';
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
