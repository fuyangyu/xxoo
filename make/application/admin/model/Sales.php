<?php
namespace app\admin\model;
use think\Db;
use think\Request;
use think\Response;
use think\Session;

class Sales extends Base
{
    //指定主键
    protected $pk = 'id';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    // 指定表名
    protected $table = 'wld_allot_log';

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
        $obj = $this->where($where)->limit($start,$limit)->order(['id'=>'desc'])->select();
        $data = $obj->toArray();
        // 查询总记录数
        $total = $this->where($where)->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }

    // 创建时间 获取器
    protected function getAddTimeAttr($value)
    {
        if ($value) {
            return $value;
        } else {
            return '';
        }
    }

    protected function getAllotOneAttr($value)
    {
        if ($value) {
            return $value . "%";
        } else {
            return '';
        }
    }

    protected function getAllotTwoAttr($value)
    {
        if ($value) {
            return $value . "%";
        } else {
            return '';
        }
    }

    protected function getAllotThreeAttr($value)
    {
        if ($value) {
            return $value . "%";
        } else {
            return '';
        }
    }


    protected function getInfiniteAttr($value)
    {
        if ($value) {
            return $value . "%";
        } else {
            return '';
        }
    }

    protected function getCarryIndexAttr($value)
    {
        if ($value) {
            return $value . "%";
        } else {
            return '';
        }
    }

    /**
     * 会员等级 获取器
     * @param $value
     * @return string
     */
    protected function getUserLevelAttr($value)
    {
        $html = '';
        switch($value)
        {
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


    /**
     * 所属业务
     * @param $value
     * @return string
     */
    protected function getChargeTypeAttr($value)
    {
        $html = '';
        switch($value)
        {
            case 1:
                $html = '会员收费';
                break;
            case 2:
                $html = '广告任务';
                break;
            case 3:
                $html = '广告业务';
                break;
        }
        return $html;
    }

    /*
     * 处理添加编辑
     */
    public function store($data=array())
    {
        if (!empty($data['id'])) {  // 编辑
            $init = [
                'allot_one' => trim($data['allot_one']),
                'allot_two' => trim($data['allot_two']),
                'charge_type' => trim($data['charge_type']),
                'user_level' => trim($data['user_level']),
                'team_one' => trim($data['team_one']),
                'team_two' => trim($data['team_two']),
            ];

            if(Db::name('allot_log')->where(['id' => $data['id']])->update($init)){
                return $this->outJson(0,'操作成功!');
            }
        } else {
            // 新增
            $init['add_time'] = date('Y-m-d H:i:s');
            $check = Db::name('allot_log')
                    ->where(['user_level' => $init['user_level'],'charge_type' => $init['charge_type']])
                    ->find();
            if ($check) return $this->outJson(1,'无法为您添加重复业务数据!');
            $id = Db::name('allot_log')->insertGetId($init);
            if ($id) {
                return $this->outJson(0,'操作成功!',['id' => $id]);
            }  else {
                return $this->outJson(1,'操作失败!');
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
        $type = isset($where['type']) ? $where['type'] : 0;
        $where = [];
        if ($keywords) {
            if (is_numeric($keywords)) {
                $where['id'] = ['like',"%{$keywords}%"];
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
            $where['user_level'] = $userLevel;
        }
        if ($type > 0) {
            $where['charge_type'] = $type;
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
            $this->where(['id' => ['in',$ids]])->delete();
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
