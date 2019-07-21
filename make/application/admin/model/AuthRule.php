<?php
/**
 * 权限规则表模型
 */
namespace app\admin\model;
use think\Model;
use think\Request;
class AuthRule extends Model
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

    //自定义初始化
    protected function initialize()
    {
        parent::initialize();
    }

    //设置 写入时间获取器
    protected function getCreateTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d',$value);
        } else {
            return '无';
        }
    }

    //设置 更新时间获取器
    protected function getUpdateTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d',$value);
        } else {
            return '无';
        }
    }

    /**
     * 获取所有数据 自定义分页
     * @param array $where
     * @return array
     */
    public function getListData($where = [],$status = false, $field = [],$limit=12)
    {
        $request = Request::instance();
        if ($status) {
            // 执行
            return $this->getSearchData($where,$field,$limit);
        }
        $data = $this->where($where)->field($field)->select()->toArray();
        if ($data) {
            //获取树形结构数据
            $treeData = \cocolait\helper\CpData::tree($data, 'title','id');
            $listData = array_values($treeData);
            $total = count($listData);
            $tempArray = [];
            $page = $request->param('page',1,'int');
            for ($i=($page-1)*$limit;$i <= $page*$limit;$i++){
                if ($i < $total) {
                    $tempArray[] = $listData[$i];
                }
            }
            $newData = [];
            foreach($tempArray as $k=>$v){
                if ($v) {
                    $newData[] = $v;
                }
            }
            $result = [
                'page' => $this->paginate($limit)->render(),
                'data' => $newData
            ];
            return $result;
        } else {
            return [
                'page' => '',
                'data' => []
            ];
        }
    }

    /**
     * 搜索时调用 特殊处理
     * @param array $where
     * @param array $field
     * @param int $limit
     * @return array
     */
    protected function getSearchData($where = [],$field = [],$limit=12)
    {
        $request = Request::instance();
        $obj = $this->where($where)->field($field)->paginate($limit,false,[
            'query' => $request->param()
        ]);

        if ($obj) {
            $data = $obj->toArray();
            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['_name'] = $v['title'];
                if (!$v['pid']) {
                    $data['data'][$k]['_level'] = 1;
                } else {
                    $data['data'][$k]['_level'] = 2;
                }
            }
            return [
                'data' => $data['data'],
                'page' => $obj->render()
            ];
        } else {
            return [
                'data' => [],
                'page' => '',
            ];
        }
    }

    /**
     * 获取权限递归菜单
     * @return array
     */
    public function getTree()
    {
        $data = [];
        $obj = $this->select();
        if ($obj) {
            $data = $obj->toArray();
            return \cocolait\helper\CpData::node_merge($data);
        }
        return $data;
    }

    /**
     * 添加|修改 权限
     * @return bool|mixed
     */
    public function insertData()
    {
        $request = Request::instance();
        $data = $request->param();
        //验证
        $validate = new \app\admin\validate\AuthRule();
        if (!$vdata = $validate->scene('store')->check($data)) {
            $this->error = $validate->getError();
            return false;
        }
        $data['status'] = isset($data['status']) ? 1 : 0;
        //处理数据
        if (!strrpos($data['name'],'/')) {
            $this->error = '权限规则格式错误';
            return false;
        }
        if ($data['init_id']) {
            // TODO 更新
            // 修改权限
            $name = $this->where(['id' => $data['init_id']])->value('name');
            if ($name != $data['name']) {
                //检测权限规则唯一性
                if ($this->where(['name' => $data['name']])->value('name')) {
                    $this->error = '权限规则已被注册';
                    return false;
                }
            }
            // 页面存在disabled属性时防止post提交pid属性值丢失
            $pid_s = isset($data['pid']) ? $data['pid'] : -1;
            if ($pid_s != '-1') {
                $pid_data = explode('_',$data['pid']);
                if ($pid_data[1] == 3) {
                    $this->error = '不能在三级菜单权限下添加子权限哦';
                    return false;
                }
                $data['pid'] = $pid_data[0];
            } else {
                $data['pid'] = 0;
            }

            $this->startTrans();
            try{
                $this->allowField(true)->isUpdate(true)->save($data,[
                    'id' => $data['init_id']
                ]);
                // 提交事务
                $this->commit();
                return true;
            } catch (\Exception $e) {
                // 回滚事务
                $this->rollback();
                $this->error = '系统繁忙,稍后再试...';
                return false;
            }
        } else {
            // TODO 新增
            //检测权限规则唯一性
            if ($this->where(['name' => $data['name']])->value('name')) {
                $this->error = '权限规则已被注册';
                return false;
            }
            $pid_data = explode('_',$data['pid']);
            if ($pid_data[1] == 3) {
                $this->error = '不能在三级菜单权限下添加子权限哦';
                return false;
            }
            $data['pid'] = $pid_data[0];
            $this->startTrans();
            try{
                $this->allowField(true)->isUpdate(false)->save($data);
                // 提交事务
                $this->commit();
                return $this->id;
            } catch (\Exception $e) {
                // 回滚事务
                $this->rollback();
                $this->error = '系统繁忙,稍后再试...';
                return false;
            }
        }
    }

    /**
     * 删除权限
     * @param int $id 权限ID
     * @return bool
     */
    public function delRule($id)
    {
        $this->startTrans();
        try{
            $this->where(['id'=> $id])->delete();
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

    /**
     * 检测并且过滤搜索条件
     * @param $keywords
     * @return array
     */
    public function filtrationWhere($keywords)
    {
        $keywords = isset($keywords['keywords']) ? $keywords['keywords'] : '';
        $where = [];
        if ($keywords) {
            if (is_numeric($keywords)) {
                $where['id'] = $keywords;
                return $where;
            }
            if (strrpos($keywords,'/')) {
                $where['name'] = ['like',"%{$keywords}%"];
                return $where;
            }
            $where['title'] = ['like',"%{$keywords}%"];
        }
        return $where;
    }
}
