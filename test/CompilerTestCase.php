<?php
namespace FFan\Std\Tpl;

use FFan\Std\Common\Config;

require_once '../vendor/autoload.php';
Config::addArray(
    array(
        'ffan-tpl' => array(
            'tpl_dir' => 'test/view',
            'cache_result' => false
        ),
        'runtime_path' => __DIR__ . DIRECTORY_SEPARATOR
    )

);

function test_parse($str)
{
    $tag_parser = new TagParser($str);
    echo $tag_parser->getResult(), PHP_EOL;
    $attributes = $tag_parser->getAttributes();
    if (!empty($attributes)) {
        print_r($attributes);
    }

}

test_parse('$test.aaa.bbb.ccc->ff|ddd|eeee:0:$aa.0|bb:$mm["aa"]');

test_parse('foreach from=$for_value item=rs step=1');
test_parse('foreach ($forvar as $key => $value )');
test_parse('if ($a > 0 && $b < 0 || $c == 0)');
test_parse('elseif ($a > 0 && $b < 0 || $c == 0)');
test_parse('else if ($a > 0 && $b < 0 || $c == 0)');
test_parse('else');
test_parse('if (!substr("aaa", 0, 1) === "b")');
test_parse('if (!substr("aa([]]]]]\"\'a", 0, 1) === "b")');
test_parse('$a = 1');
test_parse('$a = 1.02');
test_parse('$a = 0xaaff');
test_parse('$a = 3.');
test_parse('$a++');
test_parse('--$a');
test_parse('for ($i = 0; $i < 10; ++$i)');
test_parse('Log::debug("aaa")');
/*
 * Tpl::run('test1', array(
    'str' => 'this is test string',
    'tool_menu' => array(),
    'code_type' => 'run',
    '_STATIC_' => 'http://ffan.com'
));
*/
Tpl::registerPlugin('my_plugin', function(){
    print_r(func_get_args());
});
Tpl::registerGrep('my_grep', function(){
    print_r(func_get_args());
});

Tpl::registerGrep('hello', function($str){
    return json_encode($str);
});
Tpl::run('test2', array(
    'test_var' => 'hello world'
));
