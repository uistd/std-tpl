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
     * 修饰符
     */
    const CHAR_FILTER = 124;

    /**
     * 左中括号
     */
    const CHAR_LEFT_BRACKET = 91;

    /**
     * 右中括号
     */
    const CHAR_RIGHT_BRACKET = 93;

    /**
     * 点
     */
    const CHAR_DOT = 46;

    /**
     * @var string 标准内容
     */
    private $tag_content;

    /**
     * @var array 栈
     */
    private $stack = array();

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
        //解析标签头
        $this->parseHead();
        while ($this->index < $this->tag_len) {
            $this->parseAttribute();
        }
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
            if (self::CHAR_EQUAL === $this->tag_content[$this->index]) {
                $this->index;
                $value = $this->parseValue($attribute_name);
            } else {
                $value = '';
            }
            $this->attribute[$attribute_name] = $value;
        }
        $this->trim();
    }

    /**
     * 解析值
     * @param string $name 属性名
     * @return string
     * @throws TplException
     */
    private function parseValue($name)
    {
        $this->trim();
        if ($this->index >= $this->tag_len) {
            throw new TplException('属性:' . $name . ' 值解析错误', TplException::TPL_TAG_ERROR);
        }
        $char = $this->tag_content[$this->index++];
        $ord = ord($char);
        //变量
        if (self::CHAR_VAR === $char) {
            return $this->parseVar();
        }
        //单引号 或者 双引号
        if (self::CHAR_QUOTE === $ord || self::CHAR_SINGLE_QUOTE === $ord) {
            return $this->parseNormal($ord);
        }
        return $this->fetchName(self::CHAR_SPACE, false, true);
    }

    /**
     * 普通字符解析
     * @param int $quote_type 引号类型
     * @return string
     * @throws TplException
     */
    private function parseNormal($quote_type)
    {
        $in_escape = false;
        $is_eof = false;
        $re_str = '';
        while ($this->index < $this->tag_len) {
            $char = $this->tag_content[$this->index++];
            $ord = ord($char);
            //正在转义中
            if ($in_escape) {
                if ($ord === $quote_type) {
                    $re_str .= $char;
                } else {
                    $re_str .= '\\' . $char;
                }
                $in_escape = false;
            } //结束了
            elseif ($ord === $quote_type) {
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
            throw new TplException('解析出错', TplException::TPL_TAG_ERROR);
        }
        return $re_str;
    }

    /**
     * 解析变量
     * @param bool $in_bracket 是否在中括号中
     * @return string
     * @throws TplException
     */
    private function parseVar($in_bracket = false)
    {
        $re_str = '$';
        $is_eof = false;
        $end_char_arr = array(
            self::CHAR_SPACE => true,
            self::CHAR_DOT => true,
        );
        if ($in_bracket) {
            $end_char_arr[] = self::CHAR_RIGHT_BRACKET;
        } else {
            $end_char_arr = self::CHAR_LEFT_BRACKET;
        }
        $name = $this->fetchName($end_char_arr, true);
        if (0 == strlen($name)) {
            throw new TplException('变量名解析错误', TplException::TPL_TAG_ERROR);
        }
        $re_str .= $name;
        //解析完成
        if (0 === $this->break_char) {
            return $re_str;
        }
        //遇到.号
        if (self::CHAR_DOT === $this->break_char) {
            $key_name = $this->fetchName($end_char_arr);
        }

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
        $is_int = $ord >= 48 && $ord <= 57;
        if ($is_int && !$allow_int) {
            throw new TplException('数字不能出现在关键字第一位', TplException::TPL_TAG_ERROR);
        }
        //[a-zA-Z_]
        if (!($ord >= 65 && $ord <= 90) && !($ord >= 97 && $ord <= 122) && $ord !== 95 && !$is_int) {
            throw new TplException('关键字只允许a-zA-Z0-9_的字符', TplException::TPL_TAG_ERROR);
        }
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
        while ($this->index < $this->tag_len) {
            $char = $this->tag_content[$this->index];
            $ord = ord($char);
            if (($is_arr && isset($end_char[$ord])) || (!$is_arr && $ord === $ord)) {
                $this->break_char = $ord;
                break;
            }
            $this->validNameCheck($ord, $allow_int);
            $allow_int = true;
            $result .= $char;
            $this->index++;
        }
        return $result;
    }

    /**
     * 移除空格
     */
    private function trim()
    {
        while ($this->index < $this->tag_len) {
            $char = $this->tag_content[$this->index];
            if (self::CHAR_SPACE !== ord($char)) {
                break;
            }
            $this->index++;
        }
    }
}