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
    const DATA_VAR_NAME = 'tpl_data';

    /**
     * PHP代码
     */
    const TYPE_PHP_CODE = 1;

    /**
     * 普通代码
     */
    const TYPE_NORMAL_STRING = 2;

    /**
     * @var int 最近一次写入的内容类型
     */
    private $current_code_type = self::TYPE_PHP_CODE;

    /**
     * @var string 结果
     */
    private $result = '<?php' . PHP_EOL;

    /**
     * @var string 前标签
     */
    private $prefix_tag;

    /**
     * @var string 后标签
     */
    private $suffix_tag;

    /**
     * @var int 前标签长度
     */
    private $prefix_len;

    /**
     * @var int 后标签长度
     */
    private $suffix_len;

    /**
     * @var array 语法标签
     */
    private $tag_stacks = array();

    /**
     * @var int for循环变量开始ascii值
     */
    private $for_char_code = 65;

    /**
     * @var array 私有的临时变量
     */
    private $private_var = array();

    /**
     * Compiler constructor.
     * @param string $prefix_tag 前标签
     * @param string $suffix_tag 后标签
     */
    public function __construct($prefix_tag = '{{', $suffix_tag = '}}')
    {
        $this->prefix_tag = $prefix_tag;
        $this->suffix_tag = $suffix_tag;
        $this->prefix_len = strlen($prefix_tag);
        $this->suffix_len = strlen($suffix_tag);
    }

    /**
     * 编译模板
     * @param string $tpl_file 模板文件
     * @param string $func_name 函数名
     * @return string
     * @throws TplException
     */
    public function make($tpl_file, $func_name)
    {
        $begin_str = "/**\n * @param \\ffan\\php\\tpl\\Tpl \$ffan_tpl \n * @return string \n */\n";
        $begin_str .= 'function ' . $func_name . '($ffan_tpl)' . PHP_EOL . "{\nob_start();\n";
        $begin_str .= '$' . self::DATA_VAR_NAME . ' = $ffan_tpl->getData();';
        $this->pushResult($begin_str, self::TYPE_PHP_CODE);
        $file_handle = fopen($tpl_file, 'r');
        while ($line = fgets($file_handle)) {
            if (false === strpos($line, $this->prefix_tag)) {
                $this->pushResult($line, self::TYPE_NORMAL_STRING);
            } else {
                $this->compile($line);
            }
        }
        $end_str = '$str = ob_get_contents();' . PHP_EOL . 'ob_end_clean();' . PHP_EOL . 'return $str;' . PHP_EOL . '}';
        $this->pushResult($end_str, self::TYPE_PHP_CODE);
        if (!empty($this->tag_stacks)) {
            throw new TplException('标签 ' . join(', ', $this->tag_stacks) . ' 不配对');
        }
        return $this->result;
    }

    /**
     * 编译模板
     * @param string $line_content 一行内容
     * @throws TplException
     */
    private function compile($line_content)
    {
        $tmp_end_pos = $this->suffix_len * -1;
        $beg_pos = strpos($line_content, $this->prefix_tag);
        while (false !== $beg_pos) {
            $normal_str = substr($line_content, $tmp_end_pos + $this->suffix_len, $beg_pos - $tmp_end_pos - $this->suffix_len);
            if (strlen($normal_str) > 0) {
                $this->pushResult($normal_str, self::TYPE_NORMAL_STRING);
            }
            $tmp_end_pos = strpos($line_content, $this->suffix_tag, $beg_pos);
            if (false === $tmp_end_pos) {
                throw new TplException($line_content . '标签未闭合', TplException::TPL_COMPILE_ERROR);
            }
            $tag_content = substr($line_content, $beg_pos + $this->prefix_len, $tmp_end_pos - $beg_pos - $this->prefix_len);
            $this->pushResult($this->tagSyntax($tag_content), self::TYPE_PHP_CODE);
            $beg_pos = strpos($line_content, $this->prefix_tag, $tmp_end_pos);
        }
        if ($tmp_end_pos + $this->suffix_len < strlen($line_content)) {
            $normal_str = substr($line_content, $tmp_end_pos + $this->suffix_len);
            $this->pushResult($normal_str, self::TYPE_NORMAL_STRING);
        }
    }

    /**
     * 写入结果
     * @param string $str 代码
     * @param int $type 类型
     */
    private function pushResult($str, $type)
    {
        if ($this->current_code_type !== $type) {
            if (self::TYPE_NORMAL_STRING === $type) {
                $this->result .= '?>';
            } else {
                $this->result .= '<?php ';
            }
            $this->current_code_type = $type;
        }
        $this->result .= $str;
    }

    /**
     * 标签解析
     * @param string $tag_content 标签内容
     * @return string
     * @throws TplException
     */
    public function tagSyntax($tag_content)
    {
        $tag = new TagParser($tag_content);
        $type = $tag->getTagType();
        $result = '';
        switch ($type) {
            //关闭标签
            case TagParser::TAG_CLOSE:
                if ($this->popTagStack() !== $tag->getResult()) {
                    $tag->error('前面没有 ' . $tag->getResult() . ' 标签');
                }
                $result = '}';
                break;
            //条件判断
            case TagParser::TAG_IF:
                $result = $tag->getResult() . ' {';
                $this->pushTagStack('if');
                break;
            //else
            case TagParser::TAG_ELSE:
                if (!$this->hasTagStack('if')) {
                    $tag->error('前面没有if标签');
                }
                $result = '} ' . $tag->getResult() . ' {';
                break;
            //普通表达式
            case TagParser::TAG_STATEMENT:
                $result = 'echo ' . $tag->getResult() . ';';
                break;
            //函数
            case TagParser::TAG_FUNCTION:
                $result = $this->tagFunction($tag);
                break;
        }
        return $result;
    }

    /**
     * 函数
     * @param TagParser $tag
     * @return string
     */
    private function tagFunction($tag)
    {
        $name = $tag->getResult();
        $func_name = 'tagFunction' . ucfirst($name);
        switch ($func_name) {
            case 'foreach':
                $re_str = $this->tagFunctionForeach($tag);
                break;
            case 'section':
                $re_str = $this->tagFunctionSection($tag);
                break;
            default:
                $re_str = $this->tagFilter($tag);
                break;
        }
        return $re_str;
    }

    /**
     * 解析插件
     * @param TagParser $tag
     * @return string
     */
    private function tagFilter($tag)
    {
        $name = $tag->getResult();
        //未找到，就当成插件来处理
        $re_str = "\$ffan_tpl->plugin('" . $name . "'";
        $attribute = $tag->getAttributes();
        if (!empty($attribute)) {
            $re_str .= ', ';
            $args = [];
            foreach ($attribute as $item => $str) {
                $args[] = "'" . $name . "' => " . $str;
            }
            $re_str .= '[' . join(', ', $args) . ']';
        }
        $re_str .= ');';
        return $re_str;
    }

    /**
     * 循环语法解析
     * @param TagParser $tag
     * @return string
     * @throws TplException
     */
    private function tagFunctionForeach($tag)
    {
        $params = $tag->getAttributes();
        if (!isset($params['from'])) {
            $tag->error('缺少 from 属性');
        }
        if (!isset($params['item'])) {
            $tag->error('缺少 item 属性');
        }
    }

    /**
     * section语法解析
     * @param TagParser $tag
     * @return string
     */
    private function tagFunctionSection($tag)
    {
        $params = $tag->getAttributes();
    }

    /**
     * 弹出一个标签
     * @return null|string
     */
    private function popTagStack()
    {
        return array_pop($this->tag_stacks);
    }

    /**
     * 压入一个标签
     * @param string $tag
     */
    private function pushTagStack($tag)
    {
        $this->tag_stacks[] = $tag;
    }

    /**
     * 是否存在指定的tag在stack里
     * @param string $tag
     * @return bool
     */
    private function hasTagStack($tag)
    {
        return in_array($tag, $this->tag_stacks);
    }
}
