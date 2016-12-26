<?php
namespace ffan\php\tpl;

/**
 * Class Compiler
 * @package ffan\php\tpl
 */
class Compiler
{
    /**
     * 值变量名
     */
    const DATA_VAR_NAME = '$tpl_data';

    /**
     * 结果变量名
     */
    const RESULT_VAR_NAME = '$tpl_html';

    /**
     * @var string 前标签
     */
    private $prefix_tag = '{{';

    /**
     * @var string 后标签
     */
    private $suffix_tag = '}}';
    
    /**
     * @var array 语法标签
     */
    private $syntaxStack = array();
    
    /**
     * @var int for循环变量开始ascii值
     */
    private $for_char_code = 65;

    /**
     * @var array 私有的临时变量
     */
    private $private_var = array();
    
    

    /**
     * 将模板编译成文件
     * @param string $tpl_file 模板文件
     * @param string $func_name 函数名
     */
    public function make($tpl_file, $func_name)
    {
        $tpl_content = file_get_contents($tpl_file);
        $tpl_content = $this->cleanTpl($tpl_content);
        $this->compile($tpl_content);
    }

    /**
     * 清理模板内容
     * @param string $tpl_content 原始模板内容
     * @return string
     */
    private function cleanTpl($tpl_content)
    {
        //去掉行首尾的\t
        $tpl_content = preg_replace('/\r?\n[\t]+/', ' ', $tpl_content);
        //去掉行首尾的空格
        $tpl_content = preg_replace('/\r?\n[\s]*/', '', $tpl_content);
        if (false !== strpos($tpl_content, '<!--')) {
            $tpl_content = $this->cleanComment($tpl_content);
        }
        return $tpl_content;
    }

    /**
     * 清理模板中的注释
     * @param string $tpl_content 模板内容
     * @return string
     * @throws TplException
     */
    private function cleanComment($tpl_content)
    {
        $beg_pos = strpos($tpl_content, '<!--');
        if (false === $beg_pos) {
            return $tpl_content;
        };
        $new_tpl = '';
        $end_pos = -3;
        while (false !== $beg_pos) {
            $new_tpl .= substr($tpl_content, $end_pos + 3, $beg_pos);
            $end_pos = strpos($tpl_content, '-->', $beg_pos);
            if (false === $end_pos) {
                throw new TplException('注释标签不配对', TplException::TPL_COMPILE_ERROR);
            }
            $beg_pos = strpos($tpl_content, '<!--', $end_pos + 3);
        }
        return $new_tpl . substr($tpl_content, $end_pos + 3);
    }

    /**
     * 编译模板
     * @param string $tpl_content 模板内容
     * @return string
     * @throws TplException
     */
    private function compile($tpl_content)
    {
        $result_str = self::RESULT_VAR_NAME . "=''";
        $prefix_len = strlen($this->prefix_tag);
        $suffix_len = strlen($this->suffix_tag);
        $tmp_end_pos = $suffix_len * -1;
        $beg_pos = strpos($tpl_content, $this->prefix_tag);
        while (false !== $beg_pos){
            $normal_str = substr($tpl_content, $tmp_end_pos + $suffix_len, $beg_pos);
            if (strlen($normal_str) > 0){
                $result_str .= self::RESULT_VAR_NAME ." .= '". $this->cleanStr($normal_str) ."';";
            }
            $tmp_end_pos = strpos($tpl_content, $this->suffix_tag, $beg_pos);
            if (false === $tmp_end_pos) {
                throw new TplException('模板标签未闭合', TplException::TPL_COMPILE_ERROR );
            }
            $result_str .= $this->tagSyntax(substr($tpl_content, $beg_pos + $prefix_len, $tmp_end_pos));
            $beg_pos = strpos($tpl_content, $this->prefix_tag, $tmp_end_pos);
        }
        if (!empty($this->syntaxStack)){
            throw new TplException('模板语法标签不配对', TplException::TPL_COMPILE_ERROR );
        }
        $normal_str = substr($tpl_content, $tmp_end_pos + $suffix_len);
        $result_str .= self::RESULT_VAR_NAME .".='". $this->cleanStr($normal_str) ."';return ". self::RESULT_VAR_NAME .';';
        return $result_str;
    }

    /**
     * 清理字符串,将字符串里的 '和\ 再次转义
     * @param string $str
     * @return string
     */
    private function cleanStr($str)
    {
        return preg_replace('#([\'\\\])#', '\\\$1', $str);
    }

    /**
     * 标签解析
     * @param string $tag_content 标签内容
     * @return string
     */
    public function tagSyntax($tag_content)
    {
        
    }
}
