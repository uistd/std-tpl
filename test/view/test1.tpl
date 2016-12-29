<!DOCTYPE html>
<html>
<head>
    <title>协议管理工具</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="static/css/main.css"/>
    <script type='text/javascript' src="static/js/tool.js"></script>
</head>
<body>
<div id="head">
    <span class="item"><a href="index.php">首页</a></span>
    <span class="item"><a href="index.php?a=proto_fpm">生成fpm协议</a></span>
    <span class="item"><a href="index.php?a=proto_route">生成route协议</a></span>
    <span class="item"><a href="index.php?a=proto_module">生成module协议</a></span>
    <span class="item"><a href="index.php?a=proto_demo">demo协议</a></span>
    <span class="item"><a href="index.php?a=xtail">xtail协议</a></span>
    <span class="item"><a href="index.php?a=other">other协议</a></span>
</div>
<hr/>
<div>
    {{if ('run' == $code_type)}}
    <form action="index.php?a=run_code" method="post">
        <div>
            <textarea style="width:90%;height:300px;margin:0 5%;" name="code"
                      id="run_code">{{if isset($code) }}{{$code}}{{/if}}</textarea>
        </div>
        <div style="text-align:center"><input type="submit" value="执行代码"></div>
    </form>
    {{elseif ('simulate' == $code_type)}}
    <form action="index.php?a=pack_data&proto={{$proto}}" method="post">
        <div>
            <textarea style="width:70%;height:300px;margin:0 15%;" name="code"
                      id="pack_code">{{if isset($code) }}{{$code}}{{/if}}</textarea>
        </div>
        <div style="text-align:center"><input type="submit" value="打包数据"></div>
    </form>
    {{elseif ('unpack' == $code_type)}}
    <form action="index.php?a=unpack_data" method="post">
        <div>
            <textarea style="width:70%;height:300px;margin:0 15%;" name="code"
                      id="unpack_code">{{if isset($code) }}{{$code}}{{/if}}</textarea>
        </div>
        <div style="text-align:center"><input type="submit" value="解包数据"></div>
    </form>
    {{/if}}
</div>
<div style="width:90%;margin: 0 auto;word-break: break-all; word-wrap:break-word;">
    {{if !empty( $error )}}
    <div style="background-color:yellow;font-size:15px;">
        <pre class="error_msg" style="padding:2%;">{{$error}}</pre>
    </div>
    {{/if}}
    {{if !empty( $notice )}}
    <div style="background-color:yellow;font-size:15px;">
        <pre class="notice_msg" style="padding:2%;">{{$notice}}</pre>
    </div>
{{/if}}

{{if !empty( $debug )}}
    <div style="background-color:#EBFBE6;font-size:15px;">
        <pre class="notice_msg" style="padding:2%;">{{$debug}}</pre>
    </div>
{{/if}}
{{foreach $arr_list as $key => $value}}
    {{foreach $arr_list as $key2 => $value2}}
        <pre class="notice_msg" style="padding:2%;">{{$key}} => {{$value}}</pre>
        {{foreachelse}}
        <pre>Noda {{$key}}</pre>
    {{/foreach}}
{{/foreach}}
{{foreach $list as list($a, $b, $c)}}
    {{foreach $list as $m => list($a2, $b2, $c2)}}
        {{$a + $b + $c == $b}}
    {{/foreach}}
{{/foreach}}
{{$b = 10}}
{{substr($str, 0, 1)}}
{{for $i = 0; $i < 10; ++$i}}
    <i>{{++$i}}</i>
    <i>{{$i++}}</i>
    <i>{{(int)$i}}</i>
    <i>{{(float)$i}}</i>
    <i>{{(string)$i}}</i>
    <i>{{(object)$i}}</i>
    <i>{{(array)$i}}</i>
{{/for}}
{{literal}}
    {{foreach $list as list($a, $b, $c)}}
    {{foreach $list as $m => list($a2, $b2, $c2)}}
    {{$a + $b + $c == $b}}
    {{/foreach}}
    {{/foreach}}
{{/literal}}
{{$m++}}
<i>{{$i++}}</i>
<i>{{$m = $n = 10}}</i>
    {{include file="aaa/bbb/ccc.tpl"}}
</div>