<?php
namespace app\admin\model;
use think\Db;
use think\Model;
use think\Request;
use think\Response;
class PayLog extends Base
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
        $data = Db::name('pay_log')->where($where)->limit($start, $limit)->order(['id' => 'desc'])->select();
        if ($data) {
            $uid_s = [];
            foreach ($data as $k => &$v) {
                $uid_s[] = $v['uid'];
                $v['type'] = $this->getType($v['type'],$v['vip']);
            }
            $temp_name_arr = [];
            $res = Db::name('member')->where(['uid' => ['in',$uid_s]])->field('uid,phone')->select();
            foreach ($res as $k => $v) {
                $temp_name_arr[$v['uid']] = $v['phone'];
            }

            foreach ($data as $k => $v) {
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


    public function getType($type,$vip){
        $html = '';
        if($type == 1){
            $html = '充值'.$this->getVip($vip);
        }elseif($type == 2){
            $html = '续费'.$this->getVip($vip);
        }elseif($type == 3){
            $html = '升级'.$this->getVip($vip);
        }
        return $html;
    }
    public function getVip($vip){
        $html = '';
        switch ($vip) {
            case 2:
                $html = 'vip';
                break;
            case 3:
                $html = 'svip';
                break;
            case 4:
                $html = '服务中心';
                break;
        }
        return $html;
    }

    // 获取器
    public function getTypeAttr($value)
    {
        $html = '';
        switch ($value) {
            case 1:
                $html = '会员升级';
                break;
        }
        return $html;
    }

    // 获取器
    public function getPayModeAttr($value)
    {
        $html = '';
        switch ($value) {
            case 1:
                $html = '余额支付';
                break;
            case 2:
                $html = '支付宝';
                break;
            case 3:
                $html = '快捷支付';
                break;
            case 4:
                $html = '微信支付';
                break;
        }
        return $html;
    }

    protected function getPayStatusAttr($value)
    {
        $html = '';
        switch ($value) {
            case 1:
                $html = '待支付';
                break;
            case 2:
                $html = '已支付';
                break;
            case 3:
                $html = '支付失败';
                break;
        }
        return $html;
    }


    protected function getAddTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i:s',$value);
        } else {
            return '';
        }
    }

    protected function getPayTimeAttr($value)
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
        $pay_status = isset($where['pay_status']) ? $where['pay_status'] : 0;
        $type = isset($where['type']) ? $where['type'] : 0;
        $where = [];
        if ($keywords) {
            $where['order_sn'] = ['like',"%{$keywords}%"];
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
        if ($pay_status > 0) {
            $where['pay_status']  = $pay_status;
        }
        if ($type = 1) {
            $where['type']  = 1;
        }elseif($type = 2){
            $where['type']  = 2;
        }elseif($type = 3){
            $where['type']  = 3;
        }else{
            $where['type']  = 0;
        }
        return $where;
    }
}
