<?php
namespace ffan\php\tpl;
require_once '../vendor/autoload.php';
$str = '$test.aaa.bbb.ccc|ddd|eeee:0:$bb';
$tag_parser = new TagParser($str);
echo $tag_parser->getResult(), PHP_EOL;