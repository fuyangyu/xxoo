<?php
namespace app\admin\model;
use think\Model;
use think\Request;
use think\Response;
class AdminLog extends Model
{
    //指定主键
    protected $pk = 'id';

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = '';

    // 自动处理日志写入
    public static function record($params)
    {
        $jsonStr = $params->getContent();
        $data = $jsonStr ? json_decode($jsonStr,true) : [];
        if ($data) {
            if (isset($data['code']) && $data['code'] == 0) {
                $admin = isLogin();
                $admin_id = $admin ? $admin['uid'] : 0;
                $username = $admin ? $admin['username'] : '';

                $content = request()->param();
                foreach ($content as $k => $v)
                {
                    if (is_string($v) && strlen($v) > 200 || stripos($k, 'password') !== false)
                    {
                        unset($content[$k]);
                    }
                }
                $msg = isset($data['msg']) ? $data['msg'] : '';
                $title = isset($data['data']['logMsg']) ? $data['data']['logMsg'] : $msg;

                self::create([
                    'title'     => $title,
                    'content'   => !is_scalar($content) ? json_encode($content) : $content,
                    'url'       => request()->url(),
                    'admin_id'  => $admin_id,
                    'username'  => $username,
                    'useragent' => request()->server('HTTP_USER_AGENT'),
                    'ip'        => request()->ip()
                ]);
            }
        }
    }

    /**
     * 获取数据 系统分页调用法
     * @param array $where 查询条件
     * @param int $limit 一页显示多少条记录
     * @return array
     */
    public function getListData($where = [],$limit=10)
    {
        $request = Request::instance();

        $obj = $this->where($where)->order(['create_time'=>'desc'])->paginate($limit,false,[
            'query' => $request->param(),
        ]);

        $data = $obj->toArray();

        return [
            'data' => $data['data'],
            'page' => $obj->render(),
            'per_page' => $data['per_page']
        ];
    }

    /**
     * 自定义分页类
     * @param array $where
     * @param int $limit
     * @return array
     */
    public function getCustomListData($where = [],$limit=10)
    {
        $request = Request::instance();
        $page = $request->param('page',1,'int');
        $start = 0;
        if ($page != 1) {
            $start = ($page-1) * $limit;
        }
        $obj = $this->where($where)->limit($start,$limit)->order(['create_time'=>'desc'])->select();
        $data = $obj->toArray();
        // 查询总记录数
        $total = $this->where($where)->count();
        $pageNum = 0;
        // 计算总页数
        if ($total > 0) {
            $pageNum = ceil($total/$limit);
        }
        $pageShow = \cocolait\bootstrap\page\Send::instance(['total'=>$total,'limit' => $limit])->render($page,$pageNum,$request->param());
        return [
            'data' => $data,
            'page' => $pageShow,
            'total' => $total
        ];
    }

    /**
     * 异步获取管理日志列表数据
     * @param array $where
     * @param int $limit
     * @return array
     */
    public function getCustomNewListData($where = [],$limit=10)
    {
        $request = Request::instance();
        $page = $request->param('pageIndex',1,'int');
        $start = 0;
        if ($page != 1) {
            $start = ($page-1) * $limit;
        }
        $obj = $this->where($where)->limit($start,$limit)->order(['create_time'=>'desc'])->select();
        $data = $obj->toArray();
        // 查询总记录数
        $total = $this->where($where)->count();

        return [
            'rows' => $data,
            'total' => $total
        ];
    }

    // 获取器
    public function getCreateTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i:s',$value);
        } else {
            return '无';
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
            if (cp_is_ip($keywords)) {
                $where['ip'] = $keywords;
            }
            if (is_numeric($keywords)) {
                $where['admin_id'] = $keywords;
            }
            if (is_string($keywords) && !is_numeric($keywords) && !cp_is_ip($keywords)) {
                $where['username'] = ['like',"%{$keywords}%"];
            }
        }
        if ($start_time && !$end_time) {
            $where['create_time'] = ['>=',strtotime($start_time)];
        }
        if (!$start_time && $end_time) {
            $where['create_time'] = ['<=',strtotime($end_time)];
        }
        if ($start_time && $end_time) {
            $where['create_time'] = ['between',[strtotime($start_time),strtotime($end_time)]];
        }
        return $where;
    }


    /**
     * 移除管理日志
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
