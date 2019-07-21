<?php
/**
 * 用户组表管理
 */
namespace app\admin\model;
use think\Model;
use think\Request;

class AuthGroup extends Model
{
    //指定主键
    protected $pk = 'id';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    // 开启时间字段自动写入
    protected $autoWriteTimestamp = 'int';
    // 定义时间字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $modelAction;

    //自定义初始化
    protected function initialize()
    {
        parent::initialize();
        $this->modelAction = request()->module() . '/' . request()->controller() . '/' . request()->action();
    }

    protected function getCreateTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i:s',$value);
        } else {
            return '无';
        }
    }

    protected function getUpdateTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i:s',$value);
        } else {
            return '无';
        }
    }

    protected function getStatusAttr($value)
    {
        $str = '';
        switch ($value) {
            case 0:
                $str = '禁用';
                break;
            case 1:
                $str = '正常';
                break;
        }
        return $str;
    }

    /**
     * 获取所有数据
     * @param array $where
     * @param array $field
     * @param int $limit
     * @return array
     */
    public function getListData($where = [], $field = [], $limit = 10)
    {
        $request = Request::instance();

        $obj = $this->where($where)->field($field)->paginate($limit,false,[
            'query' => $request->param()
        ]);

        if ($obj) {
            $data = $obj->toArray();
            return [
                'data' => $data['data'],
                'page' => $obj->render(),
                'per_page' => $data['per_page']
            ];
        } else {
            return [
                'data' => [],
                'page' => '',
                'per_page' => 0
            ];
        }
    }

    /**
     * 处理用户组新增和修改
     * @return bool|false|int
     */
    public function insertData()
    {
        $data = Request::instance()->param();
        $id = Request::instance()->param('init_id',0);
        $data['ids'] = isset($data['ids']) ? $data['ids'] : [];
        $data['rules'] = implode(',',$data['ids']);
        $data['title'] = trim($data['title']);

        $validate = new \app\admin\validate\AuthGroup();
        if (!$vData = $validate->scene('insert')->check($data)) {
            $this->error = $validate->getError();
            return false;
        }
        if (!$data['ids']) {
            $this->error = '请勾选权限';
            return false;
        }
        if ($id) {
            // 更新用户组-权限
            $data['id'] = $data['init_id'];
            $is_user = $this->where(['title' => $data['title'],'id' => ['<>',$data['id']]])->find();
            if ($is_user) {
                $this->error = '该角色组名称已存在';
                return false;
            }
            // 启动事务
            $this->startTrans();
            try{
                $this->isUpdate(true)->allowField(true)->save($data);
                $this->commit();
                return true;
            } catch (\Exception $e) {
                // 回滚事务
                $this->rollback();
                $this->error = '系统繁忙,稍后再试~';
                return false;
            }
        } else {
            // 新增用户组-权限
            $is_user = $this->where(['title' => $data['title']])->find();
            if ($is_user) {
                $this->error = '该角色组名称已存在';
                return false;
            }
            // 启动事务
            $this->startTrans();
            try{
                $this->data($data)->allowField(true)->isUpdate(false)->save();
                $this->commit();
                return $this->id;
            } catch (\Exception $e) {
                // 回滚事务
                $this->rollback();
                $this->error = '系统繁忙,稍后再试~';
                return false;
            }
        }
    }


    /**
     * 删除数据
     * @param $id
     * @return int
     */
    public function groupDelete($id)
    {
        // 启动事务
        $this->startTrans();
        try{
            $this->where(['id'=>$id])->delete();
            $this->commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            $this->rollback();
            $this->error = '系统繁忙,稍后再试~';
            return false;
        }
    }
}
