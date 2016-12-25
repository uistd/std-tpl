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
     * 关键字符
     */
    const SECTION_KEY_CHAR = 1;

    /**
     * 数字
     */
    const SECTION_NUMBER = 2;

    /**
     * 字符串
     */
    const SECTION_STRING = 3;

    /**
     * 逻辑运算
     */
    const SECTION_LOGIC = 4;

    /**
     * 数学和位运算
     */
    const SECTION_MATH = 5;

    /**
     * 普通字符
     */
    const SECTION_NORMAL = 6;

    /**
     * 浮点数
     */
    const SECTION_FLOAT = 7;

    /**
     * 类属性
     */
    const SECTION_PROPERTY = 8;

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
     * @var array 算术运算 位运算 和逻辑运算
     */
    private static $math_and_logic_arr = array(
        '+' => self::SECTION_MATH,
        '-' => self::SECTION_MATH,
        '*' => self::SECTION_MATH,
        '/' => self::SECTION_MATH,
        '%' => self::SECTION_MATH,
        '++' => self::SECTION_MATH,
        '&' => self::SECTION_MATH,
        '|' => self::SECTION_MATH,
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
        'and' => self::SECTION_LOGIC,
        'xor' => self::SECTION_LOGIC,
        'or' => self::SECTION_LOGIC,
        '!=' => self::SECTION_LOGIC,
        '!==' => self::SECTION_LOGIC,
        '===' => self::SECTION_LOGIC,
        '==' => self::SECTION_LOGIC,
        '<' => self::SECTION_LOGIC,
        '>' => self::SECTION_LOGIC,
        '>=' => self::SECTION_LOGIC,
        '<=' => self::SECTION_LOGIC,
        '->' => self::SECTION_PROPERTY
    );

    /**
     * @var array 关键字符 字符 => 前面是否允许空格|后面是否允许空格
     */
    private static $key_char_arr = array(
        self::CHAR_VAR => 1, //1 代表 前面可以有空格 2 代表后面可以有空格 3 前后都可以有空格
        self::CHAR_DOT => 0,
        self::CHAR_COLON => 3,
        self::CHAR_EQUAL => 3,
        self::CHAR_FILTER => 3,
        self::CHAR_LEFT_SQUARE_BRACKET => 2,
        self::CHAR_RIGHT_SQUARE_BRACKET => 3,
        self::CHAR_LEFT_BRACKET => 3,
        self::CHAR_RIGHT_BRACKET => 3
    );

    /**
     * @var string 标准内容
     */
    private $tag_content;

    /**
     * @var array 属性数组
     */
    private $attribute = array();

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
    private $result_args;

    /**
     * @var string 上一个切割字符串
     */
    private $last_split_str;

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
        $this->doParse();
    }

    /**
     * 解析
     */
    private function doParse()
    {
        $this->split();
        $this->join();
    }

    /**
     * 切割
     */
    private function split()
    {
        //中括号计数
        $square_bracket = 0;
        //括号计数
        $bracket = 0;
        while (!$this->is_eof) {
            $char = $this->indexChar();
            $ord = ord($char);
            //关键字符
            if (isset(self::$key_char_arr[$ord]) && (self::CHAR_EQUAL !== $ord || '=' !== $this->nextChar())) {
                //如果是等号，并且下一个字符也是等号，属于逻辑运算符
                if (self::CHAR_EQUAL === $ord && '=' === $this->nextChar()) {
                    $tmp_char = $this->splitMathAndLogic();
                    $this->pushSection($tmp_char, self::$math_and_logic_arr[$tmp_char]);
                    continue;
                }
                $this->pushSection($char, self::SECTION_KEY_CHAR);
                $this->shiftChar();
                $opt = self::$key_char_arr[$ord];
                //前面不允许有空格
                if (!($opt & 1) && self::CHAR_SPACE === $this->last_split_str) {
                    throw new TplException('关键字符：' . $char . ' 前面不允许有空格', TplException::TPL_TAG_ERROR);
                }
                //后面不允许有空格
                if (!($opt & 2) && !$this->is_eof && self::CHAR_SPACE === ord($this->indexChar())) {
                    throw new TplException('关键字符：' . $char . ' 后面不允许有空格', TplException::TPL_TAG_ERROR);
                }
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
            } //普通字符
            elseif ($this->isNormalChar($ord)) {
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
            } //算术运算 位运算 逻辑运算
            else if (isset(self::$math_and_logic_arr[$char])) {
                $tmp_char = $this->splitMathAndLogic();
                $this->pushSection($tmp_char, self::$math_and_logic_arr[$tmp_char]);
            } else {
                throw new TplException('无法解析的字符' . $char, TplException::TPL_TAG_ERROR);
            }
        }
        if (0 !== $square_bracket || 0 !== $bracket) {
            throw new TplException('括号或者中括号不配对', TplException::TPL_TAG_ERROR);
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
            if (isset(self::$key_char_arr[$ord]) || isset(self::$math_and_logic_arr[$char])) {
                break;
            }
            if (self::CHAR_SPACE === $ord) {
                break;
            }
            if (!$this->isNormalChar($ord) && !$this->isNumber($ord)) {
                throw new TplException('不支持：' . $re_str . $this->shiftChar(), TplException::TPL_TAG_ERROR);
            }
            $re_str .= $this->shiftChar();
        }
        //普通字符后面的空格去掉，方面根据下一个字判断类型
        $this->trim();
        return $re_str;
    }

    /**
     * 解析算术和逻辑运算符
     * @return string
     * @throws TplException
     */
    private function splitMathAndLogic()
    {
        $re_str = $this->shiftChar();
        while (!$this->is_eof) {
            $char = $this->indexChar();
            if (!isset(self::$math_and_logic_arr[$re_str . $char])) {
                break;
            }
            $re_str .= $this->shiftChar();
        }
        if (('++' === $re_str || '--' === $re_str)
            && ('++' == $this->last_split_str || '--' === $this->last_split_str)
        ) {
            throw new TplException('字符串：' . $this->last_split_str . $re_str . ' 出错', TplException::TPL_TAG_ERROR);
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
                    throw new TplException('小数点出错', TplException::TPL_TAG_ERROR);
                }
                $allow_dot = false;
                $allow_hex = false;
                $re_str .= $this->shiftChar();
                continue;
            }
            //遇到空格或者关键字符中止
            if (isset(self::$key_char_arr[$ord])
                || self::CHAR_SPACE === $ord
                || isset(self::$math_and_logic_arr[$char])
            ) {
                break;
            }
            if (!$this->isNumber($ord, $is_hex)) {
                throw new TplException('数字 ' . $re_str . $this->shiftChar() . ' 解析出错', TplException::TPL_TAG_ERROR);
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
            throw new TplException('字符串 ' . $re_str . ' 出错', TplException::TPL_TAG_ERROR);
        }
        return $re_str;
    }

    /**
     * 连接起来
     * @throws TplException
     */
    private function join()
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
                    $this->result = $this->parseStatement();
                } //else语句必须是孤零零的
                elseif (!$this->is_eof) {
                    throw new TplException('else 语句不正确', TplException::TPL_TAG_ERROR);
                }

            } //不是判断语句，就是功能语句
            else {
                $this->tag_type = self::TAG_FUNCTION;
                $this->result = $name;
                $this->result_args = $this->parseAttribute();
            };
        } //关闭标签
        elseif ('/' === $this->indexSection()) {
            $this->tag_type = self::TAG_CLOSE;
            $this->shiftSection();
            $type = 0;
            $this->result = $this->shiftSection($type);
            if (self::SECTION_NORMAL !== $type || !$this->is_eof) {
                throw new TplException('关闭标签出错', TplException::TPL_TAG_ERROR);
            }
        } //正常的语句
        else {
            $this->tag_type = self::TAG_STATEMENT;
            $this->result = $this->parseStatement();
        }
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
                throw new TplException('无法识别的' . $this->shiftSection(), TplException::TPL_TAG_ERROR);
            }
            $name = $this->shiftSection();
            $value = null;
            //有等号，表示有值
            if (!$this->is_eof && '=' === $this->indexSection()) {
                $this->shiftSection();
                $value = $this->parseStatement();
            }
            $result[$name] = $value;
        }
        if (!$this->is_eof) {
            throw new TplException('语法解析错误', TplException::TPL_TAG_ERROR);
        }
        return $result;
    }

    /**
     * 解析表达式
     * @return string
     * @throws TplException
     */
    private function parseStatement()
    {
        $result = array();
        //需要的元素
        $need_item = self::ITEM_TYPE_VALUE;
        $last_item = 0;
        //自增自减
        $plus_minus_str = '';
        while (!$this->is_eof) {
            $type = $this->indexSectionType();
            $join_item = 0;
            $tmp_char = '';
            //变量
            if (self::SECTION_KEY_CHAR === $type) {
                $char = $this->indexSection();
                if ('$' === $char) {
                    $tmp_char = $this->joinVar();
                    $join_item = self::ITEM_TYPE_VALUE;
                } elseif ('(' === $char) {
                    $tmp_char = $this->shiftSection();
                    $tmp_char .= $this->parseStatement();
                    if (')' !== $this->indexSection()) {
                        throw new TplException('括号表达式出错', TplException::TPL_TAG_ERROR);
                    }
                    $tmp_char .= $this->shiftSection();
                    $tmp_char = $this->tryFilter($tmp_char);
                    $join_item = self::ITEM_TYPE_VALUE;
                }
            } //数字
            elseif (self::SECTION_NUMBER === $type || self::SECTION_FLOAT === $type) {
                $tmp_char = $this->tryFilter($this->shiftSection());
                $join_item = self::ITEM_TYPE_VALUE;
            } //字符串
            elseif (self::SECTION_STRING === $type) {
                $tmp_char = $this->tryFilter($this->shiftSection());
                $join_item = self::ITEM_TYPE_VALUE;
            } //数字表达
            elseif (self::SECTION_MATH === $type) {
                $tmp_char = $this->shiftSection();
                //自增自减特殊判断
                if ('++' === $tmp_char || '--' === $tmp_char) {
                    if (self::ITEM_TYPE_VALUE === $last_item) {
                        $last_value = array_pop($result);
                        $result[] = $last_value . $tmp_char;
                    } else {
                        $plus_minus_str = $tmp_char;
                    }
                    continue;
                } else {
                    $join_item = self::ITEM_TYPE_OPERATOR;
                }
            } //逻辑运算
            elseif (self::SECTION_LOGIC === $type) {
                $tmp_char = $this->shiftSection();
            }
            if (0 === $join_item) {
                break;
            } elseif ($need_item !== $join_item) {
                throw new TplException('表达式出错', TplException::TPL_TAG_ERROR);
            }
            $last_item = $join_item;
            if (!empty($plus_minus_str)) {
                $tmp_char = $plus_minus_str . $tmp_char;
                $plus_minus_str = '';
            }
            $result[] = $tmp_char;
            $need_item = (self::ITEM_TYPE_VALUE === $join_item) ? self::ITEM_TYPE_OPERATOR : self::ITEM_TYPE_VALUE;
        }
        if (empty($result) || self::ITEM_TYPE_VALUE === $need_item) {
            throw new TplException('表达式出错', TplException::TPL_TAG_ERROR);
        }
        return join(' ', $result);
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
            throw new TplException('修正器 ' . $this->shiftSection() . ' 错误', TplException::TPL_TAG_ERROR);
        }
        $name = $this->shiftSection();
        $args = array();
        while (!$this->is_eof) {
            if (self::SECTION_KEY_CHAR !== $this->indexSectionType() || ':' !== $this->indexSection()) {
                break;
            }
            $this->shiftSection();
            $args[] = $this->joinArg();
        }
        $str = 'smarty_modifier_' . $name . '(' . $str;
        if (!empty($args)) {
            $str .= join(', ', $args);
        };
        return $str . ')';
    }

    /**
     * 解析变量
     * @return string
     * @throws TplException
     */
    private function joinVar()
    {
        $re_str = $this->shiftSection();
        if (self::SECTION_NORMAL !== $this->indexSectionType()) {
            throw new TplException('无法解析 $' . $this->indexSection() . ' 字符串', TplException::TPL_TAG_ERROR);
        }
        $re_str .= $this->shiftSection();
        while (!$this->is_eof) {
            $type = $this->indexSectionType();
            if (self::SECTION_KEY_CHAR !== $type && self::SECTION_PROPERTY !== $type) {
                break;
            }
            $ord = ord($this->indexSection());
            // .
            if (self::CHAR_DOT === $ord) {
                $re_str .= $this->joinDot();
            } //左中括号
            elseif (self::CHAR_LEFT_SQUARE_BRACKET === $ord) {
                $re_str .= $this->joinSquareBracket();
            } elseif (self::SECTION_PROPERTY === $type) {
                $re_str .= $this->joinProperty();
            } else {
                break;
            }
        }
        return $this->tryFilter($re_str);
    }

    /**
     * 解析类属性
     * @return string
     * @throws TplException
     */
    private function joinProperty()
    {
        $re_str = $this->shiftSection();
        $type = $this->indexSectionType();
        if (self::SECTION_NORMAL !== $type) {
            throw new TplException('类属性 ' . $this->shiftSection() . ' 错误', TplException::TPL_TAG_ERROR);
        }
        return $re_str . $this->shiftSection();
    }

    /**
     * 解析key，中括号 里边 的，或者 属性 或者 参数
     * @return string
     * @throws TplException
     */
    private function joinArg()
    {
        $type = $this->indexSectionType();
        //数字， 或者字符串
        if (self::SECTION_NUMBER === $type || self::SECTION_STRING === $type) {
            return $this->shiftSection();
        } //变量
        elseif (self::SECTION_KEY_CHAR === $type && '$' === $this->indexSection()) {
            return $this->joinVar();
        }
        throw new TplException('arg错误', TplException::TPL_TAG_ERROR);
    }

    /**
     * 解析中括号
     * @return string
     * @throws TplException
     */
    private function joinSquareBracket()
    {
        $this->shiftSection();
        $re_str = '[' . $this->joinArg();
        if (']' !== $this->indexSection()) {
            throw new TplException('中括号解析出错', TplException::TPL_TAG_ERROR);
        }
        return $re_str . $this->shiftSection();
    }

    /**
     * Dot解析
     * @return string
     * @throws TplException
     */
    private function joinDot()
    {
        $this->shiftSection();
        $re_str = '[';
        $type = $this->indexSectionType();
        if (self::SECTION_NORMAL !== $type && self::SECTION_NUMBER !== $type) {
            throw new TplException('符号"."解析出错', TplException::TPL_TAG_ERROR);
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
     * 移除空格
     */
    private function trim()
    {
        while (!$this->is_eof && ' ' === $this->indexChar()) {
            $this->shiftChar(false);
        }
    }

    /**
     * 吐出一个字符
     * @param bool $is_throw_exception 如果没有字符了，是否要抛出异常
     * @return bool|string false表示已经没有字符啊
     * @throws TplException
     */
    private function shiftChar($is_throw_exception = true)
    {
        if ($this->is_eof) {
            if ($is_throw_exception) {
                throw new TplException('解析出错', TplException::TPL_TAG_ERROR);
            }
            return false;
        }
        $re = $this->tag_content[$this->index++];
        if ($this->index >= $this->tag_len) {
            $this->is_eof = true;
        }
        return $re;
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
     * 返回下一个字符
     * @return string|bool
     */
    private function nextChar()
    {
        if ($this->index + 1 < $this->tag_len) {
            return $this->tag_content[$this->index + 1];
        }
        return false;
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
     * 压入section
     * @param string $str
     * @param int $type
     */
    private function pushSection($str, $type)
    {
        $this->split_sections[] = $str;
        $this->split_types[] = $type;
        $this->last_split_str = $str;
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
            throw new TplException('parse error', TplException::TPL_TAG_ERROR);
        }
        $type = array_pop($this->split_types);
        return array_pop($this->split_sections);
    }

    /**
     * 返回属性部分
     * @return array
     */
    public function getArgs()
    {
        return $this->attribute;
    }
}