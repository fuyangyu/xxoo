<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
if (!function_exists('createEarningsLog')) { //(暂不用)
    /**
     * 创建业务分润收益记录的数据
     * @param int $money 业务金额
     * @param int $type 业务类型 1：会员收费 2：任务
     * @param int $type_id 业务id 1：用户id 2：任务ID
     * @param array $data 其他的业务参数
     * @return array
     */
    function createEarningsLog($money, $type, $type_id, $data = [])
    {
        $money_s = computational($type, $money);
        $insert_earnings_log = [
            'terrace_money' => $money_s,
            'static_money' => $money_s,
            'fund_money' => $money_s,
            'type' => $type,
            'type_money' => $money,
            'type_id' => $type_id,
            'add_time' => date('Y-m-d H:i:s'),
            'uid' => isset($data['uid']) ? $data['uid'] :0,
            'order_sn' => isset($data['order_sn']) ? $data['order_sn'] :'',
            'task_log_id' => isset($data['task_log_id']) ? $data['task_log_id'] :0,
        ];
        return $insert_earnings_log;
    }
}

if (!function_exists('computational')) {    //(暂不用)
    /**
     * 分润-计算公式
     * @param $type
     * @param $money
     * @return float|int|string
     */
    function computational($type, $money)
    {
        $moneys = 0;
        switch ($type)
        {
            case 1:
                // TODO 会员收费
                $moneys = $money;
                break;
            case 2:
                // TODO 广告任务 X/70%*10%
                $moneys = ($money / 0.7) * 0.1;
                $moneys = substr(sprintf("%.3f",$moneys),0,-1);
                break;
        }
        return $moneys;
    }
}

if (!function_exists('p')) {
    /**
     * 格式化打印数据
     * @param array $data 需要打印的数据
     */
    function p($data)
    {
        header("Content-type:text/html;charset=utf-8");
        // 定义样式
        $str = '<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
        // 如果是boolean或者null直接显示文字；否则pri
        if (is_bool($data)) {
            $show_data = $data ? 'true' : 'false';
        } elseif (is_null($data)) {
            $show_data = 'null';
        } else {
            $show_data = print_r($data, true);
        }
        $str .= $show_data;
        $str .= '</pre>';
        echo $str;
    }
}

if (!function_exists('__'))
{

    /**
     * 获取语言变量值
     * @param string    $name 语言变量名
     * @param array     $vars 动态变量值
     * @param string    $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name)
            return $name;
        if (!is_array($vars))
        {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }

}

if (!function_exists('getUrl')) {
    function getUrl($type=null) {
        if ($type == 1) {   //为链接路径
            // $url = 'https://ceshiapi.iweilingdi.com';
            $url = 'https://'.$_SERVER['HTTP_HOST'];
        }else if($type == 2){
            // $url = 'https://ceshiapi.iweilingdi.com';
            $url = 'https://'.$_SERVER['HTTP_HOST'];
        }else if($type == 3){  //商家后台路径
            // $url = 'https://ceshiapi.iweilingdi.com';
            $url = 'https://'.$_SERVER['HTTP_HOST'];
        }else if($type == 4){  //图片http域名
            // $url = 'http://ceshiapi.iweilingdi.com';
            $url = 'https://'.$_SERVER['HTTP_HOST'];
            // $url = 'http://testimg.iweilingdi.com';
        }else { //图片https域名
            $url = 'https://ceshiapi.iweilingdi.com';
            //$url = 'https://'.$_SERVER['HTTP_HOST'];
            // $url = 'https://img.weilingdi.com';
        }
        return $url;
    }
}

if (!function_exists('page')) {
    //分页统计
    function page($pageIndex, $pageCount, $dataCount, $data) {
        $list = [];
        $list["pageIndex"] = $pageIndex;
        $list["pageCount"] = $pageCount;
        $list["dataCount"] = $dataCount;
        $list["list"] = $data;
        return $list;
    }
}


if (!function_exists('exchangeTime')) {
    //判断当前日期是否是今天 和本周 并返回日期格式
    function exchangeTime($date){
        $today =date('Y-m-d');
        $newdate =substr($date,0,10);
        $year = substr($date, 2, 2);
        $month = substr($date, 5, 2);
        $day = substr($date, 8, 2);
        $ttime = substr($date, 11, 5);

        if($today==$newdate){
            return $ttime;
        }else{
            return $year . '/' . $month . '/' . $day;
        }
    }
}

if (!function_exists('getAppVersion')) {
    /**
     * app版本号判断
     */

    function getAppVersion($version){
        if(!$version){
            return false;
        }
        $data =explode(".",$version);
        if(strlen($data[1])<2){
            $two ="0".$data[1];
        }else{
            $two =$data[1];
        }

        if(count($data)==2){
            $num =$data[0].$two."00";
        }elseif(count($data)==3){
            if(strlen($data[2])>1){
                $num =$data[0].$two.$data[2];
            }else{
                $num =$data[0].$two.$data[2]."0";
            }
        }else{
            $num =$data[0].$two.$data[2].$data[3];
        }

        return $num;
    }
}

if (!function_exists('subtext')) {
    //字符截取
    function subtext($text, $length) {
        if (mb_strlen($text, 'utf8') > $length)
            return mb_substr($text, 0, $length, 'utf8') . '...';
        return $text;
    }
}

if (!function_exists('tooBig')) {
    //数量太大，返回999
    function tooBig($num) {
        if (intval($num) > 1000) {
            $b = sprintf('%.2f',$num/1000);
            $num = $b.'k';
        }else if(intval($num) > 10000){
            $b = sprintf('%.2f',$num/10000);
            $num = $b.'w';
        }

        return $num;
    }
}

//微信curl Get访问
function curlGet($url,$postData = []){
    $url = str_replace('https://', 'http://', $url);
    $ch = curl_init();
    $header = "Accept-Charset: utf-8";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    //curl_setopt($ch, CURLOPT_SSLVERSION, 3);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    //curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result);
    return objarray_to_array($data);
}

// 将对象数组转化成普通数组
function objarray_to_array($obj) {
    $ret = array();
    foreach ($obj as $key => $value) {
        if (gettype($value) == "array" || gettype($value) == "object") {
            $ret[$key] = objarray_to_array($value);
        } else {
            $ret[$key] = $value;
        }
    }
    return $ret;
}

//根据ip获取用户的经纬度
function getAreafromIp() {
    $ip = get_client_ip();
    //  $ip = '218.104.146.64';
    $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=7IZ6fgGEGohCrRKUE9Rj4TSQ&ip={$ip}&coor=bd09ll");
    $json = json_decode($content);
    $arr['longitude'] = $json->{'content'}->{'point'}->{'x'};  //经度
    $arr['latitude'] = $json->{'content'}->{'point'}->{'y'};   //纬度
    $arr['localtion'] = $json->{'content'}->{'address'}; //地区名
    return $arr;
}

//根据用户之间的经纬度计算距离（单位km）
function GetDistance( $lng1,$lat1,$lng2,$lat2) {
    $EARTH_RADIUS = 6378.137;
    $radLat1 = rad($lat1);
    $radLat2 = rad($lat2);
    $a = $radLat1 - $radLat2;
    $b = rad($lng1) - rad($lng2);
    $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
    $s = $s *$EARTH_RADIUS;
    $s = round($s * 10000) / 10000;
    return $s;
}
function rad($d) {
    return $d * 3.1415926535898 / 180.0;
}
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0,$adv=false) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

function GetIP() {
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $cip = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
        $cip = $_SERVER["REMOTE_ADDR"];
    } else {
        $cip = "无法获取！";
    }
    return $cip;
}

/**
 * 判断用户的手机是安卓的还是ios的
 */
function get_device_type() {
    //全部变成小写字母
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $type = 3;/*'other'*/

    //分别进行判断
    if(strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
        $type = 2; /*IOS*/
    }
    if(strpos($agent, 'android')) {
        $type = 1; /*android*/
    }
    return $type;
}



//获取访客操作系统
function GetOs(){
    if(!empty($_SERVER['HTTP_USER_AGENT'])){
        $OS = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/win/i',$OS)) {
            $OS = 'Windows';
        } else if (preg_match('/mac/i',$OS)) {
            $OS = 'MAC';
        } else if (preg_match('/linux/i',$OS)) {
            $OS = 'Linux';
        } else if (preg_match('/unix/i',$OS)) {
            $OS = 'Unix';
        } else if (preg_match('/bsd/i',$OS)) {
            $OS = 'BSD';
        } else {
            $OS = 'Other';
        }
        return $OS;
    } else {
        return "获取访客操作系统信息失败！";
    }
}


//获得访客浏览器类型
function GetBrowser(){
    if(!empty($_SERVER['HTTP_USER_AGENT'])){
        $br = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/MSIE/i',$br)) {
            $br = 'MSIE';
        } else if (preg_match('/Firefox/i',$br)) {
            $br = 'Firefox';
        } else if (preg_match('/Chrome/i',$br)) {
            $br = 'Chrome';
        } else if (preg_match('/Safari/i',$br)) {
            $br = 'Safari';
        } else if (preg_match('/Opera/i',$br)) {
            $br = 'Opera';
        } else {
            $br = 'Other';
        }
        return $br;
    } else {
        return "获取浏览器信息失败！";
    }
}


if (!function_exists('isLogin')) {
    /**
     * 判断后台用户是否已经登录
     * @return array|mixed
     */
    function isLogin()
    {
        if (\think\Session::get('admin')) {
            return \think\Session::get('admin');
        } else {
            return [];
        }
    }
}