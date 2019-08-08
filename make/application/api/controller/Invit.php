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
           $data['invit_phone'] = Db::name('member')->where('uid',$uid)->value('phone');

           //获取邀请人数据
           $data['invit'] = $invit = Db::name('member')->field('phone,invite_uid,nick_name,face,member_class,balance')->where('invite_uid',$uid)->order('add_time','desc')->select();
           if(!empty($invit)){
               //获取邀请邀请总数
              $data['invit_count'] = count($invit);
               //邀请轮播数据
              $data['invit_carousel'] = array_slice($invit,0,15);
           }
           //累计收益(待定)
//           $data['invit_earnings'] = Db::name('member')->where('uid',$uid)->value('balance');
           return json($this->outJson(1,'成功',$data));

       } catch (\Exception $e){
           return json($this->outJson(0,'服务器响应失败'));
       }

   }
}
