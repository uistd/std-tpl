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
    const DATA_PARAM_NAME = 'TPL_DATA';

    /**
     * 模板类变量名
     */
    const TPL_PARAM_NAME = 'TPL_RENDER';

    /**
     * 选项变量
     */
    const OPTION_IS_ECHO = 'IS_ECHO';

    /**
     * PHP代码
     */
    const TYPE_PHP_CODE = 1;

    /**
     * 普通代码
     */
    const TYPE_NORMAL_STRING = 2;

    /**
     * 注释标签 开始
     */
    const COMMENT_TAG_PREFIX = '{*';

    /**
     * 注释标签 结束
     */
    const COMMENT_TAG_SUFFIX = '*}';

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
     * @var array 语法标签栈
     */
    private $tag_stacks = array();

    /**
     * @var array 临时变量栈
     */
    private $local_var_stacks = array();

    /**
     * @var array 临时变量
     */
    private $private_vars;

    /**
     * @var bool 是否将所有字符当成普通字符，停止解析模板标签
     */
    private $literal = false;

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
        $begin_str = "/**\n * @param \\ffan\\php\\tpl\\Render \$" . self::TPL_PARAM_NAME . "\n * @param int \$" . self::OPTION_IS_ECHO . " \n * @return string|null \n */\n";
        $begin_str .= 'function ' . $func_name . '($' . self::TPL_PARAM_NAME . ', $' . self::OPTION_IS_ECHO . ')' . "\n{\n";
        $begin_str .= 'if (!$' . self::OPTION_IS_ECHO . ") \n{\nob_start();\n}\n";
        $begin_str .= '$' . self::DATA_PARAM_NAME . ' = &$' . self::TPL_PARAM_NAME . '->getData();';
        $this->pushResult($begin_str, self::TYPE_PHP_CODE);
        $file_handle = fopen($tpl_file, 'r');
        while ($line = fgets($file_handle)) {
            $is_comment_line = false;
            $line = self::cleanLine($line, $is_comment_line);
            //如果是一行注释，无视之
            if ($is_comment_line) {
                continue;
            }
            $line = trim($line, "\n\r\0\x0B");
            //空行
            if ('' === trim($line, ' ')) {
                $this->pushResult('echo PHP_EOL;', self::TYPE_PHP_CODE);
                continue;
            }
            $pre_fix_pos = strpos($line, $this->prefix_tag);
            if ( false === $pre_fix_pos || ($this->literal && false === strpos($line, '/literal'))) {
                $has_normal = true;
                $this->pushResult($line, self::TYPE_NORMAL_STRING);
            } else {
                $has_normal = $this->compile($line);
            }
            if ($has_normal) {
                $this->pushResult('echo PHP_EOL;', self::TYPE_PHP_CODE);
            }
        }
        $end_str = 'if (!$' . self::OPTION_IS_ECHO . '){$str = ob_get_contents();' . PHP_EOL . 'ob_end_clean();' . PHP_EOL . 'return $str;' . PHP_EOL . "}\n return null;}";
        $this->pushResult($end_str, self::TYPE_PHP_CODE);
        if (!empty($this->tag_stacks)) {
            throw new TplException('标签 ' . join(', ', $this->tag_stacks) . ' 不配对');
        }
        return $this->result;
    }

    /**
     * 清理一行内容，主要清理注释，检查一行是不是全是注释内容
     * @param string $line_content
     * @param bool $is_comment_line
     * @return string
     */
    private static function cleanLine($line_content, &$is_comment_line = false)
    {
        $comment_len = strlen(self::COMMENT_TAG_PREFIX);
        $tmp_end_pos = $comment_len * -1;
        $beg_pos = strpos($line_content, self::COMMENT_TAG_PREFIX);
        if (false === $beg_pos) {
            return $line_content;
        }
        $new_content = '';
        $last_pos = 0;
        while (false !== $beg_pos) {
            $normal_str = substr($line_content, $tmp_end_pos + $comment_len, $beg_pos - $tmp_end_pos - $comment_len);
            $new_content .= $normal_str;
            $tmp_end_pos = strpos($line_content, self::COMMENT_TAG_SUFFIX, $beg_pos);
            if (false === $tmp_end_pos) {
                $last_pos = $beg_pos;
                break;
            }
            $last_pos = $tmp_end_pos + $comment_len;
            $beg_pos = strpos($line_content, self::COMMENT_TAG_PREFIX, $tmp_end_pos);
        }
        if ($last_pos + $comment_len < strlen($line_content)) {
            $normal_str = substr($line_content, $last_pos);
            $new_content .= $normal_str;
        }
        $is_comment_line = 0 === strlen(trim($new_content));
        return $new_content;
    }

    /**
     * 编译模板
     * @param string $line_content 一行内容
     * @return bool
     * @throws TplException
     */
    private function compile($line_content)
    {
        $has_normal = false;
        $tmp_end_pos = $this->suffix_len * -1;
        $beg_pos = strpos($line_content, $this->prefix_tag);
        $index = 0;
        while (false !== $beg_pos) {
            $normal_str = substr($line_content, $tmp_end_pos + $this->suffix_len, $beg_pos - $tmp_end_pos - $this->suffix_len);
            if (!empty($normal_str)) {
                if ($index > 0 || '' !== ltrim($normal_str)) {
                    $has_normal = true;
                    $this->pushResult($normal_str, self::TYPE_NORMAL_STRING);
                }
            }
            $tmp_end_pos = strpos($line_content, $this->suffix_tag, $beg_pos);
            if (false === $tmp_end_pos) {
                throw new TplException($line_content . '标签未闭合', TplException::TPL_COMPILE_ERROR);
            }
            $tag_content = substr($line_content, $beg_pos + $this->prefix_len, $tmp_end_pos - $beg_pos - $this->prefix_len);
            $this->pushResult($this->tagSyntax($tag_content), self::TYPE_PHP_CODE);
            $index++;
            $beg_pos = strpos($line_content, $this->prefix_tag, $tmp_end_pos);
        }
        if ($tmp_end_pos + $this->suffix_len < strlen($line_content)) {
            $normal_str = substr($line_content, $tmp_end_pos + $this->suffix_len);
            //标签尾的空白忽略
            if ('' !== ltrim($normal_str)) {
                $this->pushResult($normal_str, self::TYPE_NORMAL_STRING);
                $has_normal = true;
            }
        }
        return $has_normal;
    }

    /**
     * 写入结果
     * @param string $str 代码
     * @param int $type 类型
     */
    private function pushResult($str, $type)
    {
        if (self::TYPE_PHP_CODE === $type) {
            $str .= PHP_EOL;
        }
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
        $tag = new TagParser($tag_content, $this);
        $type = $tag->getTagType();
        switch ($type) {
            //关闭标签
            case TagParser::TAG_CLOSE:
                $result = $this->tagClose($tag);
                break;
            //条件判断
            case TagParser::TAG_IF:
                $result = PHP_EOL . $tag->getResult() . ' {';
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
            case TagParser::TAG_ECHO:
                $result = 'echo ' . $tag->getResult() . ';';
                break;
            //函数
            case TagParser::TAG_FUNCTION:
                $result = $this->tagFunction($tag);
                break;
            //表达式
            case TagParser::TAG_STATEMENT:
                $result = $tag->getResult() . ';';
                break;
            //for循环
            case TagParser::TAG_FOR:
                $result = $this->tagFor($tag);
                break;
            default:
                throw new TplException('不支持的类型：' . $type, TplException::TPL_COMPILE_ERROR);
                break;
        }
        $var_list = $tag->getVarList();
        foreach ($var_list as $var_name => $v) {
            $result = $this->replaceVarName($result, $var_name);
        }
        return $result;
    }

    /**
     * 变量名替换
     * @param string $re_str 原始字符串
     * @param string $var_name 变更名
     * @return string
     */
    private function replaceVarName($re_str, $var_name)
    {
        if (isset($this->private_vars[$var_name])) {
            $to_str = '$' . $var_name;
        } else {
            $to_str = '$' . self::DATA_PARAM_NAME . "['" . $var_name . "']";
        }
        return str_replace('{_' . $var_name . '_}', $to_str, $re_str);
    }

    /**
     * 关闭标签
     * @param TagParser $tag 标签解析类
     * @return string
     */
    private function tagClose($tag)
    {
        $name = $tag->getResult();
        //literal标签特殊处理
        if ('literal' === $name) {
            $this->literal = false;
            $re_str = '';
        } else {
            $re_str = PHP_EOL . '}';
        }

        $pop_tag = $this->popTagStack();
        if ($pop_tag !== $name) {
            //特殊处理，因为foreach外面还包了层if，所以如果得到foreach else 就表示 foreach标签没有{{/foreach}}
            if ('foreach' === $name && 'foreach else' === $pop_tag) {
                $re_str .= $this->tagClose($tag);
            } else {
                $tag->error($pop_tag . ' 标签不匹配');
            }
        }
        return $re_str;
    }

    /**
     * for标签
     * @param TagParser $tag 标签解析类
     * @return string
     */
    private function tagFor($tag)
    {
        $vars = $tag->getAttributes();
        $this->pushTagStack('for', $vars);
        return PHP_EOL . 'for (' . $tag->getResult() . ') {' . PHP_EOL;
    }

    /**
     * 函数
     * @param TagParser $tag
     * @return string
     */
    private function tagFunction($tag)
    {
        $name = $tag->getResult();
        switch ($name) {
            case 'foreach':
                $re_str = $this->tagFunctionForeach($tag);
                break;
            case 'foreachelse':
                $re_str = $this->tagFunctionForeachElse($tag);
                break;
            //停止解析标签
            case 'literal':
                $re_str = '';
                $this->literal = true;
                $this->pushTagStack('literal');
                break;
            //包含其它文件
            case 'include':
                $re_str = $this->tagInclude($tag);
                break;
            default:
                $re_str = $this->tagPlugin($tag);
                break;
        }
        return $re_str;
    }

    /**
     * 解析插件
     * @param TagParser $tag
     * @return string
     */
    private function tagPlugin($tag)
    {
        $name = $tag->getResult();
        //未找到，就当成插件来处理
        $re_str = 'echo $' . self::TPL_PARAM_NAME . "->getTpl()->loadPlugin('" . $name . "', [";
        $attribute = $tag->getAttributes();
        if (!empty($attribute)) {
            $args = [];
            foreach ($attribute as $item => $str) {
                $args[] = "'" . $item . "' => " . $str;
            }
            $re_str .= join(', ', $args);
        }
        $re_str .= ']);';
        return $re_str;
    }

    /**
     * 包含
     * @param TagParser $tag
     * @return string
     */
    private function tagInclude($tag)
    {
        $attribute = $tag->getAttributes();
        if (!isset($attribute['file'])) {
            $tag->error('缺少 file 属性');
        }
        $file = trim($attribute['file']);
        $re_str = 'if (!$' . self::OPTION_IS_ECHO . ' ) {' . PHP_EOL
            . '$this' . self::TPL_PARAM_NAME . '->load(' . $file . ');' . PHP_EOL . ' } else {' . PHP_EOL
            . 'echo $' . self::TPL_PARAM_NAME . '->load(' . $file . ', null, true);' . PHP_EOL . '}' . PHP_EOL;
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
        $local_var = [];
        $re_str = PHP_EOL . 'if (isset(' . $params['from'] . ') && is_array(' . $params['from'] . ') && !empty(' . $params['from'] . ')) {';
        $re_str .= PHP_EOL . 'foreach (' . $params['from'] . ' as ';
        if (isset($params['key'])) {
            $key = trim($params['key'], '"\'');
            $local_var[] = $key;
            $re_str .= '$' . $key . ' => ';
        }
        $items = $params['item'];
        //如果是数组，表示是 list 的写法
        if (is_array($items)) {
            $re_str .= 'list($' . join(', $', $items) . ')';
            $local_var = array_merge($local_var, $items);
        } else {
            $items = trim($items, '"\'');
            $re_str .= '$' . $items;
            $local_var[] = $items;
        }
        $re_str .= ') {';
        //往stack压入foreach
        $this->pushTagStack('foreach');
        //往stack压入foreach else 为了后面生成关闭字符“}”
        $this->pushTagStack('foreach else', $local_var);
        return $re_str;
    }

    /**
     * 循环语法解析
     * @param TagParser $tag
     * @return string
     * @throws TplException
     */
    private function tagFunctionForeachElse($tag)
    {
        if ('foreach else' !== $this->popTagStack()) {
            $tag->error('前面没有 foreach');
        }
        return '}} else {';
    }

    /**
     * 压入一个标签
     * @param string $tag_name 标签名称
     * @param null|array $private_vars 局部变量
     */
    private function pushTagStack($tag_name, $private_vars = null)
    {
        if (is_array($private_vars)) {
            foreach ($private_vars as $name) {
                $this->setLocalVar($name);
            }
        }
        $this->tag_stacks[] = $tag_name;
        $this->local_var_stacks[] = $private_vars;
    }

    /**
     * 弹出一个标签
     * @return null|string
     * @throws TplException
     */
    private function popTagStack()
    {
        $local_vars = array_pop($this->local_var_stacks);
        //如果局部变量里有变量，要unset掉
        if (null !== $local_vars) {
            foreach ($local_vars as $name) {
                $this->unsetLocalVar($name);
            }
        }
        if (empty($this->tag_stacks)) {
            throw new TplException('标签配对出错');
        }
        $re = array_pop($this->tag_stacks);
        return $re;
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

    /**
     * 添加一个局部变量
     * @param string $name 变量名
     * @throws TplException
     */
    public function setLocalVar($name)
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z\d_]*$/', $name)) {
            throw new TplException('错误的变量名:' . $name);
        }
        //如果变量冲突
        if ($name === self::TPL_PARAM_NAME || $name === self::OPTION_IS_ECHO || $name === self::DATA_PARAM_NAME) {
            throw new TplException('变量名：' . $name . ' 是系统保留变量');
        }
        if (isset($this->private_vars[$name])) {
            throw new TplException('变量名：' . $name . ' 和外层变量名冲突');
        }
        $this->private_vars[$name] = true;
    }

    /**
     * 移除一个局部变量
     * @param string $name 变量名
     */
    public function unsetLocalVar($name)
    {
        unset($this->private_vars[$name]);
    }
}
