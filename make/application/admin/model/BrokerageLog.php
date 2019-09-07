<?php
namespace app\admin\model;
use think\Db;
use think\Model;
use think\Request;
use think\Response;
class BrokerageLog extends Base
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
    public function getListData($param,$limit = 10)
    {
        $start_time = isset($param['start_time']) ? $param['start_time'] : '';
        $end_time = isset($param['end_time']) ? $param['end_time'] : '';
        $keywords = isset($param['keywords']) ? $param['keywords'] : '';
        $userLevel = isset($param['userLevel']) ? $param['userLevel'] : 0;
        $type = isset($param['type']) ? $param['type'] : 0;
        $check = isset($param['check']) ? $param['check'] : 9;
        $where = [];
        if ($keywords) {
            $where['b.id'] = ['like',"%{$keywords}%"];
        }
        if ($start_time && !$end_time) {
            $where['b.add_time'] = ['>=',strtotime($start_time)];
        }
        if (!$start_time && $end_time) {
            $where['b.add_time'] = ['<=',strtotime($end_time)];
        }
        if ($start_time && $end_time) {
            $where['b.add_time'] = ['between',[strtotime($start_time),strtotime($end_time)]];
        }
        if ($userLevel > 0) {
            $where['b.member_class']  = $userLevel;
        }

        if ($check != 9) {
            $where['is_check']  = $check;
        }

        $request = Request::instance();
        $page = $request->param('pageIndex', 1, 'int');
        $total = 0;
        $start = 0;
        if ($page != 1) {
            $start = ($page - 1) * $limit;
        }
        $condition = '';
        if ($type == 1) { //推荐佣金
            $condition  = 'b.brokerage_type = 1';
        }elseif($type == 2){    //任务佣金
            $condition  = 'b.brokerage_type = 2';
        }elseif($type == 3){    //渠道佣金
            $condition  = 'b.brokerage_type = 3';
        }
        if($type == 4){    //静态分佣
            $data = Db::name('channel_log')->alias('c')->join('message_log s', 'c.id = s.did', 'LEFT')->where('s.type',2)->order('id','desc')->field('c.*,s.content')->select();
            if($data){
                foreach($data as $k=>$v){
                    $data[$k]['money'] = $v['channel_money'];
                    $data[$k]['member_class'] = $this->getMemberClassAttr($v['member_class']);
                    $data[$k]['brokerage_type'] = '静态佣金';
                }
            }
            $total = Db::name('channel_log')->count();
        }else { //渠道 推荐 任务分佣
            $data = Db::name('brokerage_log')->alias('b')
                    ->join('message_log s', 'b.id = s.did', 'LEFT')->where($where)->where('s.type',1)->where($condition)->limit($start, $limit)
                    ->order(['b.add_time' => 'desc'])->field('b.*,s.content')->select();
            if($data){
                foreach ($data as $k=>&$item) {
                    if($item['brokerage_type'] == 1){
                        $item['brokerage_type'] = '推荐佣金';
                    }elseif($item['brokerage_type'] == 2){
                        $item['brokerage_type'] = '任务佣金';
                    }elseif($item['brokerage_type'] == 3){
                        $item['brokerage_type'] = '渠道佣金';
                    }
                    $data[$k]['member_class'] = $this->getMemberClassAttr($item['member_class']);
                }
                // 查询总记录数
                $total = Db::name('brokerage_log')->alias('b')
                    ->join('message_log s', 'b.id = s.did', 'LEFT')->where($where)->where('s.type',1)->count();
            }
        }
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

}
