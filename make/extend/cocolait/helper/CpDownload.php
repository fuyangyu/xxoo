<?php
namespace cocolait\helper;
/** php下载类,支持断点续传
 *  download: 下载文件
 *  setSpeed: 设置下载速度
 *  getRange: 获取header中Range
 */
final class CpDownload{
    private static $_speed = 512;  // 下载速度

    /**
     * 下载
     * @param String $file  要下载的文件路径
     * @param String $name  文件名称,为空则与下载的文件名称一样
     * @param boolean $reload 是否开启断点续传
     */
    public static function download($file, $name='', $reload=false){
        if(file_exists($file)){
            if($name==''){
                $name = basename($file);
            }

            $fp = fopen($file, 'rb');
            $file_size = filesize($file);
            $ranges = self::getRange($file_size);

            header('cache-control:public');
            header('content-type:application/octet-stream');
            header('content-disposition:attachment; filename='.$name);

            if($reload && $ranges!=null){ // 使用续传
                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges:bytes');

                // 剩余长度
                header(sprintf('content-length:%u',$ranges['end']-$ranges['start']));

                // range信息
                header(sprintf('content-range:bytes %s-%s/%s', $ranges['start'], $ranges['end'], $file_size));

                // fp指针跳到断点位置
                fseek($fp, sprintf('%u', $ranges['start']));
            }else{
                header('HTTP/1.1 200 OK');
                header('content-length:'.$file_size);
            }

            while(!feof($fp)){
                echo fread($fp, round(self::$_speed*1024,0));
                ob_flush();
                //sleep(1); // 用于测试,减慢下载速度
            }
            ($fp!=null) && fclose($fp);
        }else{
            return false;
        }
    }

    // 下载app
    public static function downApp($filename, $type = 'android')
    {
        $app = ['android','ios'];
        if(!file_exists($filename)) return ['msg' => '文件错误'];
        if (!in_array($type,$app)) return ['msg' => '下载类似不包括' . $type];
        if ($type == 'android') {
            // android包apk下载 的专属头文件
            header('application/vnd.android.package-archive');
        } else {
            // ios专属现在头文件
            header('application/iphone');
        }
        header('Content-Type: application/octet-stream');
        header("Content-Length: " . filesize($filename));
        //这个头文件是为了下载时显示文件大小的，如果没有此头部，(手机)下载时不会显示大小
        header("Content-Disposition: attachment; filename=".basename($filename));
        readfile($filename);
    }

    /** 设置下载速度
     * @param int $speed
     */
    public static function setSpeed($speed){
        if(is_numeric($speed) && $speed>16 && $speed<4096){
            self::$_speed = $speed;
        }
    }

    /** 获取header range信息
     * @param int  $file_size 文件大小
     * @return Array
     */
    private static function getRange($file_size){
        if(isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE'])){
            $range = $_SERVER['HTTP_RANGE'];
            $range = preg_replace('/[\s|,].*/', '', $range);
            $range = explode('-', substr($range, 6));
            if(count($range)<2){
                $range[1] = $file_size;
            }
            $range = array_combine(array('start','end'), $range);
            if(empty($range['start'])){
                $range['start'] = 0;
            }
            if(empty($range['end'])){
                $range['end'] = $file_size;
            }
            return $range;
        }
        return null;
    }
}