<?php
namespace app\index\controller;
use think\Controller;
use think\Db;

class Cron extends Controller
{
    /**
     * 每日凌晨10分结算静态分佣
     * 脚本执行时间 10 0 * * * /usr/bin/curl  http://www.dotgomedia.com/index.php/cron/send
     * @return string
     * @throws \think\Exception
     */
    public function send()
    {
        $total = Db::name('earnings')->where(['send_id' => 666])->value('static_total_money');
        if ($total > 0 && $total) {
            $data = Db::name('member')
                ->where(['member_class' => ['in',[2,3,4]]])
                ->field('uid,member_class')
                ->select();
            if ($data) {
                // 创建佣金记录
                $insert_hire_log = [];
                $money = $this->getCalculateMoney($total,$data);
                foreach ($data as $k => $v) {
                    $hire_money = $this->calculate($money,$v['member_class']);
                    $insert_hire_log[$k] = [
                        'uid' => $v['uid'],
                        'user_level' => $v['member_class'],
                        'lower_uid' => 0,
                        'lower_parent_level' => 0,
                        'hire_money' => substr(sprintf("%.3f",$hire_money),0,-1),
                        'ratio' => 0,
                        'type_id' => 0,
                        'type_log_id' => 0,
                        'hire_type' => 3,
                        'add_time' => time(),
                        'is_check' => 1,
                        'check_time' => date('Y-m-d H:i:s'),
                        'order_sn' => ''
                    ];
                }
                // 给所具备静态资金池用户加余额
                Db::startTrans();
                $log_cu = count($insert_hire_log);
                $log_num = Db::name('hire_log')->insertAll($insert_hire_log);
                $auto_id = 0;
                foreach ($insert_hire_log as $k => $v) {
                    $auto_id = Db::name('member')->where(['uid' => $v['uid']])->setInc('balance',$v['hire_money']);
                }
                $earnings_id = Db::name('earnings')->where(['send_id' => 666])->setField('static_total_money',0);
                if ($log_cu == $log_num && $auto_id && $earnings_id) {
                    Db::commit();
                    return '操作成功';
                } else {
                    Db::rollback();
                    $log = ['date' => date('Y-m-d H:i:s'),'msg' => '操作失败,原因:auto_id->' . $auto_id . ":hire_log_id->" . $log_num . ':总记录数->' . $log_cu];
                    $this->log($log);
                    return '操作失败,原因:auto_id->' . $auto_id . ":hire_log_id->" . $log_num . ':总记录数->' . $log_cu;
                }
            } else {
                $log = ['date' => date('Y-m-d H:i:s'),'msg' => '没有具备条件的会员'];
                $this->log($log);
                return '没有具备条件的会员';
            }
        } else {
            return '今日没有静态收益或者今日已更新';
        }
    }

    /**
     * 计算出会员级别所对应的佣金比列
     * @param $total
     * @param $data
     * @return float
     */
    protected function getCalculateMoney($total,$data)
    {
        $init = [
            'vip' => 0,
            'g_vip' => 0,
            'server' => 0
        ];
        foreach ($data as $k => $v) {
            if ($v['member_class'] == 2) {
                $init['vip']++;
            }
            if ($v['member_class'] == 3) {
                $init['g_vip']++;
            }
            if ($v['member_class'] == 4) {
                $init['server']++;
            }
        }
        $money = $total / ($init['vip']*1+$init['g_vip']*2+$init['server']*5);
        return $money;
    }

    /**
     * 根据会员等级来计算每个会员级别所对应的静态资金池所占有
     * @param int $money  会员金额所占比列
     * @param int $level 会员等级
     * @return float|int
     */
    protected function calculate($money,$level)
    {
        $count = 0;
        switch($level)
        {
            case 2:
                // VIP
                $count = $money * 1;
                break;
            case 3:
                // 高级VIP
                $count = $money * 2;
                break;
            case 4:
                $count = $money * 5;
                // 服务中心
                break;
        }
        return $count;
    }

    protected function log($data){
        $log_file = ROOT_PATH . '/cache_data/cron.log';
        $content = var_export($data,true);
        $content .= "\r\n\n";
        file_put_contents($log_file,$content, FILE_APPEND);
    }
}
