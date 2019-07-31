<?php
namespace app\api\controller;
use think\Db;

class Member extends Base
{
    // 个人中心（暂时不用）
    public function index()
    {
        try{
            if ($this->request->isPost()) {
                $model = new \app\api\model\Member();
                $data = $model->getIndexData($this->uid);
                return json($this->outJson(1,'获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }
    }


    //个人信息渲染页
    public function userInfoActio(){
        return $this->fetch('demo');
    }

    //设置昵称渲染页
    public function nicknameActio(){
        $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
        $nickname = Db::name('member_info')->where(['uid' => $uid])->value('nick_name');
        if(empty($nickname)){
            $nickname = Db::name('member')->where(['uid' => $uid])->value('phone');
        }
        return $this->fetch('demo',['nickname'=>$nickname]);
    }

    /**
     * @return mixed
     * 昵称修改处理
     */
    public function setNickname(){
        try{
            $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
            $nickname = trim($this->request->param('nickname'));
            $data = Db::name('member_info')->where('uid',$uid)->setField('nick_name',$nickname);
            if($data){
                return json($this->outJson(1,'修改成功'));
            }else{
                return json($this->outJson(0,'修改失败'));
            }
        }catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }

    }

    //填写推荐人渲染页
    public function inviteActio(){
        return $this->fetch('demo');
    }


    /**
     * 填写推荐人处理
     * @return \think\response\Json
     */
    public function setInvite(){
        try{
            $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
            $phone = trim($this->request->param('phone'));
            //获取填写推荐人uid
            $imvite_uid = Db::name('member')->where('phone',$phone)->value('uid');
            if(!$imvite_uid) return json($this->outJson(0,'推荐人不存在'));
            //获取当前用户是否有推荐人
            $former_uid = Db::name('member')->where('uid',$uid)->value('invite_uid');
            if($former_uid) return json($this->outJson(0,'已有推荐人'));
            //修改推荐人
            $data = Db::name('member')->where('uid',$uid)->setField('invite_uid',$imvite_uid);
            if($data){
                return json($this->outJson(1,'修改成功'));
            }else{
                return json($this->outJson(0,'修改失败'));
            }
        }catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }


    }
    /**
     * @return \think\response\Json
     * 获取个人信息
     */
    public function userInfo(){
        try{
            $uid = !empty($this->request->param('uid'))?$this->request->param('uid'):$this->uid;
            $model = new \app\api\model\Member();
            $data = $model->getuserInfo($uid);
            if($data){
                return json($this->outJson(1,'获取成功',$data));
            }else{
                return json($this->outJson(0,'获取失败'));
            }
        }catch (\Exception $e){
            return json($this->outJson(0,'服务器响应失败' . $e->getMessage()));
        }
    }


    // 头像上传
    public function upFace()
    {
        try{
            if ($this->request->isPost()) {
                $picture = trim($this->request->param('picture'));
                if (!$picture) return json($this->outJson(0,'请求参数不完整'));
                //# dataURI base_64 编码上传 手机端常用方式
                $rootPath = './uploads/face/' . date('Ymd');
                $target = $rootPath . "/" . date('Ymd') . uniqid() . ".jpg" ;
                if (!file_exists($rootPath)) {
                    cp_directory($rootPath);
                }
                $img = base64_decode($picture);
                if (file_put_contents($target, $img)){
                    $face = substr($target,1);
                    $int = Db::name('member')->where(['uid' => $this->uid])->setField('face',$face);
                    if ($int) {
                        return json($this->outJson(1,'上传成功',['face' => $face]));
                    } else {
                        return json($this->outJson(0,'上传失败'));
                    }
                } else {
                    return json($this->outJson(0,'上传失败'));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    // 个人资料
    public function info()
    {
        try{
            if ($this->request->isPost()) {
                $status = $this->request->param('status');
                $data = [
                    'true_name' => $this->request->param('true_name',''),
                    'province' => $this->request->param('province',''),
                    'city' => $this->request->param('city',''),
                    'district' => $this->request->param('district',''),
                    'address' => $this->request->param('address',''),
                ];
                $model = new \app\api\model\Member();
                if ($status == 1) {
                    // 获取资料
                    $data = $model->getMemberInfo($this->uid);
                    return json($this->outJson(1,'获取成功',$data));
                } else {
                    // 保存
                    $bool = $model->setStoreMemberInfo($this->uid,$data);
                    if ($bool) {
                        return json($this->outJson(1,'操作成功'));
                    } else {
                        return json($this->outJson(1,'操作失败'));
                    }
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }



    // 绑定银行卡
    public function bank()
    {
        try{
            if ($this->request->isPost()) {
                $checkData = $this->request->param();
                $status = $this->request->param('status',1);
                $validate = new \app\api\validate\Bank();
                if (!$vdata = $validate->scene('bank')->check($checkData)) {
                    return json($this->outJson(0,$validate->getError()));
                }
                /*$code = $this->request->param('code');
                // 验证短信验证码
                $bool = $this->checkPhoneCode($this->getUserPhone(), trim($code), 'band');
                if (!$bool) return json($this->outJson(0,'短信验证码错误'));*/

                $insert = [
                    'user_name' => trim($checkData['user_name']),
                    'bank_name' => trim($checkData['bank_name']),
                    'bank_branch_name' => trim($checkData['bank_branch_name']),
                    'bank_card_num' => trim($checkData['bank_card_num']),
                    'uid' => $this->uid,
                    'add_time' => date('Y-m-d H:i:s')
                ];
                if ($status == 1) {
                	$is_check = Db::name('bank_info')->where(['uid' => $this->uid])->value('id');
					if ($is_check) return json($this->outJson(0,'提交方式错误'));
                    // 新增
                    $id = Db::name('bank_info')->insertGetId($insert);
                    if ($id) {
                        return json($this->outJson(1,'操作成功'));
                    } else {
                        return json($this->outJson(0,'操作失败'));
                    }
                } else {
                    // 修改
                    unset($insert['uid']);
                    unset($insert['add_time']);
                    Db::name('bank_info')->where(['uid' => $this->uid])->update($insert);
                    return json($this->outJson(1,'操作成功'));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    // 绑定支付银行卡
    public function payBank()
    {
        try{
            if ($this->request->isPost()) {
                $checkData = $this->request->param();
                $status = $this->request->param('status',1);
                $validate = new \app\api\validate\Bank();
                if (!$vdata = $validate->scene('all')->check($checkData)) {
                    return json($this->outJson(0,$validate->getError()));
                }
                /*$code = $this->request->param('code');
                // 验证短信验证码
                $bool = $this->checkPhoneCode($this->getUserPhone(), trim($code), 'band');
                if (!$bool) return json($this->outJson(0,'短信验证码错误'));*/

                $insert = [
                    'phone' => trim($checkData['phone']),
                    'id_card_num' => trim($checkData['id_card_num']),
                    'user_name' => trim($checkData['user_name']),
                    'bank_name' => trim($checkData['bank_name']),
                    'bank_branch_name' => trim($checkData['bank_branch_name']),
                    'bank_card_num' => trim($checkData['bank_card_num']),
                    'uid' => $this->uid,
                    'add_time' => date('Y-m-d H:i:s')
                ];
                if ($status == 1) {
                    $is_check = Db::name('bank_pay_info')->where(['uid' => $this->uid])->value('id');
                    if ($is_check) return json($this->outJson(0,'提交方式错误'));
                    // 新增
                    $id = Db::name('bank_pay_info')->insertGetId($insert);
                    if ($id) {
                        return json($this->outJson(1,'操作成功'));
                    } else {
                        return json($this->outJson(0,'操作失败'));
                    }
                } else {
                    // 修改
                    unset($insert['uid']);
                    unset($insert['add_time']);
                    Db::name('bank_pay_info')->where(['uid' => $this->uid])->update($insert);
                    return json($this->outJson(1,'操作成功'));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 获取用户的银行卡信息
     * @return \think\response\Json
     */
    public function getBank()
    {
        try{
            if ($this->request->isPost()) {
                $type = $this->request->param('type',1);
                if ($type == 1) {
                    // 提现
                    $data = Db::name('bank_info')
                        ->where(['uid' => $this->uid])
                        ->field('user_name,bank_name,bank_branch_name,bank_card_num')
                        ->find();
                    $data = $data ? $data : [];
                } else {
                    // 充值
                    $data = Db::name('bank_pay_info')
                        ->where(['uid' => $this->uid])
                        ->field('user_name,bank_name,bank_branch_name,bank_card_num,phone,id_card_num')
                        ->find();
                    $data = $data ? $data : [];
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
     * 获取银行列表
     * @return \think\response\Json
     */
    public function getBankInfo()
    {
        try{
            if ($this->request->isPost()) {
                return json($this->outJson(1,'获取成功',config('code.bank_info')));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 我的团队 - 获取下线会员级别数量-【TODO 已废弃】
     * @return \think\response\Json
     */
    public function getUserChildTeamNum()
    {
        try{
            if ($this->request->isPost()) {
                $model = new \app\api\model\Member();
                $data = $model->getUserChildTeamInfo($this->uid,1);
                return json($this->outJson(1, '获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 我的团队 - 获取某个级别会员的具体下线用户信息-【TODO 已废弃】
     * @return \think\response\Json
     */
    public function getUserChildTeam()
    {
        try{
            if ($this->request->isPost()) {
                $level = $this->request->param('level');
                if (!in_array($level,[1,2,3])) return json($this->outJson(0, '请求参数错误'));
                $model = new \app\api\model\Member();
                $data = $model->getUserChildTeamInfo($this->uid,2, $level);
                return json($this->outJson(1, '获取成功',$data));
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 我的团队 - 获取下线会员级别数量
     * @return \think\response\Json
     */
    public function getUserChildTeams()
    {
        try{
            if ($this->request->isPost()) {
                $model = new \app\api\model\Member();
                $page = $this->request->param('page',1,'intval');
                $data = $model->getUserChildTeams($this->uid,$page,10);
                return json($data);
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }

    /**
     * 服务中心升级申请
     * @return \think\response\Json
     */
    public function serve()
    {
        try{
            if ($this->request->isPost()) {
                $phone = $this->request->param('phone');
                $name = $this->request->param('name');
                $content = $this->request->param('content');
                if (!$phone || !$content) return json($this->outJson(0, '请求参数错误'));
                if (!cp_isMobile($phone)) return json($this->outJson(0, '手机号码格式错误'));
                if (!$name) return json($this->outJson(0, '申请人姓名不能为空'));
                if (!$content) return json($this->outJson(0, '申请的理由不能为空'));
                $check  = Db::name('member_serve')->where(['uid' => $this->uid])->value('id');
                if ($check) return json($this->outJson(0, '您已经申请过了,不要重复申请'));
                $insert = [
                    'uid' => $this->uid,
                    'phone' => $phone,
                    'name' => trim($name),
                    'content' => trim($content),
                    'add_time' => date('Y-m-d H:i:s')
                ];
                // 启动事务
                Db::startTrans();
                try{
                    $id = Db::name('member_serve')->insertGetId($insert);
                    if ($id) {
                        // 提交事务
                        Db::commit();
                        return json($this->outJson(1, '操作成功'));
                    } else {
                        // 回滚事务
                        Db::rollback();
                        return json($this->outJson(1, '操作失败'));
                    }
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return json($this->outJson(1, '操作失败'));
                }
            } else {
                return json($this->outJson(500,'非法操作'));
            }
        } catch (\Exception $e) {
            return json($this->outJson(0,'服务器响应失败'));
        }
    }
}
