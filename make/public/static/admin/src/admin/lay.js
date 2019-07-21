/**
 * 时间模块 - 公用模块
 * Created by admin on 2017/4/30.
 */
layui.define(['layer', 'laydate'], function(exports){
    var layer = layui.layer
        ,laydate = layui.laydate;

    //  格式化当前时间
    // 对Date的扩展，将 Date 转化为指定格式的String
    // 月(M)、日(d)、小时(h)、分(m)、秒(s)、季度(q) 可以用 1-2 个占位符，
    // 年(y)可以用 1-4 个占位符，毫秒(S)只能用 1 个占位符(是 1-3 位的数字)
    // 例子：
    // (new Date()).Format("yyyy-MM-dd hh:mm:ss.S") ==> 2006-07-02 08:09:04.423
    // (new Date()).Format("yyyy-M-d h:m:s.S")      ==> 2006-7-2 8:9:4.18
    Date.prototype.Format = function (fmt) { //author: meizz
        var o = {
            "M+": this.getMonth() + 1, //月份
            "d+": this.getDate(), //日
            "h+": this.getHours(), //小时
            "m+": this.getMinutes(), //分
            "s+": this.getSeconds(), //秒
            "q+": Math.floor((this.getMonth() + 3) / 3), //季度
            "S": this.getMilliseconds() //毫秒
        };
        if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
        for (var k in o)
            if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
        return fmt;
    };

    var _times = (new Date()).Format("yyyy-MM-dd hh:mm:ss");


    laydate.render({
        elem: '#cp_start_time', //指定元素
        type: 'datetime',
        isclear: true, //是否显示清空
        istoday: true, //是否显示今天
        festival: false //是否显示节日
        //min: '2017-01-01 00:00:00' //最小日期
        ,max: _times
        ,choose: function(datas){
            start.max = datas; //结束日选好后，重置开始日的最大日期
        }
    });

    laydate.render({
        elem: '#cp_end_time', //指定元素
        type: 'datetime',
        istime: true, //是否开启时间选择
        isclear: true, //是否显示清空
        istoday: true, //是否显示今天
        festival: false //是否显示节日
        //min: '2017-01-01 00:00:00', //最小日期
        //max: _times //最大日期
        //start: '2014-6-15 23:00:00'  //开始日期
        ,choose: function(datas){
            end.min = datas; //开始日选好后，重置结束日的最小日期
            end.start = datas //将结束日的初始值设定为开始日
        }
    });

    exports('lay', {});
});