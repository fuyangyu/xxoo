# excel
PHP excel表格处理
## 链接
- 博客：http://www.mgchen.com
- github：https://github.com/cocolait
- gitee：http://gitee.com/cocolait

# 安装
```php
composer require cocolait/excel
```

# 版本要求
> PHP >= 5.3

# 使用说明
> 该扩展包适用于任何框架

# 使用案例
```php
<?php
// 加载包 如果你使用的框架已经支持自动加载composer了 这行那么就可以省略了
require "vendor/autoload.php"

// excel表格处理
// 导入Excel文件
$data = \cocolait\extend\Excel::importExcel('上传文件名称','上传文件目录');
// 导出Excel文件
$data = \cocolait\extend\Excel::exportExcel('文件名称','设置Excel表格第一行的显示','需要导出的所有数据');
// Excel转换Array
$data = \cocolait\extend\Excel::excelFileToArray('Excel文件全路径','文件后缀 默认是 xls');

```