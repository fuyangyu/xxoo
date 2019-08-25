<?php
namespace auth;
use think\Cache;
class Token{
    // 对象实例
    protected static $instance;

    private function __construct($options)
    {

    }

    /**
     * 外部调用获取实列
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 获取AccessToken值,前端只需要获取一次
     * @param String $user_id
     * @return array
     */
    public function getAccessToken($user_id,$nick_name='')
    {
        $fileData = Cache::get('token');
        $token_id = md5($user_id . "_" . uniqid());
        if (isset($fileData[$user_id])) {
            if (time() > $fileData[$user_id]['time']) {
                $v_time = 30 * 86400; // 默认30天
                $fileData[$user_id] = [
                    'token' => $token_id,
                    'time' => time() +  $v_time,
                    'uid' => $user_id,
                    'nick_name' => $nick_name,
                ];
                Cache::set('token',$fileData,0);
                return $this->outJson(1,'获取token成功',$fileData[$user_id]);
            } else {
                // 存在缓存的情况
                return $this->outJson(1,'获取token成功',$fileData[$user_id]);
            }
        } else {
            // 不存在的情况
            $v_time = 30 * 86400; // 默认30天
            $fileData[$user_id] = [
                'token' => $token_id,
                'time' => time() +  $v_time,
                'uid' => $user_id,
                'nick_name' => $nick_name,
            ];
            Cache::set('token',$fileData,0);
            return $this->outJson(1,'获取token成功',$fileData[$user_id]);
        }
    }

    /**
     * 验证Token
     * @param String $token 前端请求头部token值
     * @return array
     */
    public function checkAccessToken($token)
    {
        if (!$token) return $this->outJson(0,'缺少token参数！');
        $token = trim($token);
        $fileData = Cache::get('token');
        $check = [];
        if (!$fileData) return $this->outJson(400,'token验证失败！');
        foreach ($fileData as $k => $v) {
            if ($v['token'] == $token) {
                $check = $v;
            }
        }
        if (!$check) return $this->outJson(400,'token验证失败！');
        // 存在缓存的情况
        // 判断是否是在生命周期
        if (time() > $check['time']) {
            return $this->outJson(400,'token已失效！');
        }
        return $this->outJson(1,'token验证成功！');
    }

    /**
     * 获取token用户UID
     * @param $token
     * @return bool|int
     */
    public function getTokenToUid($token)
    {
        if (!$token) return false;
        $fileData = Cache::get('token');
        $uid = 0;
        $token = trim($token);
        if (!$fileData) return false;
        foreach ($fileData as $k => $v) {
            if ($v['token'] == $token) {
                $uid = $v['uid'];
            }
        }
        return $uid;
    }

    /**
     * 移除指定
     * @param $token
     * @return array
     */
    public function rmAccessToken($token)
    {
        if (!$token) return $this->outJson(1001,'缺少token参数！');
        $fileData = Cache::get('token');
        $status = false;
        foreach ($fileData as $k => $v) {
            if ($v['token'] == $token) {
                unset($fileData[$k]);
                $status = true;
            }
        }
        if ($status) {
            Cache::set('token',$fileData,0);
            return $this->outJson(1,'退出成功');
        } else {
            return $this->outJson(0,'退出失败！');
        }
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

    private function __clone() {}
}