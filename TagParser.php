<?php
namespace ffan\php\tpl;

/**
 * Class TagParser 标签解析
 * @package ffan\php\tpl
 */
class TagParser
{
    /**
     * 变量
     */
    const STACK_VAR = 1;

    /**
     * 属性
     */
    const STACK_VALUE = 2;

    /**
     * 修正器
     */
    const STACK_FILTER = 3;

    /**
     * 引号
     */
    const STACK_QUOTE = 4;

    /**
     * 空格
     */
    const CHAR_SPACE = 32;

    /**
     * 相等
     */
    const CHAR_EQUAL = 61;

    /**
     * $符号
     */
    const CHAR_VAR = 36;

    /**
     * 双引号
     */
    const CHAR_QUOTE = 34;

    /**
     * 单引号
     */
    const CHAR_SINGLE_QUOTE = 39;

    /**
     * 转义符
     */
    const CHAR_ESCAPE = 92;

    /**
     * /号
     */
    const CHAR_CLOSE_TAG = 47;

    /**
     * 修饰符
     */
    const CHAR_FILTER = 124;

    /**
     * 左中括号
     */
    const CHAR_LEFT_SQUARE_BRACKET = 91;

    /**
     * 右中括号
     */
    const CHAR_RIGHT_SQUARE_BRACKET = 93;

    /**
     * 左括号
     */
    const CHAR_LEFT_BRACKET = 40;

    /**
     * 右括号
     */
    const CHAR_RIGHT_BRACKET = 41;

    /**
     * 点
     */
    const CHAR_DOT = 46;

    /**
     * 冒号
     */
    const CHAR_COLON = 58;

    /**
     * 数字
     */
    const SECTION_NUMBER = 1;

    /**
     * 字符串
     */
    const SECTION_STRING = 2;

    /**
     * 逻辑运算
     */
    const SECTION_LOGIC = 3;

    /**
     * 数学和位运算
     */
    const SECTION_MATH = 4;

    /**
     * 普通字符
     */
    const SECTION_NORMAL = 5;

    /**
     * 浮点数
     */
    const SECTION_FLOAT = 6;

    /**
     * 类属性
     */
    const SECTION_PROPERTY = 7;

    /**
     * 管道
     */
    const SECTION_GREP = 8;

    /**
     * 变量
     */
    const SECTION_VAR = 9;

    /**
     * 属性
     */
    const SECTION_ATTRIBUTE = 10;

    /**
     * 冒号
     */
    const SECTION_COLON = 11;

    /**
     * .号
     */
    const SECTION_DOT = 12;

    /**
     * 修饰符
     */
    const SECTION_EMBELLISH = 13;

    /**
     * 左中括号
     */
    const SECTION_LEFT_SQUARE_BRACKET = 13;

    /**
     * 右中括号
     */
    const SECTION_RIGHT_SQUARE_BRACKET = 14;

    /**
     * 左括号
     */
    const SECTION_LEFT_BRACKET = 15;

    /**
     * 右括号
     */
    const SECTION_RIGHT_BRACKET = 16;

    /**
     * 自增/自减
     */
    const SECTION_INCREASE = 17;

    /**
     * 静态方法
     */
    const SECTION_STATIC_METHOD = 18;

    /**
     * 逗号
     */
    const SECTION_COMMA = 19;

    /**
     * 条件判断标签
     */
    const TAG_CONDITION = 1;

    /**
     * 功能标签
     */
    const TAG_FUNCTION = 2;

    /**
     * 一个语句：变量 或者 计算
     */
    const TAG_STATEMENT = 3;

    /**
     * 关闭
     */
    const TAG_CLOSE = 4;

    /**
     * 元素类型：值
     */
    const ITEM_TYPE_VALUE = 1;

    /**
     * 元素类型：操作符
     */
    const ITEM_TYPE_OPERATOR = 2;

    /**
     * 前面不能有空格
     */
    const SPACE_LIMIT_BEFORE = 1;

    /**
     * 后面不能有空格
     */
    const SPACE_LIMIT_AFTER = 2;

    /**
     * @var array 特殊字符 算术运算 位运算 和逻辑运算
     */
    private static $special_char_arr = array(
        ' ' => array(
            ' and ' => self::SECTION_LOGIC,
            ' or ' => self::SECTION_LOGIC,
            ' xor ' => self::SECTION_LOGIC,
        ),
        '$' => self::SECTION_VAR,
        '|' => self::SECTION_GREP,
        '=' => self::SECTION_ATTRIBUTE,
        '.' => self::SECTION_DOT,
        ',' => self::SECTION_COMMA,
        '[' => self::SECTION_LEFT_SQUARE_BRACKET,
        ']' => self::SECTION_RIGHT_SQUARE_BRACKET,
        '(' => array(
            '(int)' => self::SECTION_EMBELLISH,
            '(float)' => self::SECTION_EMBELLISH,
            '(string)' => self::SECTION_EMBELLISH,
            '(array)' => self::SECTION_EMBELLISH,
            '(object)' => self::SECTION_EMBELLISH,
            '(bool)' => self::SECTION_EMBELLISH,
            '(' => self::SECTION_LEFT_BRACKET
        ),
        ')' => self::SECTION_RIGHT_BRACKET,
        ':' => self::SECTION_COLON,
        '+' => self::SECTION_MATH,
        '-' => self::SECTION_MATH,
        '*' => self::SECTION_MATH,
        '/' => self::SECTION_MATH,
        '%' => self::SECTION_MATH,
        '&' => self::SECTION_MATH,
        //'|' => self::SECTION_MATH, 因为和管道符混淆，不支持
        '^' => self::SECTION_MATH,
        '<<' => self::SECTION_MATH,
        '>>' => self::SECTION_MATH,
        '+=' => self::SECTION_MATH,
        '-=' => self::SECTION_MATH,
        '*=' => self::SECTION_MATH,
        '/=' => self::SECTION_MATH,
        '%=' => self::SECTION_MATH,
        '!' => self::SECTION_LOGIC,
        '&&' => self::SECTION_LOGIC,
        '||' => self::SECTION_LOGIC,
        '!=' => self::SECTION_LOGIC,
        '!==' => self::SECTION_LOGIC,
        '===' => self::SECTION_LOGIC,
        '==' => self::SECTION_LOGIC,
        '<' => self::SECTION_LOGIC,
        '>' => self::SECTION_LOGIC,
        '>=' => self::SECTION_LOGIC,
        '<=' => self::SECTION_LOGIC,
        '->' => self::SECTION_PROPERTY,
        '@' => self::SECTION_EMBELLISH,
        '++' => self::SECTION_INCREASE,
        '--' => self::SECTION_INCREASE,
        '::' => self::SECTION_STATIC_METHOD
    );

    /**
     * @var array 空格限制设置 前面 或者 后面不允许有空格
     */
    private static $space_limit_set = array(
        '$' => self::SPACE_LIMIT_AFTER,
        '.' => self::SPACE_LIMIT_BEFORE | self::SPACE_LIMIT_AFTER,
        '[' => self::SPACE_LIMIT_BEFORE,
        '->' => self::SPACE_LIMIT_BEFORE | self::SPACE_LIMIT_AFTER,
        '::' => self::SPACE_LIMIT_BEFORE | self::SPACE_LIMIT_AFTER
    );

    /**
     * @var string 标准内容
     */
    private $tag_content;

    /**
     * @var int 处理的游标值
     */
    private $index = 0;

    /**
     * @var int 长度
     */
    private $tag_len;

    /**
     * @var bool 是否已经完成了
     */
    private $is_eof = false;

    /**
     * @var array 切割结果
     */
    private $split_sections = [];

    /**
     * @var array 类型结果
     */
    private $split_types = [];

    /**
     * @var string 解析结果
     */
    private $result;

    /**
     * @var null|array 参数
     */
    private $attributes;

    /**
     * @var string 上一个切割字符串
     */
    private $last_split_str;

    /**
     * @var int 上一个类型
     */
    private $last_split_type;

    /**
     * @var int 标签类型
     */
    private $tag_type = 0;

    /**
     * TagParser constructor.
     * @param string $tag_str
     */
    public function __construct($tag_str)
    {
        $this->tag_content = trim($tag_str);
        $this->tag_len = strlen($this->tag_content);
        $this->parse();
    }

    /**
     * 解析
     */
    private function parse()
    {
        $this->translate();
        $this->syntaxParse();
    }

    /**
     * 翻译成更容易识别的符号
     */
    private function translate()
    {
        //中括号计数
        $square_bracket = 0;
        //括号计数
        $bracket = 0;
        while (!$this->is_eof) {
            $char = $this->indexChar();
            $ord = ord($char);
            //中括号
            if (self::CHAR_LEFT_SQUARE_BRACKET === $ord) {
                ++$square_bracket;
            } elseif (self::CHAR_RIGHT_SQUARE_BRACKET === $ord) {
                --$square_bracket;
            }
            //括号
            if (self::CHAR_RIGHT_BRACKET === $ord) {
                --$bracket;
            } elseif (self::CHAR_LEFT_BRACKET === $ord) {
                ++$bracket;
            }
            //关键字符
            if (isset(self::$special_char_arr[$char])) {
                $type = null;
                $tmp_char = $this->splitSpecialChar($type);
                if (false !== $tmp_char) {
                    $this->pushSection($tmp_char, $type);
                    continue;
                }
            }
            //普通字符
            if ($this->isNormalChar($ord)) {
                $tmp_str = $this->splitNormal();
                $type = self::SECTION_NORMAL;
                $this->pushSection($tmp_str, $type);
            } //数字
            elseif ($this->isNumber($ord)) {
                $tmp_str = $this->splitNumber();
                //如果上一个字符是 '-' 表示负数
                if ($this->last_split_str === ord('-')) {
                    $last_str = $this->popSection();
                    $tmp_str = $last_str . $tmp_str;
                }
                $type = self::SECTION_NUMBER;
                //如果有小数点
                if (false !== strpos($tmp_str, '.')) {
                    $type = self::SECTION_FLOAT;
                }
                $this->pushSection($tmp_str, $type);
            } //引号表示字符串
            elseif ($ord === self::CHAR_SINGLE_QUOTE || $ord === self::CHAR_QUOTE) {
                $this->pushSection($this->splitString($ord), self::SECTION_STRING);
            }//空格
            else if ($ord === self::CHAR_SPACE) {
                $this->shiftChar();
            } else {
                $this->error('无法解析的字符' . $char);
            }
        }
        if (0 !== $square_bracket || 0 !== $bracket) {
            $this->error('括号或者中括号不配对');
        }
        //重置index和is_eof
        $this->index = 0;
        $this->is_eof = false;
        $this->tag_len = count($this->split_sections);
    }

    /**
     * 普通字符解析
     * @return string
     * @throws TplException
     */
    private function splitNormal()
    {
        $re_str = $this->shiftChar();
        while (!$this->is_eof) {
            $char = $this->indexChar();
            $ord = ord($char);
            if (isset(self::$special_char_arr[$char]) || self::CHAR_SPACE === $ord) {
                break;
            }
            if (!$this->isNormalChar($ord) && !$this->isNumber($ord)) {
                $this->error('不支持：' . $re_str . $this->shiftChar());
            }
            $re_str .= $this->shiftChar();
        }
        return $re_str;
    }

    /**
     * 解析算术和逻辑运算符
     * @param null $type 类型
     * @return string|bool
     * @throws TplException
     */
    private function splitSpecialChar(&$type)
    {
        $re_str = $this->indexChar();
        $tmp_type = self::$special_char_arr[$re_str];
        //如果是数字，依次对比
        if (is_array($tmp_type)) {
            foreach ($tmp_type as $item => $t) {
                $len = strlen($item);
                $tmp_char = $this->subString($len, false);
                if ($item === $tmp_char) {
                    $type = $t;
                    $this->subString($len);
                    return $tmp_char;
                }
            }
            return false;
        } else {
            $this->shiftChar();
            while (!$this->is_eof) {
                $char = $this->indexChar();
                if (!isset(self::$special_char_arr[$re_str . $char])) {
                    break;
                }
                $re_str .= $this->shiftChar();
            }
        }
        $type = self::$special_char_arr[$re_str];
        //如果连续两次相同的类型，报错
        if ($this->last_split_type === $type) {
            $this->error('字符串：' . $this->last_split_str . $re_str . ' 出错');
        }
        //空格限制
        if (isset(self::$space_limit_set[$re_str])) {
            $opt = self::$space_limit_set[$re_str];
            //前面不允许有空格
            if ((self::SPACE_LIMIT_BEFORE & $opt) && self::CHAR_SPACE === $this->last_split_str) {
                $this->error('关键字符：' . $re_str . ' 前面不允许有空格');
            }
            //后面不允许有空格
            if ((self::SPACE_LIMIT_AFTER & $opt) === $opt && !$this->is_eof && self::CHAR_SPACE === ord($this->indexChar())) {
                $this->error('关键字符：' . $re_str . ' 后面不允许有空格');
            }
        }
        return $re_str;
    }

    /**
     * 解析数字
     * @return string
     * @throws TplException
     */
    private function splitNumber()
    {
        $re_str = '';
        $allow_dot = true;
        $tmp_index = 0;
        $allow_hex = false;
        $is_hex = false;
        while (!$this->is_eof) {
            $tmp_index++;
            $char = $this->indexChar();
            $ord = ord($char);
            //第一位是 0,
            if (1 === $tmp_index && 48 === $ord) {
                $allow_hex = true;
                //第二位是 x或者X
            } elseif ($allow_hex && 2 === $tmp_index && (120 === $ord || 88 === $ord)) {
                $is_hex = true;
                $re_str .= $this->shiftChar();
                continue;
            }
            //遇到.号
            if (self::CHAR_DOT === $ord) {
                if ($is_hex || !$allow_dot) {
                    $this->error('小数点出错');
                }
                $allow_dot = false;
                $allow_hex = false;
                $re_str .= $this->shiftChar();
                continue;
            }
            //遇到空格或者关键字符中止
            if (isset(self::$special_char_arr[$ord])
                || self::CHAR_SPACE === $ord
                || isset(self::$special_char_arr[$char])
            ) {
                break;
            }
            if (!$this->isNumber($ord, $is_hex)) {
                $this->error('数字 ' . $re_str . $this->shiftChar() . ' 解析出错');
            }
            $re_str .= $this->shiftChar();
        }
        return $re_str;
    }

    /**
     * 普通字符解析
     * @param int $quote_type 引号类型
     * @return string
     * @throws TplException
     */
    private function splitString($quote_type)
    {
        $in_escape = false;
        $is_eof = false;
        $re_str = $this->shiftChar();
        while (!$this->is_eof) {
            $char = $this->shiftChar();
            $ord = ord($char);
            //正在转义中
            if ($in_escape) {
                $re_str .= '\\' . $char;
                $in_escape = false;
            } //结束了
            elseif ($ord === $quote_type) {
                $re_str .= $char;
                $is_eof = true;
                break;
            } //转义符
            elseif (self::CHAR_ESCAPE === $ord) {
                $in_escape = true;
            } else {
                $re_str .= $char;
            }
        }
        //解析字符串未完成， 就结束了
        if (!$is_eof) {
            $this->error('字符串 ' . $re_str . ' 出错');
        }
        return $re_str;
    }

    /**
     * 连接起来
     * @throws TplException
     */
    private function syntaxParse()
    {
        $first_type = $this->indexSectionType();
        //以普通的字符串开始的
        if (self::SECTION_NORMAL === $first_type) {
            $name = $this->shiftSection();
            //else if 合并为 elseif
            if ('else' === $name && !$this->is_eof && 'if' === $this->indexSection()) {
                $name .= $this->shiftSection();
            }
            //判断语句
            if ('if' === $name || 'elseif' === $name || 'else' === $name) {
                $this->tag_type = self::TAG_CONDITION;
                if ('else' !== $name) {
                    $this->result = $this->parseIf();
                } //else语句必须是孤零零的
                elseif (!$this->is_eof) {
                    $this->error('else 语句不正确');
                }

            } //不是判断语句，就是功能语句
            else {
                $this->tag_type = self::TAG_FUNCTION;
                $this->result = $name;
                $this->attributes = $this->parseAttribute();
            };
        } //关闭标签
        elseif ('/' === $this->indexSection()) {
            $this->tag_type = self::TAG_CLOSE;
            $this->shiftSection();
            $type = 0;
            $this->result = $this->shiftSection($type);
            if (self::SECTION_NORMAL !== $type || !$this->is_eof) {
                $this->error();
            }
        } //正常的语句
        else {
            $this->tag_type = self::TAG_STATEMENT;
            $this->result = $this->parseStatement();
            if (!$this->is_eof) {
                $this->error();
            }
        }
    }

    /**
     * 解析if
     * @return string
     * @throws TplException
     */
    private function parseIf()
    {
        if (self::SECTION_LEFT_BRACKET !== $this->indexSectionType()) {
            $this->error();
        }
        $re_str = $this->parseStatement(false, 0, true);
        if (!$this->is_eof) {
            $this->error();
        }
        return $re_str;
    }

    /**
     * 当为功能函数时，属性解析
     * @return array
     * @throws TplException
     */
    private function parseAttribute()
    {
        $result = [];
        while (!$this->is_eof) {
            $name_type = $this->indexSectionType();
            if ($name_type !== self::SECTION_NORMAL) {
                $this->error('无法识别的' . $this->shiftSection());
            }
            $name = $this->shiftSection();
            $value = null;
            //有等号，表示有值
            if (!$this->is_eof && '=' === $this->indexSection()) {
                $this->shiftSection();
                $value = $this->parseStatement(true, 1);
            }
            $result[$name] = $value;
        }
        if (!$this->is_eof) {
            $this->error();
        }
        return $result;
    }

    /**
     * 解析表达式
     * @param bool $normal_as_string 普通字符当成字符串
     * @param int $times 解析多少次
     * @param bool $in_bracket 是否在括号内
     * @return string
     * @throws TplException
     */
    private function parseStatement($normal_as_string = false, $times = 0, $in_bracket = false)
    {
        //是否需要加括号
        $need_add_bracket = $in_bracket;
        $result = array();
        //如果是在括号内,移除第一个符号
        if ($in_bracket) {
            $this->shiftSection();
        }
        //需要的元素
        $need_item = self::ITEM_TYPE_VALUE;
        //修饰符
        $embellish_str = '';
        $count = 0;
        while (!$this->is_eof && ($times <= 0 || $count++ < $times)) {
            $type = $this->indexSectionType();
            $join_item = 0;
            $tmp_char = '';
            //变量
            if (self::SECTION_VAR === $type) {
                $tmp_char = $this->parseVar();
                $join_item = self::ITEM_TYPE_VALUE;
            }//自增长，并且下一个就是变量
            elseif (self::SECTION_INCREASE === $type && self::SECTION_VAR === $this->nextSectionType()) {
                $tmp_char = $this->parseVar();
                $join_item = self::ITEM_TYPE_VALUE;
            } //左括号
            elseif (self::SECTION_LEFT_BRACKET === $type) {
                $tmp_char .= $this->parseStatement(false, 0, true);
                $tmp_char = $this->tryFilter($tmp_char);
                $join_item = self::ITEM_TYPE_VALUE;
            } //右括号
            elseif (self::SECTION_RIGHT_BRACKET === $type) {
                if ($in_bracket){
                    $this->shiftSection();
                }
                break;
            } //数字
            elseif (self::SECTION_NUMBER === $type || self::SECTION_FLOAT === $type) {
                $tmp_char = $this->tryFilter($this->shiftSection());
                $join_item = self::ITEM_TYPE_VALUE;
            } //字符串
            elseif (self::SECTION_STRING === $type) {
                $tmp_char = $this->tryFilter($this->shiftSection());
                $join_item = self::ITEM_TYPE_VALUE;
            } //普通字符 
            elseif (self::SECTION_NORMAL === $type) {
                //当成字符串
                if ($normal_as_string) {
                    $normal_str = $this->shiftSection();
                    $tmp_char = $this->tryFilter("'" . $normal_str . "'");
                    $join_item = self::ITEM_TYPE_VALUE;
                } //如果接下来就是 ) 或 :: 表示直接调用函数
                elseif (self::SECTION_LEFT_BRACKET === $this->nextSectionType() || self::SECTION_STATIC_METHOD === $this->nextSectionType()) {
                    $tmp_char = $this->tryFilter($this->parseFunction());
                    $join_item = self::ITEM_TYPE_VALUE;
                }
            } //数字表达
            elseif (self::SECTION_MATH === $type) {
                $need_add_bracket = true;
                $tmp_char = $this->shiftSection();
                $join_item = self::ITEM_TYPE_OPERATOR;
            } //逻辑运算
            elseif (self::SECTION_LOGIC === $type) {
                $need_add_bracket = true;
                $tmp_char = $this->shiftSection();
                $join_item = self::ITEM_TYPE_OPERATOR;
            } //修饰符
            elseif (self::SECTION_EMBELLISH === $type) {
                $embellish_str = $this->shiftSection();
                continue;
            } //逗号中止
            elseif (self::SECTION_COMMA === $type) {
                break;
            }
            if (0 === $join_item) {
                break;
            } elseif ($need_item !== $join_item) {
                $this->error('语法错误');
            }
            //如果有修饰字符
            if (!empty($embellish_str)) {
                $tmp_char = $embellish_str . $tmp_char;
                $embellish_str = '';
            }
            $result[] = $tmp_char;
            $need_item = (self::ITEM_TYPE_VALUE === $join_item) ? self::ITEM_TYPE_OPERATOR : self::ITEM_TYPE_VALUE;
        }
        if (empty($result)) {
            $this->error();
        }
        $re_str = join(' ', $result);
        //结果加上括号
        if ($need_add_bracket && $this->tag_type === self::TAG_STATEMENT) {
            $re_str = '(' . $re_str . ')';
        }
        return $re_str;
    }

    /**
     * 解析函数语法
     * @return string
     */
    private function parseFunction()
    {
        $re_str = $this->shiftSection();
        if (self::SECTION_STATIC_METHOD === $this->indexSectionType()) {
            $re_str .= $this->shiftSection();
            //接下来不是normal字符串 或者 下一字符不是 (，报错
            if (self::SECTION_NORMAL !== $this->indexSectionType() || self::SECTION_LEFT_BRACKET !== $this->nextSectionType()) {
                $this->error();
            }
            $re_str .= $this->shiftSection();
        }
        //左括号
        $re_str .= $this->shiftSection();
        $params = array();
        //参数解析
        if (self::SECTION_RIGHT_BRACKET !== $this->indexSectionType()) {
            while (!$this->is_eof) {
                $params[] = $this->parseStatement();
                //逗号，继续
                if (self::SECTION_COMMA === $this->indexSectionType()) {
                    $this->shiftSection();
                    continue;
                }
                //反括号，中止
                if (self::SECTION_RIGHT_BRACKET === $this->indexSectionType()){
                    break;
                }
                $this->error();
            }
        }
        return $re_str . join(', ', $params) . $this->shiftSection();
    }

    /**
     * 尝试加修正器
     * @param string $str 原始字符
     * @return string
     * @throws TplException
     */
    private function tryFilter($str)
    {
        if ($this->is_eof || '|' !== $this->indexSection()) {
            return $str;
        }
        $this->shiftSection();
        $type = $this->indexSectionType();
        if (self::SECTION_NORMAL !== $type) {
            $this->error('修正器 ' . $this->shiftSection() . ' 错误');
        }
        $name = $this->shiftSection();
        $args = array();
        while (!$this->is_eof) {
            if (self::SECTION_COLON !== $this->indexSectionType()) {
                break;
            }
            $this->shiftSection();
            $args[] = $this->parseArgument();
        }
        $str = 'smarty_modifier_' . $name . '(' . $str;
        if (!empty($args)) {
            $str .= ', ' . join(', ', $args);
        };
        return $this->tryFilter($str . ')');
    }

    /**
     * 解析变量
     * @return string
     * @throws TplException
     */
    private function parseVar()
    {
        $type = null;
        $re_str = $this->shiftSection($type);
        if (self::SECTION_INCREASE === $type) {
            $re_str .= $this->shiftSection();
        }
        if (self::SECTION_NORMAL !== $this->indexSectionType()) {
            $this->error('无法解析 $' . $this->indexSection() . ' 字符串');
        }
        $re_str .= $this->shiftSection();
        while (!$this->is_eof) {
            $type = $this->indexSectionType();
            // .
            if (self::SECTION_DOT === $type) {
                $re_str .= $this->parseDot();
            } //左中括号
            elseif (self::SECTION_LEFT_SQUARE_BRACKET === $type) {
                $re_str .= $this->parseSquareBracket();
            }//对象属性
            elseif (self::SECTION_PROPERTY === $type) {
                $re_str .= $this->parseProperty();
            } else {
                break;
            }
        }
        //再次检查自增/自减
        if (self::SECTION_INCREASE === $this->indexSectionType()) {
            $re_str .= $this->shiftSection();
        }
        return $this->tryFilter($re_str);
    }

    /**
     * 解析类属性
     * @return string
     * @throws TplException
     */
    private function parseProperty()
    {
        $re_str = $this->shiftSection();
        $type = $this->indexSectionType();
        if (self::SECTION_NORMAL !== $type) {
            $this->error('类属性 ' . $this->shiftSection() . ' 错误');
        }
        return $re_str . $this->shiftSection();
    }

    /**
     * 解析key，中括号 里边 的，或者 属性 或者 参数
     * @return string
     * @throws TplException
     */
    private function parseArgument()
    {
        $type = $this->indexSectionType();
        //数字， 或者字符串
        if (self::SECTION_NUMBER === $type || self::SECTION_STRING === $type) {
            return $this->shiftSection();
        } //变量
        elseif (self::SECTION_VAR === $type) {
            return $this->parseVar();
        }
        $this->error();
        return '';
    }

    /**
     * 解析中括号
     * @return string
     * @throws TplException
     */
    private function parseSquareBracket()
    {
        $this->shiftSection();
        $re_str = '[' . $this->parseStatement();
        if (']' !== $this->indexSection()) {
            $this->error('中括号解析出错');
        }
        return $re_str . $this->shiftSection();
    }

    /**
     * Dot解析
     * @return string
     * @throws TplException
     */
    private function parseDot()
    {
        $this->shiftSection();
        $re_str = '[';
        $type = $this->indexSectionType();
        if (self::SECTION_NORMAL !== $type && self::SECTION_NUMBER !== $type) {
            $this->error('符号"."解析出错');
        }
        $name = $this->shiftSection();
        if (self::SECTION_NORMAL === $type) {
            $name = "'" . $name . "'";
        }
        return $re_str . $name . ']';
    }

    /**
     * 是否是普通字符a-zA-Z和_
     * @param int $ord
     * @return bool
     */
    private function isNormalChar($ord)
    {
        return ($ord >= 65 && $ord <= 90)
        || ($ord >= 97 && $ord <= 122) || 95 === $ord;
    }

    /**
     * 是否是数字
     * @param int $ord
     * @param bool $is_hex 是否16进制
     * @return bool
     */
    private
    function isNumber($ord, $is_hex = false)
    {
        return ($ord >= 48 && $ord <= 57)
        || ($is_hex && $ord >= 97 && $ord <= 102)
        || ($is_hex && $ord >= 65 && $ord <= 70);
    }

    /**
     * 吐出一个字符
     * @return bool|string false表示已经没有字符啊
     * @throws TplException
     */
    private function shiftChar()
    {
        if ($this->is_eof) {
            return false;
        }
        $re = $this->tag_content[$this->index++];
        if ($this->index >= $this->tag_len) {
            $this->is_eof = true;
        }
        return $re;
    }

    /**
     * 取出一个字符串
     * @param int $len 长度
     * @param bool $is_shift 是否将字符串真实的取出来
     * @return string
     */
    private function subString($len, $is_shift = true)
    {
        if ($this->is_eof || $this->index + $len >= $this->tag_len) {
            return false;
        }
        $re_str = substr($this->tag_content, $this->index, $len);
        if ($is_shift) {
            $this->index += $len;
            $this->is_eof = $this->index >= $this->tag_len;
        }
        return $re_str;
    }

    /**
     * 返回当前的字符串
     * @return bool|string
     */
    private function indexChar()
    {
        if ($this->is_eof) {
            return false;
        }
        return $this->tag_content[$this->index];
    }

    /**
     * 返回切割好的一段
     * @param int $type 类型
     * @return bool|string
     */
    private function shiftSection(&$type = null)
    {
        if ($this->is_eof) {
            return false;
        }
        $re = $this->split_sections[$this->index];
        $type = $this->split_types[$this->index++];
        if ($this->index >= $this->tag_len) {
            $this->is_eof = true;
        }
        return $re;
    }

    /**
     * 返回切割好的当前段
     * @return string|bool
     */
    private function indexSection()
    {
        if ($this->is_eof) {
            return false;
        }
        return $this->split_sections[$this->index];
    }

    /**
     * 当前的类型
     * @return string|bool
     */
    private function indexSectionType()
    {
        if ($this->is_eof) {
            return false;
        }
        return $this->split_types[$this->index];
    }

    /**
     * 下一种类型
     * @return bool
     */
    private function nextSectionType()
    {
        if ($this->is_eof || $this->index + 1 >= $this->tag_len) {
            return false;
        }
        return $this->split_types[$this->index + 1];
    }

    /**
     * 压入section
     * @param string $str
     * @param int $type
     */
    private function pushSection($str, $type)
    {
        $this->split_sections[] = $str;
        $this->split_types[] = $type;
        $this->last_split_str = $str;
        $this->last_split_type = $type;
    }

    /**
     * 弹出section
     * @param null|string $type
     * @return string
     * @throws TplException
     */
    private function popSection(&$type = null)
    {
        $len = count($this->split_sections);
        if (0 === $len) {
            $this->error('');
        }
        $type = array_pop($this->split_types);
        return array_pop($this->split_sections);
    }

    /**
     * 出错了
     * @param string $msg 消息
     * @param int $code
     * @throws TplException
     */
    private function error($msg = '', $code = TplException::TPL_TAG_ERROR)
    {
        $err_msg = '{{' . $this->tag_content . '}} parse error!';
        if (!empty($msg)) {
            $err_msg .= ' ' . $msg;
        }
        throw new TplException($err_msg, $code);
    }

    /**
     * 获取标签类型
     * @return int
     */
    public function getTagType()
    {
        return $this->tag_type;
    }

    /**
     * 获取结果
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * 获取属性
     * @return array
     */
    public function getAttributes()
    {
        return (null === $this->attributes) ? [] : $this->attributes;
    }
}
