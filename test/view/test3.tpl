{{$test_var|capitalize:1:2:'aa':'bb':'ccc'}}
{{++$test_a}}
{{$test_a}}
{{$test_a *= 100}}
{{$test_a}}
{{$test_a += 100}}
{{$test_a}}
{{$test_a >> 2}}
{{$test_a %= 16}}
{{$test_a = 0xFFFF}}
{{my_plugin a=1 b=2 c=$test_a d=$test_var|capitalize}}
{{$test_a|my_grep:'haha':true}}
