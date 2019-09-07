<?php
/**
 * 基类
 */
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Response;
class Base extends Controller
{
    // 调试接口debug
    protected $debug = true;

    // 用户uid
    protected $uid;
    

    // 初始化方法
    public function _initialize()
    {
        header("Access-Control-Allow-Origin:*");
        $token =  trim($this->request->param('token'));
        $this->uid = \auth\Token::instance()->getTokenToUid($token);
    }

    /**
     * 输出json数组
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected function outJson($code = 0, $msg = '', $data = [])
    {
       return [
            "status" => $code,
            "msg" =>  $msg,
            "data" => $data
        ];
    }

    /**
     * 验证手机短信验证码
     * @param $phone
     * @param $code
     * @param $scene
     * @return bool
     * @throws \think\Exception
     */
    protected function checkPhoneCode($phone, $code, $scene)
    {
        $check_item = '';
        switch($scene)
        {
            case 'register':
                $check_item = $phone . '_' . $code . '_' . $scene;
                break;
            case 'find':
                $check_item = $phone . '_' . $code . '_' . $scene;
                break;
            case 'bank':
                $check_item = $phone . '_' . $code . '_' . $scene;
                break;
            case 'login':
                $check_item = $phone . '_' . $code . '_' . $scene;
                break;
            case 'alipay':
                $check_item = $phone . '_' . $code . '_' . $scene;
                break;
            case 'withdraw':
                $check_item = $phone . '_' . $code . '_' . $scene;
                break;

        }
        $check = Db::name('check_phone')
                ->where(['check_item' => $check_item,'is_check' => 0, 'phone' => $phone])
                ->whereTime('sub_time','today')
                ->value('id');
        if ($check) {
            // 修改验证状态
            $bool = Db::name('check_phone')
                ->where(['id' => $check])
                ->update([
                    'check_time' => time(),
                    'is_check' => 1
                ]);
            if ($bool) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 获取用户手机号码
     * @return mixed
     */
    protected function getUserPhone($uid =-1)
    {
        return Db::name('member')->where(['uid' => $uid])->value('phone');
    }

    //添加消息
    protected function insertMessage($data = array()){

        return Db::name('message_log')->insert($data);
    }

    /**获取用户团队数据
     * @param int $uid
     * @return array
     */
    protected function getUserTeam($uid=-1){
//        set_time_limit(0);
//        ini_set('memory_limit','500M');
        $data = array();
        $user = Db::name('member')->where(['invite_uid' => $uid])->field('uid,nick_name,phone,face,member_class,invite_time')->select();
        if(!empty($user)) {
            $data = array_merge($data, $user);
            foreach ($user as $value) {
                $arr = $this->getUserTeam($value['uid']);
                $data = array_merge($data,$arr);

            }
        }
        return $data;
    }

    /*
    *2.获取某个会员的无限下级方法  非递归方法
    *$members是所有会员数据表,$mid是用户的id
    */
    function GetTeamMember($members,$uid) {
        $Teams=array();//最终结果
        $mids=array($uid);//第一次执行时候的用户id
        do {
            $othermids=array();
            $state=false;
            foreach ($mids as $valueone) {
                foreach ($members as $key => $valuetwo) {
                    if($valuetwo['invite_uid']==$valueone){
                        $Teams[]=$valuetwo;//找到我的下级立即添加到最终结果中
                        $othermids[]=$valuetwo['uid'];//将我的下级id保存起来用来下轮循环他的下级
                        unset($members[$key]);//从所有会员中删除他
                        $state=true;
                    }
                }
            }
            $mids=$othermids;//foreach中找到的我的下级集合,用来下次循环
        } while ($state==true);
        return $Teams;
    }

    public function getTeam($members,$uid){
        set_time_limit(0);
        ini_set('memory_limit','500M');
        $arr=array();
        if(is_array($members)){
            foreach($members as $k => $v){
                if($uid == $v['invite_uid']){
                    $arr[]=$v;
                    $arr = array_merge($arr,$this->getTeam($members,$v['uid']));
                }
            }
        }
        return $arr;
    }

}
