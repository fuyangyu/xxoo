/**
 * 全局删除执行操作
 */
layui.define(['layer'], function(exports){
    var layer = layui.layer

    // 批量导出
    $("#cp-down").on('click',function(){
        layer.prompt({
            formType: 2,
            value: '输入页码 比如你想要1-5页的数据 输入1-5 点击确定后系统会自动导出',
            title: '批量导出数据到Excel',
            area: ['300px', '55px'] //自定义文本域宽高
        }, function(value, index, elem){
            layer.close(index);
            $("#cp-hidden-value").attr('value',value);
            $("#downData").submit();
        });
    });
    exports('down', {});
});