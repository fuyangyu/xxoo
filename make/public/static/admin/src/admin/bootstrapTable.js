/**
 * table - 公用模块
 * Created by admin on 2018/5/16 0016.
 */
layui.define(['layer'], function(exports){
    var layer = layui.layer;

    // 初始化表格
    initTable();

    function  initTable() {
        // 初始化表格
        $('#exampleTableEvents').bootstrapTable({
            method: 'get',
            url: _get_table_url,//后台接口地址
            dataType: "json",
            pagination: true, //分页
            search: false, //显示搜索框，是否显示表格搜索，此搜索是客户端搜索，不会进服务端
            strictSearch: false,//设置为 true启用全匹配搜索，否则为模糊搜索
            showPaginationSwitch: true, // 是否显示隐藏分页
            striped: true, //是否显示行间隔色
            pageNumber: 1, //初始化加载第一页，默认第一页
            pageSize: 15,//每页的记录行数
            pageList:[15,20,25,30,50],//分页步进值
            showRefresh:true,//刷新按钮
            showColumns:true,//是否显示所有的列

            sortable: false,//是否启用排序
            sortOrder: "asc",//排序方式
            uniqueId: "id",//每一行的唯一标识，一般为主键列
            showToggle:false,//是否显示详细视图和列表视图的切换按钮
            cardView: false,//是否显示详细视图
            detailView: false,//是否显示父子表

            queryParamsType:'limit',//查询参数组织方式
            queryParams:queryParams,//请求服务器时所传的参数

            cache: false,//是否使用缓存，默认为true，所以一般情况下需要设置一下这个属性（*）
            locale:'zh-CN',//中文支持
            sidePagination: "server", //服务端处理分页
            responseHandler:function(res){
                // 服务端返回数据
                return res;
            },
            columns: _json_columns
        });
    }

    // 重新加载表格
    function refreshTable()
    {
        $('#exampleTableEvents').bootstrapTable(('refresh'));
    }

    // 搜索按钮触发事件
    $("#eventquery").on('click', function() {
        refreshTable();
    });


    // 清空赛选条件
    $("#cp-refresh").on('click', function(){
        $("#cp-table-box").find("input").val('');
        $("#cp-table-box").find("select option").prop("selected", false);
        refreshTable();
    });

    /**
     * 判断对象|数组 是否为空
     * @param  Array  obj
     * @return Bool
     */
    function isEmpty(obj) {
        // 本身为空直接返回true
        if (obj == null) return true;
        // 然后可以根据长度判断，在低版本的ie浏览器中无法这样判断。
        if (obj.length > 0)    return false;
        if (obj.length === 0)  return true;
        //最后通过属性长度判断。
        for (var key in obj) {
            if (hasOwnProperty.call(obj, key)) return false;
        }
        return true;
    }


    // 获取被选中的值
    function getCheckVel(){
        var obj = $('#exampleTableEvents').bootstrapTable('getAllSelections');
        check_val = [];
        if (isEmpty(obj)) {
            return check_val;
        } else {
            $.each(obj, function(k, v){
                check_val.push(v.id);
            });
            return check_val;
        }
    }

    // 批量删除
    $("#cp-rm-ids").on('click',function(){
        var _ids = getCheckVel();
        if (isEmpty(_ids)) {
            layer.alert('请勾选左侧ID值',{icon:5},function(index){
                layer.close(index);
            });
            return false;
        }
        var load = layer.load(2);
        $.ajax({
            url: _del_table_url,
            data: {'ids' : _ids},
            method: "POST",
            async : true,
            dataType : "json",
            success: function (data) {
                layer.close(load);
                if (!data.code) {
                    layer.msg(data.msg,{icon:1},function(index){
                        layer.close(index);
                        refreshTable();
                    });
                } else {
                    layer.msg(data.msg,{icon:5},function(index){
                        layer.close(index);
                    });
                }
            }
        });
    });

    exports('bootstrapTable', {});
});