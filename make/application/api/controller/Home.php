<?php
namespace app\api\controller;
use think\Db;
use Endroid\QrCode\QrCode;
use think\Response;

class Home extends Base
{
    // 推广二维码
    public function index()
    {
        try{
            if ($this->request->isPost()) {
                $old = Db::name('member')->where(['uid' => $this->uid])->field('invite_img,invite_code')->find();
                if ($old['invite_img']) {
                    $old['url'] = $this->request->domain() . "/index.php/index/register/code/" . $old['invite_code'] . ".html";
                    return json($this->outJson(1,'获取成功',$old));
                } else {
                    $url = $this->request->domain() . "/index.php/index/register/code/" . $old['invite_code'] . ".html";
                    $logo = './uploads/qrcode/dp_logo.png';
                    $filename = uniqid() . ".png";
                    $msg = $this->createQrCodeImg($url,$filename, 200, $logo,50);
                    if (!$msg['status']) return json($msg);
                    $data = [
                        'invite_img' => substr($msg['msg'],1),
                        'invite_code' => $old['invite_code'],
                        'url' => $url
                    ];
                    Db::name('member')->where(['uid' => $this->uid])->setField('invite_img',substr($msg['msg'],1));
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
     * 所有公告列表
     * @return \think\response\Json
     */
    public function notice()
    {
        try{
            if ($this->request->isPost()) {
                $data = Db::name('notice')->where(['is_show' => 1])->field('id,title,content,add_time')->order(['id'=>'desc'])->select();
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
     * 首页弹窗公告
     * @return \think\response\Json
     */
    public function noticeIndex()
    {
        try{
            if ($this->request->isPost()) {
                $data = Db::name('notice')->where(['is_show' => 1,'is_index' => 1])->field('id,title,content,add_time')->order(['id'=>'desc'])->find();
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
                $data = Db::name('banner')->where(['is_show' => 1])->field('url,skip')->order(['sort'=>'desc','id'=>'desc'])->select();
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
                    'service_time' => isset($fileData['service_time']) ? $fileData['service_time'] : ''
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
     * 获取会员等级充值的费用信息
     * @return \think\response\Json
     */
    public function charge()
    {
        try{
            if ($this->request->isPost()) {
                $fileData = cp_getCacheFile('system');
                $res = [
                    'vip' => [
                        ['name' => '普通VIP','money' => isset($fileData['common_money']) ? $fileData['common_money'] : '']
                        ,['name' => '高级VIP','money' => isset($fileData['expert_money']) ? $fileData['expert_money'] : '']
                    ],
                    'img' => isset($fileData['member_img']) ? $fileData['member_img'] : ''
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

    public function messageStatus(){
        $uid = $this->request->param('uid',1);
        $message = Db::name('message_log')->where(['uid' => $uid,'status' => 1])->order('id desc')->find();
        $status = $message?1:2; //1：未读 2：已读
        return json($this->outJson(1,'成功',$status));
    }

    /**
     * 消息列表
     * @return \think\response\Json
     */
    public function messageList(){
        $data = array();
        $page = $this->request->param('page',1); //页数
        $uid = $this->request->param('uid',1); //页数
        $limit = 10;    //每页数量
        $start = 0;     //开始位置
        if ($page > 1) {
            $start = ($page-1) * $limit;
        }
        $sql = "SELECT * FROM wld_message_log where uid = $uid ORDER BY id DESC LIMIT {$start},{$limit};";
        $data = Db::query($sql);
        return json($this->outJson(1,'成功',$data));
    }

}