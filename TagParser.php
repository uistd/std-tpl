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
    const TYPE_KEY_CHAR = 1;

    /**
     * 数字
     */
    const TYPE_NUMBER = 2;

    /**
     * 标签头
     */
    const TYPE_HEAD = 3;

    /**
     * 字符串
     */
    const TYPE_STRING = 4;

    /**
     * 属性标签
     */
    const TYPE_ATTRIBUTE = 5;

    /**
     * 函数（非自定义插件）
     */
    const TYPE_FUNCTION = 6;

    /**
     * @var array 操作符
     */
    private static $operator_char = array(
        '+' => true,
        '-' => true,
        '*' => true,
        '/' => true,
        '%' => true,
        '++' => true,
        '--' => true,
        '+=' => true,
        '-=' => true,
        '*=' => true,
        '/=' => true,
        '&=' => true,
        '^=' => true,
        '&' => true,
        '^' => true,
        '|' => true,
        '|=' => true,
        '%=' => true,
        '<<=' => true,
        '>>=' => true,
        '<<' => true,
        '>>' => true
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
     * @var string
     */
    private $tag_head = '';

    /**
     * @var string 导致中断的字符
     */
    private $break_char = '';

    /**
     * @var bool 是否已经完成了
     */
    private $is_eof = false;

    /**
     * @var string 结果
     */
    private $result = '';

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
        $result_arr = array();
        $type_arr = array();
        $last_ord = 0;
        while (!$this->is_eof) {
            $char = $this->indexChar();
            $ord = ord($char);
            //关键字符
            if (isset(self::$key_char_arr[$ord])) {
                $result_arr[] = $char;
                $type_arr[] = self::TYPE_KEY_CHAR;
                $this->popChar();
                $opt = self::$key_char_arr[$ord];
                //前面不允许有空格
                if (!($opt & 1) && self::CHAR_SPACE === $last_ord) {
                    throw new TplException('关键字符：' . $char . ' 前面不允许有空格', TplException::TPL_TAG_ERROR);
                }
                //后面不允许有空格
                if (!($opt & 2) && !$this->is_eof && self::CHAR_SPACE === ord($this->indexChar())) {
                    throw new TplException('关键字符：' . $char . ' 后面不允许有空格', TplException::TPL_TAG_ERROR);
                }
            } //普通字符
            elseif ($this->isNormalChar($ord)) {
                $result_arr[] = $this->parseNormal();
                //如果是第一个，表示标签头
                if (empty($result_arr)) {
                    $type = self::TYPE_HEAD;
                } //如果下一个字符是 左括号，表示内置函数
                elseif (self::CHAR_LEFT_BRACKET === $ord) {
                    $type = self::TYPE_FUNCTION;
                } //当成属性
                else {
                    $type = self::TYPE_ATTRIBUTE;
                }
                $type_arr[] = $type;;

            } //数字
            elseif ($this->isNumber($ord)) {
                $result_arr[] = $this->parseNumber();
                $type_arr[] = self::TYPE_NUMBER;
            } //引号表示字符串
            elseif ($ord === self::CHAR_SINGLE_QUOTE || $ord === self::CHAR_QUOTE) {
                $result_arr[] = $this->parseString($ord);
                $type_arr[] = self::TYPE_STRING;
            }//空格
            else if ($ord === self::CHAR_SPACE) {
                $this->popChar();
            } else {
                throw new TplException('无法解析的字符' . $char, TplException::TPL_TAG_ERROR);
            }
            $last_ord = $ord;
        }
        print_r($result_arr);
    }

    /**
     * 普通字符解析
     * @return string
     * @throws TplException
     */
    private function parseNormal()
    {
        $re_str = $this->popChar();
        while (!$this->is_eof) {
            $char = $this->indexChar();
            $ord = ord($char);
            if (isset(self::$key_char_arr[$ord])) {
                break;
            }
            if (self::CHAR_SPACE === $ord) {
                break;
            }
            if (!$this->isNormalChar($ord) && !$this->isNumber($ord)) {
                throw new TplException('不支持：' . $re_str . $this->popChar(), TplException::TPL_TAG_ERROR);
            }
            $re_str .= $this->popChar();
        }
        return $re_str;
    }

    /**
     * 属性解析
     */
    private function parseAttribute()
    {
        $attribute_name = $this->fetchName(array(self::CHAR_SPACE => true, self::CHAR_EQUAL => true), true);
        //如果不是由 = 引起的，开始找 = 号
        if (self::CHAR_EQUAL !== $this->break_char) {
            $this->trim();
            //找到 = 号，开始找值
            if (!$this->is_eof && self::CHAR_EQUAL === $this->tag_content[$this->index]) {
                $this->index;
                $value = $this->parseValue();
            } else {
                $value = '';
            }
            $this->attribute[$attribute_name] = $value;
        }
        $this->trim();
    }

    /**
     * 解析值
     * @return string
     * @throws TplException
     */
    private function parseValue()
    {
        $this->trim();
        $char = $this->indexChar();
        $ord = ord($char);
        //变量
        if (self::CHAR_VAR === $ord) {
            return $this->parseVar();
        }
        //单引号 或者 双引号
        if (self::CHAR_QUOTE === $ord || self::CHAR_SINGLE_QUOTE === $ord) {
            return $this->parseString($ord);
        }
        $end_char_arr = array(
            self::CHAR_SPACE => true,
            self::CHAR_COLON => true,
            self::CHAR_RIGHT_SQUARE_BRACKET => true
        );
        return $this->fetchName($end_char_arr, true, true);
    }

    /**
     * 解析数字
     * @return string
     * @throws TplException
     */
    private function parseNumber()
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
                $re_str .= $this->popChar();
                continue;
            }
            //遇到.号
            if (self::CHAR_DOT === $ord) {
                if ($is_hex || !$allow_dot) {
                    throw new TplException('小数点出错', TplException::TPL_TAG_ERROR);
                }
                $allow_dot = false;
                $allow_hex = false;
                $re_str .= $this->popChar();
                continue;
            }
            //遇到空格或者关键字符中止
            if (isset(self::$key_char_arr[$ord]) || self::CHAR_SPACE === $ord) {
                break;
            }
            if (!$this->isNumber($ord, $is_hex)) {
                throw new TplException('数字 ' . $re_str . $this->popChar() . ' 解析出错', TplException::TPL_TAG_ERROR);
            }
            $re_str .= $this->popChar();
        }
        return $re_str;
    }

    /**
     * 普通字符解析
     * @param int $quote_type 引号类型
     * @return string
     * @throws TplException
     */
    private function parseString($quote_type)
    {
        $in_escape = false;
        $is_eof = false;
        $re_str = $this->popChar();
        while (!$this->is_eof) {
            $char = $this->popChar();
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
     * 解析变量
     * @return string
     * @throws TplException
     */
    private function parseVar()
    {
        $this->popChar();
        $re_str = '$';
        static $end_char_arr = array(
            self::CHAR_SPACE => true,
            self::CHAR_DOT => true,
            self::CHAR_RIGHT_SQUARE_BRACKET => true,
            self::CHAR_LEFT_SQUARE_BRACKET => true,
            self::CHAR_FILTER => true
        );
        $name = $this->fetchName($end_char_arr, true);
        if (0 == strlen($name)) {
            throw new TplException('变量名解析错误', TplException::TPL_TAG_ERROR);
        }
        $re_str .= $name;
        while ($this->break_char > 0) {
            switch ($this->break_char) {
                case self::CHAR_DOT:
                    $re_str .= $this->parseDot();
                    break;
                case self::CHAR_LEFT_SQUARE_BRACKET:
                    $re_str .= $this->parseBracket();
                    break;
                default:
                    //直接break while
                    break 2;
            }
        }
        $this->trim();
        //修正器解析
        while (!$this->is_eof && '|' === $this->indexChar()) {
            $filter = $this->parseFilter();
            print_r($filter);
            $this->trim();
            $re_str = 'modifier_' . $filter['name'] . '(' . $re_str;
            if (!empty($filter['args'])) {
                $re_str .= ', ' . join(', ', $filter['args']);
            }
            $re_str .= ')';
        }
        return $re_str;
    }

    /**
     * 修正器解析
     * @return array
     * @throws TplException
     */
    private function parseFilter()
    {
        $end_char = array(
            self::CHAR_SPACE => true,
            self::CHAR_COLON => true,
            self::CHAR_FILTER => true
        );
        $this->popChar();
        $this->trim();
        $filter_name = $this->fetchName($end_char, true);
        if (0 == strlen($filter_name)) {
            throw new TplException('修正器解析出错', TplException::TPL_TAG_ERROR);
        }
        $result = array('name' => $filter_name, 'args' => []);
        while ($this->break_char > 0) {
            $this->trim();
            //包含
            if ($this->is_eof || ':' !== $this->indexChar()) {
                break;
            }
            //弹出 ： 号
            $this->popChar();
            $this->trim();
            $filter_arg = $this->parseValue();
            $result['args'][] = $filter_arg;
        }
        return $result;
    }

    /**
     * 解析中括号
     * @return string
     * @throws TplException
     */
    private function parseBracket()
    {
        //弹出[
        $this->popChar();
        $re_str = '[';
        $re_str .= $this->parseValue();
        $this->trim();
        if (']' !== $this->popChar(false)) {
            throw new TplException('中括号不配对', TplException::TPL_TAG_ERROR);
        }
        $re_str .= ']';
        $this->break_char = self::CHAR_RIGHT_SQUARE_BRACKET;
        return $re_str;
    }

    /**
     * Dot解析
     * @return string
     * @throws TplException
     */
    private function parseDot()
    {
        $this->index++;
        $re_str = "['";
        $end_char_arr = array(
            self::CHAR_SPACE => true,
            self::CHAR_LEFT_SQUARE_BRACKET => true,
            self::CHAR_DOT => true,
            self::CHAR_FILTER => true
        );
        $name = $this->fetchName($end_char_arr, true, false);
        if (0 == strlen($name)) {
            throw new TplException('变更名解析错误', TplException::TPL_TAG_ERROR);
        }
        $re_str .= $name . "']";
        return $re_str;
    }

    /**
     * 是否合法关键字
     * @param int $ord ascii码值
     * @param bool $allow_int 是否允许int
     * @throws TplException
     */
    private function validNameCheck($ord, $allow_int)
    {
        //如果是数字 子游标不能是0
        $is_int = $this->isNumber($ord);
        if ($is_int && !$allow_int) {
            throw new TplException('数字不能出现在关键字第一位', TplException::TPL_TAG_ERROR);
        }
        //a-zA-z_和数字
        if (!$this->isNormalChar($ord) && !$is_int) {
            throw new TplException('关键字只允许a-zA-Z0-9_的字符', TplException::TPL_TAG_ERROR);
        }
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
     * 解析头
     */
    private function parseHead()
    {
        $this->tag_head = $this->fetchName(self::CHAR_SPACE);
        $this->trim();
    }

    /**
     * 提取名字
     * @param int|array $end_char
     * @param bool $is_arr 传入的是否是数组
     * @param bool $allow_int 第一个字符是否可以是int
     * @return string
     * @throws TplException
     */
    private function fetchName($end_char = self::CHAR_SPACE, $is_arr = false, $allow_int = false)
    {
        $result = '';
        $this->break_char = 0;
        while (!$this->is_eof) {
            $char = $this->indexChar();
            $ord = ord($char);
            if (isset(self::$operator_char[$char])) {
                $this->break_char = $ord;
                break;
            }
            if (($is_arr && isset($end_char[$ord])) || (!$is_arr && $ord === $ord)) {
                $this->break_char = $ord;
                break;
            }
            $this->validNameCheck($ord, $allow_int);
            $allow_int = true;
            $result .= $char;
            $this->popChar();
        }
        return $result;
    }

    /**
     * 移除空格
     */
    private function trim()
    {
        while (!$this->is_eof && ' ' === $this->indexChar()) {
            $this->popChar(false);
        }
    }

    /**
     * 吐出一个字符
     * @param bool $is_throw_exception 如果没有字符了，是否要抛出异常
     * @return bool|string false表示已经没有字符啊
     * @throws TplException
     */
    private function popChar($is_throw_exception = true)
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
     * 返回属性部分
     * @return array
     */
    public function getArgs()
    {
        return $this->attribute;
    }

    /**
     * 获取结果
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}