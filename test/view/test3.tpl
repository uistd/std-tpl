{{$test_var|capitalize:1:2:'aa':'bb':'ccc'}}
{{++$test_a}}
{{$test_a}}
{{$test_a *= 100}}
{{$test_a}}
{{$test_a += 100}}
{{$test_a}}
{{$test_a >> 2}}
{{$test_a %= 16}}
{{$test_a}}
{{TplGrep::capitalize(substr('aaaa', 3))}}
