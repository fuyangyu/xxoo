<?php
namespace bootstrap\modal;
/**
 * 远程请求模态框处理类
 * Created by PhpStorm.
 * User: Chen Peng
 * Date: 2018/5/14 0014
 * Time: 9:09
 */
class Html{
    protected static $instance;
    protected $content;
    private function __construct($options)
    {

    }

    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 封装table表格显示弹出层
     * @param array $table 表格参数 ['title' => '表格标题', 't_head' => 'thead区域内容', 't_body' => 'tbody循环内容']
     * @param string $tableClass 表格的class 默认不带网格 table-striped 带网格的
     * @param string $style 设置表格列的style
     * @return $this
     */
    public function modalTableHtml($table, $tableClass = 'table-striped', $style = '')
    {
        $this->content = '<div class="ibox-title">';
        $this->content .= "<h5>{$table['title']}</h5></div>";
        $this->content .= '<div class="ibox-content">';
        $this->content .= "<table class='table {$tableClass}'><thead>";
        if (isset($table['t_head']) && is_array($table['t_head'])) {
            if ($table['t_head']) {
                $this->content .= '<tr>';
                foreach ($table['t_head'] as $th_content) {
                    $this->content .= "<th style='{$style}'>{$th_content}</th>";
                }
                $this->content .= '</tr>';
            }
        }
        $this->content .= '</thead>';
        $this->content .= '<tbody>';
        if (isset($table['t_body']) && is_array($table['t_body'])) {
            if ($table['t_body']) {
                foreach ($table['t_body'] as $tr) {
                    $this->content .= "<tr>";
                    if ($tr) {
                        foreach ($tr as $td_content) {
                            $this->content .= "<td>{$td_content}</td>";
                        }
                    }
                    $this->content .= "</tr>";
                }
            }
        }
        $this->content .= '</tbody></table></div>';
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    private function __clone() {}
}