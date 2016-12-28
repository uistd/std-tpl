<?php
namespace ffan\php\tpl;

/**
 * Class TplGrep 管道类
 * @package ffan\php\tpl
 */
class TplGrep
{
    /**
     * @param string $str 首字母大写
     * @return string
     */
    public static function capitalize($str)
    {
        if (!is_string($str)){
            return $str;
        }
        return ucfirst($str);
    }
}
