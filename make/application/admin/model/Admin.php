<?php

namespace app\admin\model;

use think\Db;
use think\Model;
use think\Request;
use think\Session;

class Admin extends Model
{
    //指定主键
    protected $pk = 'uid';

    // 请求体
    protected $request;

    //设置了模型的数据集返回类型
    protected $resultSetType = 'collection';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 加密字符秘钥
    protected $salt;

    //自定义初始化
    protected function initialize(Request $request = null)
    {
        parent::initialize();
        $this->salt = md5('wld-admin');
        $this->request = is_null($request) ? Request::instance() : $request;
    }

    protected function getLoginTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i',$value);
        } else {
            return '无';
        }
    }

    public function getIsLockAttr($value)
    {
        $str = '';
        switch ($value) {
            case 1:
                $str = '正常';
                break;
            case 2:
                $str = '禁用';
                break;
        }
        return $str;
    }

    /**
     * 多对多-关联模型
     * 获取用户所属的用户组信息
     */
    public function authGroups()
    {
        return $this->belongsToMany('AuthGroup', 'auth_group_access','group_id','uid');
    }

    /**
     * 查询所有的数据
     * @param array $where
     * @param array $field
     * @param int $limit
     * @return array
     */
    public function getListData($where = [], $field = [], $limit = 10)
    {
        if (!$field) {
            $field = ['uid','username','email','nickname','is_lock','last_login_ip','login_time'];
        }
        $request = Request::instance();
        $obj = $this->relation(['authGroups' => function ($query) {
            $query->withField('*');
        }])->where($where)->field($field)->paginate($limit,false,[
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
     * 获取角色id
     * @param $uid
     * @return mixed
     */
    public function getGroupId($uid)
    {
        return Db::name('auth_group_access')->where(['uid' => $uid])->value('group_id');
    }

    //执行登录
    public function sendLogin() {
        $validate = new \app\admin\validate\Admin();
        $data = $this->request->param();
        $vdata = $validate->scene('login')->check($data);
        if ($vdata == false) {
            $this->error = $validate->getError();
            return false;
        }
        $where = ['username' => trim($data['username'])];
        $obj = $this->where($where)->find();
        if ($obj->is_lock == 2) {
            $this->error = '用户已被锁定,请联系管理员!';
            return false;
        }

        if (!$obj) {
            $this->error = '用户名或密码错误 ^_^';
            return false;
        }

        if ($this->encryptPassword($data['password']) != $obj->password) {
            $this->error = '用户名或密码错误 ^_^';
            return false;
        }
        // 启动事务
        $this->startTrans();
        try{
            $this->isUpdate(true)->save([
                'last_login_ip'=> \cocolait\helper\CpGet::get_client_ip(0,true),
                'login_time' => time()
            ],['uid' => $obj->uid]);
            Session::set('admin',$obj->toArray());
            // 提交事务
            $this->commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            $this->rollback();
            $this->error = '系统繁忙,稍后再试~';
            return false;
        }
    }

    //设置组合性质的读取器
    /*protected function getUserTitleAttr($value,$data)
    {
        return $data['username'] . ':' . $data['sex'];
    }*/


    /**
     * 添加管理员
     */
    public function insertAdmin()
    {
        $validate = new \app\admin\validate\Admin();
        $data = $this->request->param();
        $id = isset($data['init_id']) ? $data['init_id'] : 0;
        if ($id) {
            // 更新的验证器
            $vdata = $validate->scene('edit')->check($data);
        } else {
            $vdata = $validate->scene('insert')->check($data);
        }
        if ($vdata == false) {
            $this->error = $validate->getError();
            return false;
        }
        $this->startTrans();
        $insert = [
            'username' => trim($data['username']),
            'nickname' => $data['nickname'] ? trim($data['nickname']) : '',
            'password' => $this->encryptPassword($data['password']),
            'email' => $data['email'] ? trim($data['email']) : ''
        ];


        if ($id) {
            // 检测用户是否被更新
            $is_user = $this->where(['username' => $insert['username'],'uid' => ['<>',$id]])->find();
            if ($is_user) {
                $this->error = '该用户名已被占用';
                return false;
            }
            // 检测密码是否有被更新
            if ($data['password']) {
                if (strlen($data['password']) < 6) {
                    $this->error = '密码不能小于6个字符';
                    return false;
                }
                if ($data['re_password'] != $data['password']) {
                    $this->error = '密码和确认密码不一致';
                    return false;
                }
                $insert['password'] = $this->encryptPassword($data['password']);
            } else {
                unset($insert['password']);
            }
        }
        try{
            if ($id) {
                $insert['uid'] = $id;
                // 修改
                $this->allowField(true)->isUpdate(true)->save($insert);
                Db::name('auth_group_access')->where(['uid' => $id])->delete();
                Db::name('auth_group_access')->insert([
                    'uid' => $id,
                    'group_id' => $data['group_id']
                ]);
                // 提交事务
                $this->commit();
                return true;
            } else {
                // 新增数据
                $this->isUpdate(false)->save($insert);
                Db::name('auth_group_access')->insert([
                    'uid' => $this->uid,
                    'group_id' => $data['group_id']
                ]);
                // 提交事务
                $this->commit();
                return $this->uid;
            }
        } catch (\Exception $e) {
            // 回滚事务
            $this->rollback();
            $this->error = '操作失败,稍后再试...';
            return false;
        }

    }

    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['uid' => $uid])->update(['password' => $passwd]);
        return $ret;
    }

    // 密码加密
    public function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        $salt_s = $salt ? $salt : $this->salt;
        return $encrypt($password . $salt_s);
    }

    /**
     * 检测并且过滤搜索条件
     * @param $keywords
     * @return array
     */
    public function filtrationWhere($keywords)
    {
        $where = [];
        if ($keywords) {
            if (is_numeric($keywords)) {
                $where['id'] = $keywords;
                return $where;
            }
            if (cp_is_ip($keywords)) {
                $where['last_login_ip'] = ['like',"%{$keywords}%"];
                return $where;
            }
            if (cp_isEmail($keywords)) {
                $where['email'] = ['like',"%{$keywords}%"];
                return $where;
            }
            $where['username'] = ['like',"%{$keywords}%"];
        }
        return $where;
    }
}
