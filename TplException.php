<?php
namespace ffan\php\tpl;

/**
 * Class TplException
 * @package ffan\php\tpl
 */
class TplException extends \Exception
{
    /**
     * 模板编译出错
     */
    const TPL_COMPILE_ERROR = 1;
}
