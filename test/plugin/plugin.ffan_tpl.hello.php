<?php
/**
 * 测试插件功能
 * @param array $params
 * @param \ffan\php\tpl\Tpl $tpl
 * @return string
 */
function plugin_ffan_tpl_hello($params, $tpl)
{
    return 'this is test plugin '. print_r($params, true);
}