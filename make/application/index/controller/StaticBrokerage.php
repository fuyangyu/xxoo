<?php
namespace app\index\controller;
use think\Db;
//use think\Log;
//class StaticBrokerage
//{

    /**
     * 静态佣金发放脚本
     * @throws \think\Exception
     */
//    public function staticBrokerage(){
        set_time_limit(0);
        ini_set("memory_limit","500M");
        $vip_messageLog = $svip_messageLog = $serve_messageLog = array();
        //获取各等级用户数量
        $data = cp_getCacheFile('system');
        $vip_num = isset($data['vip_num']) ? $data['vip_num'] : '1000';
        $svip_num = isset($data['svip_num']) ? $data['svip_num'] : '500';
        $serve_num = isset($data['serve_num']) ? $data['serve_num'] : '100';
        //本月会员收入
        $currentMemberSql = "SELECT SUM(money) as member_money FROM wld_pay_log WHERE pay_status = 2 and from_unixtime(pay_time,'%Y-%m') = date_format(now(), '%Y-%m');";
        $member = Db::query($currentMemberSql);
        $member_money = !empty($member[0]['member_money'])?$member[0]['member_money']:0;
        //本月任务收入
        $currentTaskSql = "SELECT SUM(task_money) as task_money FROM wld_send_task_log WHERE is_check = 1 AND date_format(check_time, '%Y-%m') = date_format(now(), '%Y-%m');";
        $task = Db::query($currentTaskSql);
        $task_money = !empty($task[0]['task_money'])?$task[0]['task_money']:0;
        //计算静态各等级静态收益 36+1.5
        $vip_money = sprintf("%.2f",(($member_money*0.03 + $task_money*0.05)*0.8/0.06*1)/$vip_num);
        $svip_money = sprintf("%.2f",(($member_money*0.03 + $task_money*0.05)*0.8/0.06*2)/$svip_num);
        $serve_money = sprintf("%.2f",(($member_money*0.03 + $task_money*0.05)*0.8/0.06*3)/$serve_num);
        //等级为VIP的会员
        if($vip_money > 0) {
            //获取VIP会员用户
            $vipData = Db::name('member')->where('member_class', 2)->field('uid,phone')->select();
            if ($vipData) {
                foreach ($vipData as $k => $item) {
                    Db::name('member')->where('uid', $item['uid'])->setInc('static_money', $vip_money);
                    $vip_channel = [
                        'uid' => $item['uid'], 'phone' => $item['phone'], 'member_class' => 2, 'channel_money' => $vip_money,
                        'channel_time' => date('Y-m'), 'add_time' => date('Y-m-d H:i:s'),
                    ];
                    //静态佣金记录
                    $vip_id = Db::name('channel_log')->insertGetId($vip_channel);
                    //静态分佣记录数据拼装
                    $vip_messageLog[$k] = [
                        'uid' => $item['uid'], 'did' => $vip_id, 'content' => date('Y年m月') . '会员静态收益', 'type' => 2,
                        'status' => 1, 'add_time' => date('Y-m-d H:i:s'),
                    ];
                }
                Db::name('message_log')->insertAll($vip_messageLog);
            }
        }
        if($svip_money > 0) {
            //获取SVIP会员用户
            $svipData = Db::name('member')->where('member_class', 3)->field('uid,phone')->select();
            if ($svipData) {
                foreach ($svipData as $k => $v) {
                    Db::name('member')->where('uid', $v['uid'])->setInc('static_money', $svip_money);
                    $svip_channel = [
                        'uid' => $v['uid'], 'phone' => $v['phone'], 'member_class' => 3, 'channel_money' => $svip_money,
                        'channel_time' => date('Y-m'), 'add_time' => date('Y-m-d H:i:s'),
                    ];
                    //静态佣金记录
                    $svip_id = Db::name('channel_log')->insertGetId($svip_channel);
                    //静态分佣记录数据拼装
                    $svip_messageLog[$k] = [
                        'uid' => $v['uid'], 'did' => $svip_id, 'content' => date('Y年m月') . '会员静态收益', 'type' => 2,
                        'status' => 1, 'add_time' => date('Y-m-d H:i:s'),
                    ];
                }
                Db::name('message_log')->insertAll($svip_messageLog);
            }
        }
        if($serve_money > 0) {
            //获取服务中心会员用户
            $serveData = Db::name('member')->where('member_class', 4)->field('uid,phone')->select();
            if ($serveData) {
                foreach ($serveData as $k => $value) {
                    Db::name('member')->where('uid', $value['uid'])->setInc('static_money', $serve_money);
                    $serve_channel = [
                        'uid' => $value['uid'], 'phone' => $value['phone'], 'member_class' => 4, 'channel_money' => $serve_money,
                        'channel_time' => date('Y-m'), 'add_time' => date('Y-m-d H:i:s'),
                    ];
                    //静态佣金记录
                    $serve_id = Db::name('channel_log')->insertGetId($serve_channel);
                    //静态分佣记录数据拼装
                    $serve_messageLog[$k] = [
                        'uid' => $value['uid'], 'did' => $serve_id, 'content' => date('Y年m月') . '会员静态收益', 'type' => 2,
                        'status' => 1, 'add_time' => date('Y-m-d H:i:s'),
                    ];
                }
                Db::name('message_log')->insertAll($serve_messageLog);
            }
        }
//    }
//}
