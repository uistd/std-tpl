<?php
namespace ffan\php\tpl;
require_once '../vendor/autoload.php';

function test_parse($str){
    $tag_parser = new TagParser($str);
    echo $tag_parser->getResult(), PHP_EOL;
    $attributes = $tag_parser->getAttributes();
    if (!empty($attributes)){
        print_r($attributes);
    }

}
test_parse('$test.aaa.bbb.ccc->ff|ddd|eeee:0:$aa.0|bb:$mm["aa"]');

test_parse('foreach from=$for_value item=rs step=1');
test_parse('if ($a > 0 && $b < 0 || $c == 0)');

