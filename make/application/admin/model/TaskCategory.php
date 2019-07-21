<?php
namespace app\admin\model;
use think\Model;
use think\Request;
use think\Response;
class TaskCategory extends Model
{
    //指定主键
    protected $pk = 'task_cid';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    // 指定表名
    protected $table = 'wld_task_classify';

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
        $obj = $this->where($where)->limit($start,$limit)->order(['task_cid'=>'desc'])->select();
        $data = $obj->toArray();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['id'] = $v['task_cid'];
            }
        }
        // 查询总记录数
        $total = $this->where($where)->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }

    /*
     * 添加|编辑任务分类
     */
    public function store($data)
    {
        $id = $data['task_cid'];
        $sw = [
            'name' => trim($data['name']),
            'sort' => trim($data['sort']) ? trim($data['sort']) : 0
        ];
        if (!$sw['name']) {
            $this->error = '分类名称不能为空';
            return false;
        }
        if ($id > 0) {
            // 编辑
            $this->update($sw,['task_cid' => $id]);
            return true;
        } else {
            $sw['add_time'] = date('Y-m-d H:i:s');
            $this->isUpdate(false)->save($sw);
            return $this->task_cid;
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
            $where['name'] = ['like',"%{$keywords}%"];
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
     * 获取页面渲染数据
     * @return array
     */
    public function getHtmlList()
    {
        return $this->field('task_cid,name')->select()->toArray();
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
            $this->where(['task_cid' => ['in',$ids]])->delete();
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
