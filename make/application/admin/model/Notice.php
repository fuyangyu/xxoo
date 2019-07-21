<?php
namespace app\admin\model;
use think\Db;
use think\Request;
use think\Response;
use think\Session;

class Notice extends Base
{
    //指定主键
    protected $pk = 'id';

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

    protected function getIsShowAttr($value)
    {
        if ($value == 1) {
            return '展示中';
        } else {
            return '已关闭';
        }
    }

    /*
     * 处理添加编辑
     */
    public function store($data)
    {
        $id = $data['id'];
        $init = [
            'title' => trim($data['title']),
            'content' => request()->param('content'),
            'is_index' => trim($data['is_index']),
            'is_show' => trim($data['is_show'])
        ];
        if (!$init['title']) {
            return $this->outJson(1,'标题必须不能为空!');
        }
        if (!$init['content']) {
            return $this->outJson(1,'内容必须不能为空!');
        }
        if ($id > 0) {
            // 编辑
            Db::name('notice')->where(['id' => $id])->update($init);
            return $this->outJson(0,'操作成功!');
        } else {
            // 新增
            $init['add_time'] = date('Y-m-d H:i:s');
            $init['username'] = Session::get('admin')['username'];
            $id = Db::name('notice')->insertGetId($init);
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
        $where = [];
        if ($keywords) {
            $where['title'] = ['like',"%{$keywords}%"];
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
