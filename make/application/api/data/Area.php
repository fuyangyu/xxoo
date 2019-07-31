<?php
namespace app\api\data;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/8
 * Time: 9:24
 */
class Area
{
    // 对象实例
    protected static $instance;

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
     * 创建插件所需要的json数据
     * @return \think\response\Json
     */
    public function createJson()
    {
        $data = $this->getCacheFile('area');
        $tree = \cocolait\helper\CpData::node_merge($data,0);
        $json = [];
        if ($tree) {
            foreach ($tree as $k => $v) {
                $json[$k] = ['p' => $v['name'], 'c' => []];
                if ($v['child']) {
                    foreach ($v['child'] as $k2 => $v2) {
                        if ($v2['child']) {
                            $json[$k]['c'][$k2] = ['n' => $v2['name'], 'a' => []];

                            foreach ($v2['child'] as $k3 => $v3) {
                                $json[$k]['c'][$k2]['a'][] = ['s' => $v3['name']];
                            }
                        } else {
                            $json[$k]['c'][] = ['n' => $v2['name']];
                        }
                    }
                }
            }
        }
        // 创建cityselect插件json数据
        return json(["citylist" => $json]);
    }


    /**
     * 创建插件IOS所需要的json数据
     * @return \think\response\Json
     */
    public function createIosJson()
    {
        $data = $this->getCacheFile('area');
        $tree = \cocolait\helper\CpData::node_merge($data,0);
        $json = [];
        /*$a = [
            '北京市' => [
                '北京市' => [
                    ['Item0' => '朝阳区', 'Item1' => '丰台区']
                ]
            ]
        ];*/
        $res = [];
        if ($tree) {
            $temp = [];
            foreach ($tree as $k => $v) {
                if ($v['child']) {
                    foreach ($v['child'] as $k2 => $v2) {
                       if ($v2['child']) {
                            $temp[$k2] = [];
                            foreach ($v2['child'] as $k3 => $v3) {
                                $temp[$k2]['Item' . $k3] = $v3['name'];
                            }
                            $res[$v['name']][$v2['name']] = $temp[$k2];
                       }
                    }
                }
            }
        }
        return json(["Root" => $res]);
    }

    /**
     * 获取省市区
     * @param int $id
     * @param int $level
     * @return array
     */
    public function getArea($id = 0, $level = 1)
    {
        $data = [];
        switch($level)
        {
            case 1:
                $data = $this->getProvinceData();
                break;
            case 2:
                $data = $this->getCityData($id);
                break;
            case 3:
                $data = $this->getDistrictData($id);
                break;
        }
        return $data;
    }

    /**
     * 获取省份
     * @return array
     */
    public function getProvinceData()
    {
        $data = $this->getCacheFile('area');
        $province = [];
        foreach ($data as $k => $v) {
            if ($v['level'] == 1 && $v['pid'] == 0) {
                $province[$k]['id'] = $v['id'];
                $province[$k]['pid'] = $v['pid'];
                $province[$k]['name'] = $v['name'];
                $province[$k]['level'] = $v['level'];
            }
        }
        return array_values($province);
    }

    /**
     * 获取市区
     * @param $id
     * @return array
     */
    public function getCityData($id)
    {
        $data = $this->getCacheFile('area');
        $city = [];
        foreach ($data as $k => $v) {
            if ($v['level'] == 2 && $v['pid'] == $id) {
                $city[$k]['id'] = $v['id'];
                $city[$k]['pid'] = $v['pid'];
                $city[$k]['name'] = $v['name'];
                $city[$k]['level'] = $v['level'];
            }
        }
        return array_values($city);
    }

    /**
     * 获取区
     * @param $id
     * @return array
     */
    protected function getDistrictData($id)
    {
        $data = $this->getCacheFile('area');
        $district = [];
        foreach ($data as $k => $v) {
            if ($v['level'] == 3 && $v['pid'] == $id) {
                $district[$k]['id'] = $v['id'];
                $district[$k]['pid'] = $v['pid'];
                $district[$k]['name'] = $v['name'];
                $district[$k]['level'] = $v['level'];
            }
        }
        return array_values($district);
    }

    /**
     * 获取缓存文件
     * @param $fileName
     * @param string $suffix
     * @param string $dir
     * @return array
     */
    protected function getCacheFile($fileName, $suffix = '.php', $dir = 'cache_data/temp/')
    {
        $file = $fileName  . $suffix;
        static $result = [];
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