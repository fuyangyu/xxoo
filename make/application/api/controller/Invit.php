<?php
namespace app\api\controller;
use app\api\controller;
use think\Db;

/**
 * 邀请好友
 * Class Invit
 * @package app\api\controller
 */
class Invit extends Base
{


   public function index(){

       try{

           $data = array();
           $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
           $user = Db::name('member')->where('uid',$uid)->field('phone,member_brokerage_money,channel_money')->find();
           if($user){
               $data['invit_phone'] = $user['phone'];   //推荐人手机 (邀请注册时用)
               $data['invit_earnings'] = $user['member_brokerage_money'] + $user['channel_money']; //累计收益
           }
           //获取邀请人数据
           $data['invit'] = Db::name('member')->field('phone,invite_uid,nick_name,face,member_class')->where('invite_uid',$uid)->order('add_time','desc')->select();
           if($data['invit']){
               foreach($data['invit'] as &$value){
                   if(!empty($value['face'])){
                       $value['face'] = $this->request->domain().$value['face'];
                   }
                   $value['phone'] = cp_func_substr_replace($value['phone'],'*',3,4);
               }
           }
           //获取邀请邀请总数
           $data['invit_count'] = count($data['invit']);
           //邀请轮播数据
           $data['invit_carousel'] = Db::name('member')->field('phone,invite_uid,nick_name,face')->where('invite_uid','<>','')->order('uid','desc')->limit(15)->select();
           if($data['invit_carousel']){
               foreach($data['invit_carousel'] as $k => &$v){
                   if(!empty($v['face'])){
                       $v['face'] = $this->request->domain().$v['face'];
                   }
                   $invitData = Db::name('member')->where('uid',$v['invite_uid'])->field('nick_name,phone')->find();
                   $data['invit_carousel'][$k]['invit_name'] = $invitData['nick_name'];
                   $data['invit_carousel'][$k]['invit_phone'] = cp_func_substr_replace($invitData['phone'],'*',3,4);
                   $v['phone'] = cp_func_substr_replace($v['phone'],'*',3,4);
               }
           }
           return json($this->outJson(1,'成功',$data));

       } catch (\Exception $e){
           return json($this->outJson(0,'服务器响应失败'));
       }

   }

    /**
     * 邀请注册页面
     * @return \think\response\Json
     */
    public function invitationRegisterShow(){
        $phone = $this->request->param('phone');
        if(!$phone) return json($this->outJson(0,'参数错误'));

        //获取用户信息
        $data['nick_name'] = Db::name('member')->where('phone',$phone)->value('nick_name');
        $invit = Db::name('member')->field('phone,invite_uid,nick_name,face,member_class')->where('invite_uid','<>','')->order('uid','desc')->limit(15)->select();
        if($invit){
            foreach($invit as $k => &$v){
                if(!empty($v['face'])){
                    $v['face'] = $this->request->domain().$v['face'];
                }
                $invitData = Db::name('member')->where('uid',$v['invite_uid'])->field('nick_name,phone')->find();
                $invit[$k]['invit_name'] = $invitData['nick_name'];
                $invit[$k]['invit_phone'] = cp_func_substr_replace($invitData['phone'],'*',3,4);
                $v['phone'] = cp_func_substr_replace($v['phone'],'*',3,4);
            }
        }
        $data['invit'] = $invit;
        return json($this->outJson(1,'成功',$data));
    }
}
