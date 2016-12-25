<?php
namespace ffan\php\tpl;
require_once '../vendor/autoload.php';
$str = '$test.aaa.bbb.ccc->ff|ddd|eeee:0:$aa.0|bb:$mm[aa]';
$tag_parser = new TagParser($str);
print_r( $tag_parser->getResult() );