/**
 * 公共上传组件
 */
layui.define(['layer','upload'], function(exports){
    var upload = layui.upload //末尾不要加分号 ";"
        ,layer = layui.layer

    // 单文件上传文件
    upload.render({
        elem: '#cp_upload' // 触发元素绑定
        ,url: _upload_url // 请求地址
        ,field:'files'// 设定文件域的字段名
        ,auto:true // 选择文件后是否自动触发上传
        ,size : 2048 // 限制文件上传的大小
        ,multiple:false // 是否允许多文件上传
        ,method:'post' // 请求方式
        ,accept: 'images' //只能选择图片文件
        ,acceptMime: 'image/*' // 默认选择时只显示图片文件
        ,exts:'png|gif|jpeg|jpg' // 限制上传文件的类型
        ,before: function(obj){ //obj参数包含的信息，跟 choose回调完全一致，可参见上文。
            layer.load(2); //上传loading
        }
        ,done: function(res, index, upload){
            layer.closeAll('loading'); //关闭loading
            if (res.status == 0 || !res.status) {
                layer.alert(res.msg,{icon:2},function(index){
                    layer.close(index);
                })
            } else {
                $("#cp-show-img-box").show();
                var _html = '';
                _html += '<div class="up-div">';
                _html += '<a href="'+ res.msg +'" target="_blank" title="图片">';
                _html += '<img src="'+ res.msg +'"></a>';
                _html += '<button class="btn btn-warning btn-circle btn-cc" did="'+ res.msg +'" type="button"><i class="fa fa-times"></i></button></div>';
                $("#cp-show-img-box").html('');
                $("#cp-show-img-box").html(_html);
                $("#cp-find-img").attr('value',res.msg);
            }
        }
        ,error: function(index, upload){
            layer.closeAll('loading'); //关闭loading
        }
    });


    // 多文件上传
    upload.render({
        elem: '#cp_upload_all' // 触发元素绑定
        ,url: _upload_url // 请求地址
        ,field:'files'// 设定文件域的字段名
        ,auto:true // 选择文件后是否自动触发上传
        ,size : 2048 // 限制文件上传的大小
        ,multiple:true // 是否允许多文件上传
        ,method:'post' // 请求方式
        ,accept: 'images' //只能选择图片文件
        ,acceptMime: 'image/*' // 默认选择时只显示图片文件
        ,exts:'png|gif|jpeg|jpg' // 限制上传文件的类型
        ,before: function(obj){ //obj参数包含的信息，跟 choose回调完全一致，可参见上文。
            layer.load(2); //上传loading
        }
        ,done: function(res, index, upload){
            layer.closeAll('loading'); //关闭loading
            if (res.status == 0 || !res.status) {
                layer.alert(res.msg,{icon:2},function(index){
                    layer.close(index);
                })
            } else {
                var _img = $("#cp-all-img").val();
                if (_img) {
                    _img = _img + "@" + res.msg;
                } else {
                    _img = res.msg;
                }
                $("#cp-show-img-box").show();
                var _html = '';
                _html += '<div class="up-div">';
                _html += '<a href="'+ res.msg +'" target="_blank" title="图片">';
                _html += '<img src="'+ res.msg +'"></a>';
                _html += '<button class="btn btn-warning btn-circle btn-cc" did="'+ res.msg +'" type="button"><i class="fa fa-times"></i></button></div>';
                $("#cp-show-img-box").append(_html);
                $("#cp-all-img").attr('value',_img);
            }
        }
        ,error: function(index, upload){
            layer.closeAll('loading'); //关闭loading
        }
    });

    exports('uploadFile', {});
});