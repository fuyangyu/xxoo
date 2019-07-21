<?php
namespace app\admin\model;
use think\Db;
use think\Model;
use think\Request;
use think\Response;
class Member extends Base
{
    //指定主键
    protected $pk = 'uid';

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
        $obj = $this->where($where)->limit($start,$limit)->order(['uid'=>'desc'])->select();
        $data = $obj->toArray();
        if ($data) {
            foreach ($data as $k => $v) {
                $line_total = $this->where($where)
                    ->whereOr('parent_level_1','=',$v['uid'])
                    ->whereOr('parent_level_2','=',$v['uid'])
                    ->whereOr('parent_level_3','=',$v['uid'])
                    ->whereOr('parents','=',$v['uid'])
                    ->count();
                $data[$k]['line_total'] = $line_total;
            }
        }
        // 查询总记录数
        $total = $this->where($where)->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }

    /**
     * 异步获取服务中心申请
     * @param array $where
     * @param int $limit
     * @return array
     */
    public function getServeData($where = [],$limit=10)
    {
        $request = Request::instance();
        $page = $request->param('pageIndex',1,'int');
        $start = 0;
        if ($page != 1) {
            $start = ($page-1) * $limit;
        }
        $data = Db::name('member_serve')->where($where)->limit($start,$limit)->order(['id'=>'desc'])->select();
        // 查询总记录数
        $total = Db::name('member_serve')->where($where)->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }

    /**
     * 检测并且过滤搜索条件
     * @param array $where
     * @return array
     */
    public function filtrationServeWhere($where = [])
    {
        if (!$where) return [];
        $start_time = isset($where['start_time']) ? $where['start_time'] : '';
        $end_time = isset($where['end_time']) ? $where['end_time'] : '';
        $keywords = isset($where['keywords']) ? $where['keywords'] : '';
        $where = [];
        if ($keywords) {
            if (is_numeric($keywords) && !cp_isMobile($keywords)) {
                $where['uid'] = $keywords;
            } else {
                $where['phone'] = ['like',"%{$keywords}%"];
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
        return $where;
    }

    /**
     * 异步获取下线会员数据
     * @param array $where
     * @param int $uid
     * @param int $limit
     * @return array
     */
    public function getDownLineListData($where = [],$uid, $limit=10)
    {
        $request = Request::instance();
        $page = $request->param('pageIndex',1,'int');
        $start = 0;
        if ($page != 1) {
            $start = ($page-1) * $limit;
        }
        $obj = $this
                ->where($where)
                ->whereOr('parent_level_1','=',$uid)
                ->whereOr('parent_level_2','=',$uid)
                ->whereOr('parent_level_3','=',$uid)
                ->whereOr('parents','=',$uid)
                ->limit($start,$limit)
                ->order(['uid'=>'desc'])
                ->select();
        $phone = $this->where('uid','=',$uid)->value('phone');
        $data = $obj->toArray();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['parent'] = $phone;
            }
        }
        // 查询总记录数
        $total = $this
                ->where($where)
                ->whereOr('parent_level_1','=',$uid)
                ->whereOr('parent_level_2','=',$uid)
                ->whereOr('parent_level_3','=',$uid)
                ->whereOr('parents','=',$uid)
                ->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }

    // 获取器
    public function getMemberClassAttr($value)
    {
        $html = '';
        switch ($value) {
            case 1:
                $html = '普通会员';
                break;
            case 2:
                $html = 'VIP';
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
        $where = [];
        if ($keywords) {
            if (is_numeric($keywords) && !cp_isMobile($keywords)) {
                $where['uid'] = $keywords;
            } else {
                $where['phone'] = ['like',"%{$keywords}%"];
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
            $where['member_class']  = $userLevel;
        }
        return $where;
    }


    /*
     * 处理编辑
     */
    public function store($data)
    {
        $id = $data['id'];
        $init = [
            'member_class' => $data['member_class']
        ];
        if ($init['member_class'] == 4) {
            $is_check = $this->where(['uid' => $id])->value('member_class');
            if ($is_check == $init['member_class']) return $this->outJson(1,'您已经是服务中心了,无法再升级！');
            // 服务中心
            $insert_hire_Log = $this->createHireLog($id,1,10000,$id);
            if ($insert_hire_Log) {
                Db::startTrans();
                $log_count = count($insert_hire_Log);
                $log_id = Db::name('hire_log')->insertAll($insert_hire_Log);
                $auto_id = 0;
                foreach ($insert_hire_Log as $k => $v) {
                    $auto_id = Db::name('member')->where(['uid' => $v['uid']])->setInc('balance',$v['hire_money']);
                }
                // 编辑
                $id = Db::name('member')->where(['uid' => $id])->update($init);
                if ($log_count == $log_id && $auto_id > 0 && $id > 0) {
                    Db::commit();
                    return $this->outJson(0,'操作成功!');
                } else {
                    Db::rollback();
                    return $this->outJson(1,'操作失败!');
                }
            }
        } else {
            // 编辑
            $this->where(['uid' => $id])->update($init);
            return $this->outJson(0,'操作成功!');
        }
    }

    // 更改为服务中心 调用该方法 来创建佣金记录 和 给相应的用户加余额
    /**
     * 创建任务-会员充值佣金数据
     * @param int $uid    当前用户id
     * @param int $type   业务类型
     * @param int $money  当前充值金额
     * @param int $type_id 会员id
     * @return array
     */
    protected function createHireLog($uid, $type, $money, $type_id)
    {
        // 查询当前用户的具体信息
        $user = Db::name('member')
            ->where(['uid' => $uid])
            ->find();
        $insert = [];
        // TODO 会员收费
        if ($user['parent_level_1']) {
            // 存在父一级的情况
            $data = $this->createMemberHireLogData($user['parent_level_1'],$type,$uid,1, $money, $type_id);
            if ($data) array_push($insert,$data);
        }
        if ($user['parent_level_2']) {
            // 父二级
            $data = $this->createMemberHireLogData($user['parent_level_2'],$type,$uid,2, $money, $type_id);
            if ($data) array_push($insert,$data);
        }
        if ($user['parent_level_3']) {
            // 父三级
            $data = $this->createMemberHireLogData($user['parent_level_3'],$type,$uid,3, $money, $type_id);
            if ($data) array_push($insert,$data);
        }
        if ($user['parents']) {
            // 直接上级
            $data = $this->createServerMemberHireLogData($user,$money);
            if ($data) {
                foreach ($data as $k => $v) {
                    array_push($insert,$v);
                }
            }
        }
        return $insert;
    }

    /**
     * 创建任务-会员充值佣金数据
     * @param int $parent_id  用户的父级id
     * @param int $type   业务类型
     * @param int $uid    当前用户id
     * @param int $lower_parent_level    当前用户的父几级
     * @param int $money  当前充值金额
     * @param int $type_id 会员id
     * @return array
     */
    protected function createMemberHireLogData($parent_id, $type, $uid, $lower_parent_level, $money, $type_id)
    {
        // 查询用户的等级
        $parent_level = Db::name('member')
            ->where(['uid' => $parent_id])
            ->value('member_class');
        // 每个级别取对应的级别数据
        $value_html = '';
        switch($lower_parent_level)
        {
            case 1:
                $value_html = 'allot_one';
                break;
            case 2:
                $value_html = 'allot_two';
                break;
            case 3:
                $value_html = 'allot_three';
                break;
        }
        // 查询业务所占比例
        $scale = Db::name('allot_log')
            ->where(['charge_type' => $type, 'user_level' => $parent_level])
            ->value($value_html);
        if ($scale && $scale > 0) {
            // 比列按百分比 需要除以100
            $ratio = $scale / 100;

            // 初始化公式所计算的具体分配给用户的佣金
            $init_money = computational(1,$money);

            // 如果当前用户的父级会员是服务中心会员级别的 还需要计算拿团队的比列
            $server_money = 0;
            /*if ($parent_level == 4) {
                $infinite_scale = Db::name('allot_log')
                    ->where(['charge_type' => 1, 'user_level' => $parent_level])
                    ->value('infinite');
                if ($infinite_scale && $infinite_scale > 0) {
                    $infinite_ratio = $infinite_scale / 100;
                    $server_money = $init_money * $infinite_ratio;
                    $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                }
            }*/

            // 计算所占比列
            $init_m = $init_money * $ratio;
            $hire_money = $init_m + $server_money;
            $hire_money = substr(sprintf("%.3f",$hire_money),0,-1);

            // 初始化 佣金记录
            $hire_log = [
                'uid' => $parent_id,
                'user_level' => $parent_level,
                'lower_parent_level' => $lower_parent_level,
                'ratio' => $scale,
                'hire_type' => $type,
                'lower_uid' => $uid,
                'hire_money' => $hire_money,
                'type_id' => $type_id,
                'type_log_id' => 0,
                'add_time' => time()
            ];
            // 后台更换 直接发放
            $hire_log['is_check'] = 1;
            $hire_log['check_time'] = date('Y-m-d H:i:s');
            return $hire_log;
        } else {
            return [];
        }
    }

    /**
     * 寻找当前用户id 父级服务中心等级的个数
     * @param int $uid 用户Id
     * @param int $num 取几代
     * @param array $user_parents_uid 当期父类id属于服务中心的数组
     * @return array
     */
    public function seekParentsUid($uid, $num, $user_parents_uid = [])
    {
        $users = Db::name('member')->select();
        if ($users) {
            // 递归遍历当前用户id 的 所有父级id
            $data = \cocolait\helper\CpData::parentChannel($users,$uid,'uid','parents');
            if ($data) {
                array_shift($data);
                $count = count($data);
                if ($count > 0) {
                    $parents_uid = [];
                    foreach ($data as $k => $v) {
                        if ($v['member_class'] == 4) {
                            if ($user_parents_uid) {
                                if (!in_array($v['uid'],$user_parents_uid)) {
                                    array_push($parents_uid,$v['uid']);
                                }
                            } else {
                                array_push($parents_uid,$v['uid']);
                            }
                        }
                    }
                    $count_s = count($parents_uid);
                    if ($count_s > 0) {
                        if ($count_s >= $num) {
                            $temp = [];
                            for ($i = 0; $i < $num; $i++) {
                                $temp[] = $parents_uid[$i];
                            }
                            return $temp;
                        } else {
                            return $parents_uid;
                        }
                    } else {
                        return [];
                    }
                } else {
                    return [];
                }
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    /**
     * 【会员充值】检测服务中心的个数并且创建佣金记录
     * @param array $userData  当前用户的信息
     * @param int $money  当前业务佣金
     * @return array
     */
    protected function createServerMemberHireLogData($userData,$money)
    {
        // 查询当前用户父级的会员等级
        $parent_uid_s = [];
        if ($userData['parent_level_1']) {
            array_push($parent_uid_s,$userData['parent_level_1']);
        }
        if ($userData['parent_level_2']) {
            array_push($parent_uid_s,$userData['parent_level_2']);
        }
        if ($userData['parent_level_3']) {
            array_push($parent_uid_s,$userData['parent_level_3']);
        }
        if (!$parent_uid_s) return [];

        // 初始化公式所计算的具体分配给用户的佣金
        $init_money = computational(1,$money);
        // 查询服务中心所占的比列
        $scale = Db::name('allot_log')
            ->where(['charge_type' => 1, 'user_level' => 4])
            ->value('infinite');
        if ($scale && $scale > 0) {
            $ratio = $scale / 100;
            $server_money = $init_money * $ratio;
            $server_money = substr(sprintf("%.3f",$server_money),0,-1);

            // 查询出上三级会员等级
            $user_parent_data = Db::name('member')
                ->where(['uid' => ['in',$parent_uid_s]])
                ->field('member_class,uid')
                ->select();

            if ($user_parent_data) {
                // 检测父三级到底有几个服务中心
                $check_level = 0;
                $user_parents_uid = [];
                foreach ($user_parent_data as $k => $v) {
                    if ($v['member_class'] == 4) {
                        $check_level++;
                        $user_parents_uid[] = $v['uid'];
                    }
                }
                $result = [];
                // 匹配服务中心个数
                switch($check_level)
                {
                    case 0:
                        // 一个都没有
                        $uid_s = $this->seekParentsUid($userData['uid'],3,$user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 1,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $userData['uid'],
                                    'type_log_id' => 0,
                                    'add_time' => time(),
                                ];
                                $result[$i]['is_check'] = 1;
                                $result[$i]['check_time'] = date('Y-m-d H:i:s');
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 1:
                        // 有一个
                        $uid_s = $this->seekParentsUid($userData['uid'],2,$user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 1,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $userData['uid'],
                                    'type_log_id' => 0,
                                    'add_time' => time(),
                                ];
                                $result[$i]['is_check'] = 1;
                                $result[$i]['check_time'] = date('Y-m-d H:i:s');
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 2:
                        // 有二个服务中心 那么最多只能找一个服务中心 找不到返回[]
                        $uid_s = $this->seekParentsUid($userData['uid'],1,$user_parents_uid);
                        if ($uid_s) {
                            for($i=0; $i < count($uid_s); $i++){
                                if ($i == 0) {
                                    // 第一级 拿5%
                                    $ratio = 5 / 100;
                                } else {
                                    // 后面2级 1%
                                    $ratio = 1 / 100;
                                }
                                $server_money = $init_money * $ratio;
                                $server_money = substr(sprintf("%.3f",$server_money),0,-1);
                                $user_level = Db::name('member')->where(['uid' => $uid_s[$i]])->value('member_class');
                                $result[$i] = [
                                    'uid' => $uid_s[$i],
                                    'user_level' => $user_level,
                                    'lower_parent_level' => $userData['member_class'],
                                    'ratio' => $scale,
                                    'hire_type' => 1,
                                    'lower_uid' => $userData['uid'],
                                    'hire_money' => $server_money,
                                    'type_id' => $userData['uid'],
                                    'type_log_id' => 0,
                                    'add_time' => time(),
                                ];
                                $result[$i]['is_check'] = 1;
                                $result[$i]['check_time'] = date('Y-m-d H:i:s');
                            }
                        } else {
                            $result = [];
                        }
                        break;
                    case 3:
                        // 有三个
                        $result = [];
                        break;
                }
                return $result;
            } else {
                // 没有父级情况 也是直接退出
                return [];
            }
        } else {
            // 没有相对应的比列设置也是直接退出
            return [];
        }
    }

    /**
     * 异步获取佣金记录列表数据
     * @param array $where
     * @param int $limit
     * @return array
     */
    public function getBrokerageData($where = [],$limit=10)
    {
        $request = Request::instance();
        $page = $request->param('pageIndex',1,'int');
        $start = 0;
        if ($page != 1) {
            $start = ($page-1) * $limit;
        }
        $obj = Db::name('hire_log')->where($where)->limit($start,$limit)->order(['id'=>'desc'])->select();
        $data = $obj->toArray();
        // 查询总记录数
        $total = $this->where($where)->count();
        return [
            'rows' => $data,
            'total' => $total
        ];
    }
}
