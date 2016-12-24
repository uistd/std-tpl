<?php
namespace ffan\php\tpl;
require_once '../vendor/autoload.php';
$str = '$test.aaa.bbb.ccc|ddd|eeee:0:$aa|bb:$mm[aa] 0Xff000 if (isset($a))';
$tag_parser = new TagParser($str);
echo $tag_parser->getResult(), PHP_EOL;