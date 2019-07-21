/**
 * 远程弹出层
 */
layui.define(['layer'], function(exports){
    var layer = layui.layer;

    $(".cp-open-box").on('click',".cp-open-url",function(){
        var _url = $(this).attr('url');
        var _title = $(this).attr('title');
        var _width = $(this).attr('open-width');
        if (!_title) {
            _title = '';
        }
        if (!_width) {
            _width = '1000px';
        }
        layer.open({
            type: 2,
            title: _title,
            shadeClose: true,
            shade: 0.5,
            area: [_width, '95%'],
            content: _url
        });
    });

    $("#cp-add-open-box").on('click',function(){
        var _url = $(this).attr('url');
        var _title = $(this).attr('title');
        var _width = $(this).attr('open-width');
        if (!_title) {
            _title = '';
        }
        if (!_width) {
            _width = '1000px';
        }
        layer.open({
            type: 2,
            title: _title,
            shadeClose: true,
            shade: 0.5,
            area: [_width, '95%'],
            content: _url
        });
    });
    exports('open', {});
});