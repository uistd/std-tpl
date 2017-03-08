{{$test_var|capitalize:1:2:'aa':'bb':'ccc'}}
{{++$test_a}}
{{$test_a}}
{{$test_a *= 100}}
{{$test_a}}
{{$test_a += 100}}
{{$test_a}}
{{$test_a >> 2}}
普通文字
aaaa
aaa
bbbb
{{$test_a %= 16}}
{{$test_a = 0xFFFF}}
{{my_plugin a=1 b=2 c=$test_a d=$test_var|capitalize}}
{{$test_a|my_grep:'haha':true}}

{{hello name="ffan" id="100"}}
{{$test_a|my_grep:'haha':true}}
{{$var = 'hello '}}
{{$var|hello}}
{{$var|hello:5}}
