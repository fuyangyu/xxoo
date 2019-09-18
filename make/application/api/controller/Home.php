<?php
namespace app\api\controller;
use think\Db;
use Endroid\QrCode\QrCode;
use think\Response;

class Home extends Base
{
    // 推广二维码
    public function qrCode()
    {
        try{
            if ($this->request->isPost()) {
                $uid = $this->request->param('uid');
                if(!$uid) return json($this->outJson(0,'参数错误'));
                $old = Db::name('member')->where(['uid' => $uid])->field('invite_img,phone,face')->find();
                if ($old['invite_img']) {
                    $data['invite_img'] = $this->request->domain().$old['invite_img'];
                    $data['code'] = $old['phone'];
                    return json($this->outJson(1,'获取成功',$data));
                } else {
                    $url = 'http://www.diandonglife.com/share/invite.html?phone='.$old['phone'];
                    if($old['face']){
                        $logo = '.'.$old['face'];
                    }else{
                        $logo = './uploads/qrcode/qrlogo.png';
                    }
                    $filename = uniqid() . ".png";
                    $msg = $this->createQrCodeImg($url,$filename, 200, $logo,50);
                    if (!$msg['status']) return json($msg);
                    $data = [
                        'invite_img' => $this->request->domain().substr($msg['msg'],1),
                        'code' => $old['phone'],
//                        'url' => $url
                    ];
                    Db::name('member')->where(['uid' => $uid])->setField('invite_img',substr($msg['msg'],1));
                    return json($this->outJson(1,'获取成功',$data));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 首页公告
     * @return \think\response\Json
     */
    public function notice()
    {
        try{
            if ($this->request->isPost()) {
                $data = Db::name('notice')->where(['is_show' => 1])->field('id,title,add_time')->order(['sort'=>'desc'])->limit(2)->select();
                $data = $data ? $data : [];
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 公告列表
     * @return \think\response\Json
     */
    public function noticeList()
    {
        try{
            if ($this->request->isPost()) {
                $data = Db::name('notice')->where(['is_show' => 1])->field('id,title,content,add_time')->order(['sort'=>'desc'])->select();
                $data = $data ? $data : [];
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }


    /**
     * 获取指定id公告具体信息
     * @return \think\response\Json
     */
    public function noticeIdFind()
    {
        try{
            if ($this->request->isPost()) {
                $id = $this->request->param('id');
                $data = Db::name('notice')->where(['id' => $id])->field('id,title,content,add_time')->find();
                $data = $data ? $data : [];
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 轮播图
     * @return \think\response\Json
     */
    public function play()
    {
        try{
            if ($this->request->isPost()) {
                $data = array();
                $data = Db::name('banner')->where(['is_show' => 1])->field('url,skip')->order(['sort'=>'desc','id'=>'desc'])->select();
                if(!empty($data)){
                    foreach($data as &$v){
                        $v['url'] = $this->request->domain().$v['url'];
                    }
                }

                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 获取客服电话以及其他公共配置数据
     * @return \think\response\Json
     */
    public function official()
    {
        try{
            if ($this->request->isPost()) {
                $fileData = cp_getCacheFile('system');
                $res = [
                    'service_mobile' => isset($fileData['service_mobile']) ? $fileData['service_mobile'] : '',
                    'investment_mobile' => isset($fileData['investment_mobile']) ? $fileData['investment_mobile'] : '',
                    'official_mobile' => isset($fileData['official_mobile']) ? $fileData['official_mobile'] : '',
                    'service_time' => isset($fileData['service_time']) ? $fileData['service_time'] : '',
                    'service_weixin' => isset($fileData['service_weixin']) ? $fileData['service_weixin'] : '',
                    'service_email' => isset($fileData['service_email']) ? $fileData['service_email'] : ''
                ];
                return json($this->outJson(1,'获取成功',$res));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 首页点动会员
     * @return \think\response\Json
     */
    public function showCharge()
    {
        try{
            if ($this->request->isPost()) {
                $data = array();
                $fileData = cp_getCacheFile('system');
                $data['common_money'] = isset($fileData['common_money']) ? $fileData['common_money'] : '';
                $data['expert_money'] = isset($fileData['expert_money']) ? $fileData['expert_money'] : '';
                $data['serve_money'] = isset($fileData['serve_money']) ? $fileData['serve_money'] : '';

                //点动会员数据记录
                $sql = "SELECT b.uid,m.nick_name,m.phone,m.face,b.type,b.money,b.add_time,m.member_class FROM wld_brokerage_log as b INNER JOIN wld_member as m ON b.uid = m.uid AND b.type in(1,4) ORDER BY b.id DESC LIMIT 4;";
                $task = Db::query($sql);
                foreach($task as &$v){
                    if($v['face']){
                        $v['face'] = $this->request->domain().$v['face'];
                    }
                }
                $data['task'] = $task;
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 会员升级充值(未调用)
     * @return \think\response\Json
     */
    public function chargePay()
    {
        try{
            if ($this->request->isPost()) {
                $level = $this->request->param('level');
                $pay_status = $this->request->param('pay_status');
                if (!$level && !$pay_status) return json($this->outJson(0,'参数不合法'));
                $model = new \app\api\model\AllotLog();
                $data = $model->userPay($this->uid,$level,$pay_status);
                return json($data);
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 获取如何赚佣
     * @return \think\response\Json
     */
    public function getMake()
    {
        try{
            if ($this->request->isPost()) {
                $fileData = cp_getCacheFile('system');
                $make_des = isset($fileData['make_des']) ? $fileData['make_des'] : '';
                /*$make_arr = [];
                if (strrpos($make_des,'@') === false) {
                    $make_arr = ['name' => $make_des];
                } else {
                    $rule_s = explode('@',$make_des);
                    foreach ($rule_s as $k => $v) {
                        $make_arr[$k]['name'] = $v;
                    }
                }*/
                $res = [
                    'des' => $make_des,
                    'img' => isset($fileData['make_img']) ? $fileData['make_img'] : ''
                ];
                return json($this->outJson(1,'获取成功',$res));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch(\Exception $e){
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 创建二维码
     * @param $content
     * @param $filename
     * @param int $size
     * @param string $logo
     * @param int $logoSize
     * @param int $padding
     * @return array
     * @throws \Endroid\QrCode\Exceptions\DataDoesntExistsException
     * @throws \Endroid\QrCode\Exceptions\ImageTypeInvalidException
     */
    protected function createQrCodeImg($content,$filename, $size = 100, $logo = '', $logoSize = 60, $padding = 0)
    {
        $qrCode = new QrCode();
        $qrCode
            ->setText($content)
            ->setSize($size)
            ->setPadding($padding)
            ->setErrorCorrection('high')
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0])
            ->setLogo($logo)
            ->setLogoSize($logoSize)
            ->setImageType(QrCode::IMAGE_TYPE_PNG);
        $dir_name = './uploads/qrcode/' . date('Y-m-d') . "/";
        if (!file_exists($dir_name)) {
            $bool = cp_directory($dir_name);
            if (!$bool) return ['status'=>0,'msg'=>"无法创建目录：" . $dir_name];
        }
        // 保存图片
        $qrCode->save($dir_name . $filename);
        return ['status' => 1,'msg' => $dir_name . $filename];
    }

    /**
     * 检测时候有已读消息
     * @return \think\response\Json
     */
    public function messageStatus(){
        $uid = $this->request->param('uid',1);
        $message = Db::name('message_log')->where(['uid' => $uid,'status' => 1])->order('id desc')->find();
        $status = $message?1:2; //1：未读 2：已读
        return json($this->outJson(1,'成功',$status));
    }

    /**
     * 消息列表(同时修改未读消息为已读)
     * @return \think\response\Json
     */
    public function messageList(){
        $data = array();
        $page = $this->request->param('page',1); //页数
        $uid = $this->request->param('uid',1);
        $limit = 1;    //每页数量
        $start = 0;     //开始位置
        if ($page > 1) {
            $start = ($page-1) * $limit;
        }
        $sql = "SELECT * FROM wld_message_log where uid = $uid ORDER BY id DESC LIMIT {$start},{$limit};";
        $data = Db::query($sql);
        if($page == 1) { //更新所有消息状态为已读
            Db::name('message_log')->where('uid', $uid)->update(['status' => 2]);
        }
        return json($this->outJson(1,'成功',$data));
    }

    /**
     * 精选任务
     * is_start 是否开始 0 未开始 1 开始
     * @return \think\response\Json
     */
    public function showTask(){
        $uid = $this->request->param('uid');
        $task = array();
        //精选任务
        $sql = "SELECT task_id,title,task_icon,is_area,task_user_level,task_area,start_time,task_money,(taks_fixation_num+get_task_num) as rap_num,(limit_total_num-get_task_num) as authentic_num,1 as is_start FROM wld_task
                WHERE start_time < unix_timestamp(now()) AND status = 1
                ORDER BY start_time DESC LIMIT 5;";
        $task = Db::query($sql);
        if(!empty($task)){
            foreach($task as $k=> &$v){
                if($v['is_area'] == 1){
                    $v['task_area'] = json_decode($v['task_area'],true)['city'];
                }
                $v['task_icon'] = $this->request->domain().$v['task_icon'];
                if($uid){
                    $is_check = Db::name('send_task_log')->where(['uid'=>$uid,'task_id'=>$v['task_id']])->order('id','desc')->limit(1)->value('is_check');
                    $member_classs = Db::name('member')->where('uid',$uid)->value('member_class');
                    $task[$k]['is_check'] = $is_check;
                    $task[$k]['member_classs'] = $member_classs;
                }
                $v['task_user_level'] = explode(',',$v['task_user_level']);
            }
            return json($this->outJson(1,'成功',$task));
        }
        return json($this->outJson(0,'成功'));
    }

    /**
     * 首页精选任务更多
     * is_start 是否开始 0 未开始 1 开始
     * @return \think\response\Json
     */
    public function showTaskMore(){
        if ($this->request->isPost()) {
            $task = array();
            $uid = $this->request->param('uid');
            $page = $this->request->param('page',1); //页数
            $limit = 10;    //每页数量
            $start = 0;     //开始位置
            if ($page > 1) {
                $start = ($page-1) * $limit;
            }
            $sql = "SELECT task_id,title,task_icon,is_area,task_area,start_time,task_money,task_user_level,(taks_fixation_num+get_task_num) as rap_num,(limit_total_num-get_task_num) as authentic_num,1 as is_start FROM wld_task
                    WHERE start_time < unix_timestamp(now()) AND status = 1
                    ORDER BY start_time DESC LIMIT {$start},{$limit};";
            $task = Db::query($sql);
            if(!empty($task)){
                foreach($task as $k => &$v){
                    if($v['is_area'] == 1){
                        $v['task_area'] = json_decode($v['task_area'],true)['city'];
                    }
                    $v['task_icon'] = $this->request->domain().$v['task_icon'];
                    if($uid){
                        $is_check = Db::name('send_task_log')->where(['uid'=>$uid,'task_id'=>$v['task_id']])->order('id','desc')->limit(1)->value('is_check');
                        $member_classs = Db::name('member')->where('uid',$uid)->value('member_class');
                        $task[$k]['is_check'] = $is_check;
                        $task[$k]['member_classs'] = $member_classs;
                    }
                    $v['task_user_level'] = explode(',',$v['task_user_level']);
                }
            }
            return json($this->outJson(1,'成功',$task));
        } else {
            return json($this->outJson(500,'非法操作'));
        }
    }
}