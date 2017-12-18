<?php
namespace UiStd\Tpl;

/**
 * Class TplException
 * @package UiStd\Tpl
 */
class TplException extends \Exception
{
    /**
     * 模板编译出错
     */
    const TPL_COMPILE_ERROR = 1;

    /**
     * 标签解析出错
     */
    const TPL_TAG_ERROR = 2;

    /**
     * 模板名称错误
     */
    const TPL_NAME_ERROR = 3;
}
