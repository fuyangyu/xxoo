/**
 * 全局删除执行操作
 */
layui.define(['layer','jquery'], function(exports){
    var $ = layui.jquery //末尾不要加分号 ";"
        ,layer = layui.layer

    $("#rm-model").on('click','.cp-rm',function(){
        var _this = $(this);
        layer.confirm('确定要移除？', {
            btn: ['确定'], //按钮
            icon:2
        }, function(index){
            layer.close(index);
            var _id = _this.attr('did');
            var _url = _this.attr('url');
            var _parents = _this.parents('.cp-rm-tr');
            var load = layer.load(2);
            $.ajax({
                url: _url,
                data: {'id' : _id},
                method: "POST",
                async : true,
                dataType : "json",
                success: function (data) {
                    layer.close(load);
                    if (!data.code) {
                        _parents.fadeOut("slow");
                    } else {
                        layer.alert(data.msg,{icon:5},function(index){
                            layer.close(index);
                        });
                    }
                }
            });
        });
    });
    exports('rm', {});
});