<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/11
 * Time: 16:14
 */
if (!function_exists('cp_delDir')) {
    /**
     * 删除目录以及目录下的所有文件
     * @param $dir 目录路径
     * @return bool
     */
    function cp_delDir($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                is_dir("$dir/$file") ? cp_delDir("$dir/$file") : @unlink("$dir/$file");
            }
        }
        if (readdir($handle) == false) {
            closedir($handle);
            @rmdir($dir);
        }
    }
}
if (!function_exists('cp_setCacheFile')) {
    /**
     * 设置缓存文件
     * @param string $fileName 文件名
     * @param string $cacheData 内容
     * @param string $suffix 文件后缀
     * @param string $dir 存储路径 前面不带'/'
     * @return array
     */
    function cp_setCacheFile($fileName, $cacheData, $suffix = '.php', $dir = 'cache_data/temp/')
    {
        $file_path = ROOT_PATH . '/' . $dir .  $fileName . $suffix;
        $bool = cp_directory(ROOT_PATH . '/' . $dir);
        if (!$bool) return ['msg' => '目录创建失败'];
        $my_file = fopen($file_path, "w");
        if ($suffix == '.php') {
            $content = "<?php\r\n";
            $content .= "return " . var_export($cacheData, true) . ";\r\n";
            $content .= "?>";
        } else {
            $content = $cacheData . "\r\n";
        }
        fwrite($my_file, $content);
        fclose($my_file);
    }
}
if (!function_exists('cp_directory')) {
    /**
     * 递归创建目录
     * @param $dir
     * @return bool
     */
    function cp_directory($dir){
        return  is_dir($dir) or cp_directory(dirname($dir)) and  @mkdir($dir,0777,true);
    }
}
if (!function_exists('cp_getCacheFile')) {
    /**
     * 获取缓存文件里面的数据
     * @param string $fileName 文件名
     * @param string $suffix 后缀
     * @param string $dir 目录名 前面不带‘/’
     * @return array
     */
    function cp_getCacheFile($fileName, $suffix = '.php', $dir = 'cache_data/temp/')
    {
        $file = $fileName  . $suffix;
        $result = [];
        if (!empty($result[$fileName]))
        {
            return $result[$fileName];
        }
        $cacheFilePath = ROOT_PATH . '/' . $dir . $file;
        if (file_exists($cacheFilePath))
        {
            $data = include($cacheFilePath);
            $result[$fileName] = $data;
            return $result[$fileName];
        } else {
            return $result;
        }
    }
}
// 应用公共文件
if (!function_exists('cp_rand_award')) {
    /**
     * TODO 抽奖概率算法
     * 不同概率的抽奖原理就是把0到*（比重总数）的区间分块
     * 分块的依据是物品占整个的比重，再根据随机数种子来产生0-* 中的某个数
     * 判断这个数是落在哪个区间上，区间对应的就是抽到的那个物品。
     * 随机数理论上是概率均等的，那么相应的区间所含数的多少就体现了抽奖物品概率的不同。
     * 案例：
     * $arr = [
        ['id'=>1,'name'=>'特等奖','v'=>1],
        ['id'=>2,'name'=>'一等奖','v'=>5],
        ['id'=>3,'name'=>'二等奖','v'=>10],
        ['id'=>4,'name'=>'三等奖','v'=>120],
        ['id'=>5,'name'=>'四等奖','v'=>22],
        ['id'=>6,'name'=>'没中奖','v'=>50]
     ];
     * 测试1W次的结果 TODO 权重值越大 中奖概率越大
     * Array
        (
            [6] => 2449
            [4] => 5751
            [3] => 489
            [5] => 1056
            [2] => 220
            [1] => 35
        )
     * @param $proArr 被抽奖的数组
     * @return array
     */
    function cp_rand_award($proArr) {
        $result = array();
        foreach ($proArr as $key => $val) {
            $arr[$key] = $val['v'];
        }
        // 概率数组的总概率
        $proSum = array_sum($arr);
        asort($arr);
        // 概率数组循环
        foreach ($arr as $k => $v) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v) {
                $result = $proArr[$k];
                break;
            } else {
                $proSum -= $v;
            }
        }
        return $result;
    }
}


if (!function_exists('cp_object2array')) {
    /**
     * 对象转换为数组
     * @param $object
     * @return mixed
     */
    function cp_object2array($object) {
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }
}

if (!function_exists('cp_encrypt')) {
    /**
     * 加密方法
     * @param string $data 要加密的字符串
     * @param string $key  加密密钥
     * @param int $expire  过期时间 单位 秒
     * @return string
     */
    function cp_encrypt($data, $key = '', $expire = 0) {
        $key  = md5(empty($key) ? '' : $key);
        $data = base64_encode($data);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char = '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }

        $str = sprintf('%010d', $expire ? $expire + time():0);

        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
        }
        return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
    }
}

if (!function_exists('cp_decrypt')) {
    /**
     * 解密方法
     * @param  string $data 要解密的字符串 （必须是cp_encrypt方法加密的字符串）
     * @param  string $key  加密密钥
     * @return string
     */
    function cp_decrypt($data, $key = ''){
        $key    = md5(empty($key) ? '' : $key);
        $data   = str_replace(array('-','_'),array('+','/'),$data);
        $mod4   = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        $data   = base64_decode($data);
        $expire = substr($data,0,10);
        $data   = substr($data,10);

        if($expire > 0 && $expire < time()) {
            return '';
        }
        $x      = 0;
        $len    = strlen($data);
        $l      = strlen($key);
        $char   = $str = '';

        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char .= substr($key, $x, 1);
            $x++;
        }

        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            }else{
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return base64_decode($str);
    }
}

if (!function_exists('cp_encrypt_info')) {
    /**
     * 加密信息集合
     * @param $data
     * @return string
     */
    function cp_encrypt_info($data)
    {
        $temp = [];
        foreach ($data as $k => $v) {
            $temp[] = $v . '#' . $k;
        }
        return cp_encrypt(implode(',',$temp));
    }
}

if (!function_exists('cp_decrypt_info')) {
    /**
     * 解密信息集合 [必须是 cp_encrypt_info 加密]
     * @param $str
     * @return array
     */
    function cp_decrypt_info($str)
    {
        $temp = [];
        $info  = cp_decrypt($str);
        $data = explode(',',$info);
        foreach ($data as $k => $v) {
            $temp[] = explode('#',$v);
        }
        $return = [];
        foreach ($temp as $k => $v) {
            $return[$v[1]] = $v[0];
        }
        return $return;
    }
}

if (!function_exists('cp_keyWrods_replace')) {
    /**
     * 替换关键字并且写入样式
     * @param $keywords 查询的关键字
     * @param $content  查询的内容
     * @return mixed
     */
    function cp_keyWrods_replace($keywords,$content){
        $str = "<span style='color: #D2322D;font-weight: 700;'>{$keywords}</span>";
        return str_replace($keywords,$str,$content);
    }
}

if (!function_exists('cp_time_format')) {
    /**
     * 格式化时间
     * @param $time
     * @return bool|string
     */
    function cp_time_format($time){
        //获取当前时间
        $now = time();
        //今天零时零分零秒
        $today = strtotime(date('y-m-d',$now));
        //当前时间与传递时间相差的秒数
        $diff = $now - $time;
        switch ($time) {
            case $diff < 60 :
                $str = $diff . ' 秒前';
                break;
            case $diff < 3600 :
                $str = floor($diff / 60) . ' 分钟前';
                break;
            case $diff < (3600 * 8) :
                $str = floor($diff / 3600) . ' 小时前';
                break;
            case $time > $today :
                $str = '今天&nbsp;&nbsp;' . date('H:i',$time);
                break;
            default:
                $str = date('Y-m-d H:i',$time);
                break;
        }
        return $str;

    }
}

if (!function_exists('cp_isMobile')) {
    /**
     * 验证手机
     * @param string $subject
     * @return boolean
     */
    function cp_isMobile($subject = '') {
        $pattern = "/^1[34578]{1}\d{9}$/";
        if (preg_match($pattern, $subject)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('cp_isEmail')) {
    /**
     * 验证是否是邮箱
     * @param  string  $email 邮箱
     * @return boolean        是否是邮箱
     */
    function cp_isEmail($email){
        if(filter_var($email,FILTER_VALIDATE_EMAIL)){
            return true;
        }else{
            return false;
        }
    }
}

if (!function_exists('cp_is_url')) {
    /**
     * 验证是否是URL地址
     * @param  string  $email 邮箱
     * @return boolean  是否是邮箱
     */
    function cp_is_url($url){
        if(filter_var($url,FILTER_VALIDATE_URL)){
            return true;
        }else{
            return false;
        }
    }
}


if (!function_exists('cp_is_ip')) {
    /**
     * 验证是否是URL地址
     * @param  string  $email 邮箱
     * @return boolean  是否是邮箱
     */
    function cp_is_ip($ip){
        if(filter_var($ip,FILTER_VALIDATE_IP)){
            return true;
        }else{
            return false;
        }
    }
}

if (!function_exists('cp_replace_phone')) {
    /**
     * 替换手机号码
     * @param $str
     * @return string
     */
    function cp_replace_phone($str){
        $start = substr($str,0,3);
        $end = substr($str,-4);
        return $start . "****" . $end;
    }
}


if (!function_exists('cp_randomFloat')) {
    /**
     * 随机生成0~0.1之间的数,并且保留指定位数
     * @param int $min 最小值
     * @param float $max 最大值
     * @param int $num  要取多少位数 默认2位
     * @param int $type 返回类型 true ：四舍五入制返回指定位数 false : 不是四舍五入
     * @return string
     */
    function cp_randomFloat($num = 2, $type = true, $min = 0, $max = 0.1) {
        $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        if ($type === true) {
            // 四舍五入 保留指定位数
            return sprintf("%.{$num}f", $rand);
        } else {
            // 不是四舍五入 保留指定位数
            $new = $num + 1;
            return sprintf("%.{$num}f",substr(sprintf("%.{$new}f", $rand), 0, -$num));
        }
    }
}

if (!function_exists('cp_mbs_strlen')) {
    /**
     * 计算中英文字符长度
     * @param $str
     * @return int
     */
    function cp_mbs_strlen($str){
        preg_match_all("/./us", $str, $matches);
        return count(current($matches));
    }
}


if (!function_exists('cp_checkEvenNum')) {
    /**
     * 检测数字是否为偶数
     * @param $num 数值
     * @return bool
     */
    function cp_checkEvenNum($num)
    {
        if((abs($num)+2)%2==1){
            return false;
        }else{
            return true;
        }
    }
}

if (!function_exists('cp_isArraySame')) {
    /**
     * 比较2个数组是否相等 二维数组
     * @param $arr1 数组1
     * @param $arr2 数组2
     * @return bool
     */
    function cp_isArraySame ($arr1,$arr2){
        foreach ($arr1 as $key => $v) {
            if(isset($arr2[$key])){
                if($arr2[$key] !=  $arr1[$key]){
                    return false;
                }
            }else{
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('cp_encryption_password')) {
    /**
     * 密码加密
     * @param $value
     * @param string $key
     * @return string
     */
    function cp_encryption_password($value, $key = 'Ma2018')
    {
        return md5(md5(md5($value . $key)));
    }
}

if (!function_exists('cp_array_sort')) {
    /**
     * 二维数组 指定字段排序
     * @param $array  要排序的数组
     * @param $row    排序依据列 指定的键位
     * @param $type   排序类型[asc or desc]
     * @return array  排好序的数组
     */
    function cp_array_sort($array,$row,$type){
        $array_temp = array();
        foreach($array as $v){
            $array_temp[$v[$row]] = $v;
        }
        if($type == 'asc'){
            ksort($array_temp);
        }elseif($type='desc'){
            krsort($array_temp);
        }else{
        }
        return $array_temp;
    }
}

if (!function_exists('cp_get_ip_info')) {
    /**
     * 获取ip的详细信息
     * 163.125.127.241
     * 返回信息 国家/地区	省份	   城市	  县	  运营商
     *          中国        广东省  深圳市  *  联通
     * @param $ip ip地址
     * @return mixed
     */
    function cp_get_ip_info($ip)
    {
        // 淘宝开源api 淘宝IP地址库
        $taobaoUrl = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $taobaoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ( $ch,  CURLOPT_NOSIGNAL,true);//支持毫秒级别超时设置
        curl_setopt($ch, CURLOPT_TIMEOUT, 1200);   //1.2秒未获取到信息，视为定位失败
        $myCity = curl_exec($ch);
        curl_close($ch);

        $myCity = json_decode($myCity, true);
        return $myCity;
    }
}

if (!function_exists('cp_get_client_ip')) {
    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function cp_get_client_ip($type = 0,$adv=false) {
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
}


if (!function_exists('cp_substr_cut')) {
    /**
     * 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
     * @param string $user_name 姓名
     * @return string 格式化后的姓名
     */
    function cp_substr_cut($user_name){
        $strlen   = mb_strlen($user_name, 'utf-8');
        $firstStr   = mb_substr($user_name, 0, 1, 'utf-8');
        $lastStr   = mb_substr($user_name, -1, 1, 'utf-8');
        return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strlen - 2) . $lastStr;
    }
}



if (!function_exists('cp_func_substr_replace')) {
    /**
     * 隐藏部分字符串
     * # 此方法多用于手机号码或身份证号、银行卡号的中间部分数字的隐藏
     * @param $start 前面显示的数量
     * @param $length 替换字符转的数量
     */
    function cp_func_substr_replace($str, $replacement = '*', $start = 1, $length = 3)
    {
        $len = mb_strlen($str, 'utf-8');
        if ($len > intval($start + $length)) {
            $str1 = mb_substr($str, 0, $start, 'utf-8');
            $str2 = mb_substr($str, intval($start + $length), NULL, 'utf-8');
        } else {
            $str1 = mb_substr($str, 0, 1, 'utf-8');
            $str2 = mb_substr($str, $len - 1, 1, 'utf-8');
            $length = $len - 2;
        }
        $new_str = $str1;
        for ($i = 0; $i < $length; $i++) {
            $new_str .= $replacement;
        }
        $new_str .= $str2;
        return $new_str;
    }

}
