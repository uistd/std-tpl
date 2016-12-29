<div class="footer newe_homepage_foot tc" id="footer">
    <div class="pb35" style="background:#343434;">
        <!-- <div class="main_in main_box newe_foot_msg">
            <div class="pt35 pb35 newe_foot_mid">
                <span>没有想要的资源？</span>
                <input type="text" placeholder="留下资源名称，8小时反馈">
                <input type="text" placeholder="留下你的QQ/手机号码" class="newe_foot_phone">
                <a href="javascript:;" class="btn ml15">提交</a>
            </div>
        </div> -->
    </div>
    <div class="footer_in">
        <div class="pb40">
            <ul>
                <li><a href="/main/newe/provin" target="_blank">服务协议</a></li>
                <li class="line">|</li>
                <li><a href="/main/newe/desclaimer" target="_blank">免责声明</a></li>
                <li class="line">|</li>
                <li><a href="/main/newe/about" target="_blank">加入牛微</a></li>
                <li class="line">|</li>
                <li class="last"><a href="/main/newe/contact_us" target="_blank">联系牛微</a></li>
            </ul>
            <div>
                <span class="mr10">版权所有 © 上海牛微文化传媒有限公司</span>
                <span>2014-2015.</span>
            </div>
            <div class="pt5">
                <span class="mr10">沪ICP证：130126号.</span>
                <span class="mr10">沪ICP备：09047853号.</span>
                <span>沪公网安备：11010502023471号.</span>
                <script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan id='cnzz_stat_icon_1258232259' class='tool_chinaz'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s11.cnzz.com/z_stat.php%3Fid%3D1258232259' type='text/javascript'%3E%3C/script%3E"));</script>
            </div>
            <div class="pt20">
                <img src="{{$_STATIC_}}main/images/copyright_bot.png">
            </div>
        </div>
    </div>
</div>
<div class="float_menu">
    <div class="relative">
        <a href="http://wpa.qq.com/msgrd?v=3&uin=2880304880&site=qq&menu=yes" target="_blank" class="qq">
            <div class="float_menu_box">
                <img src="{{$_STATIC_}}main/images/qq.png">
                <p>咨询</p>
            </div>
        </a>
        <a href="javascript:;" class="phone_call" id="phone_call">
            <div class="float_menu_box" style="position: relative; overflow: hidden;">
                <img src="{{$_STATIC_}}main/images/phone_call.png" class="fl ml5">
                <p>联系电话</p>
                <span class="float_phone_num">400-858-3335</span>
            </div>
        </a>
        <a href="javascript:;" class="gf_ewm" id="gf_ewm">
            <div class="float_menu_box">
                <img src="{{$_STATIC_}}main/images/view_ewm.png">
                <p>官方微信</p>
            </div>
        </a>
        <a href="javascript:;" class="to_top" id="to_top">
            <div class="float_menu_box">
                <img src="{{$_STATIC_}}main/images/to_top.png">
                <p>返回顶部</p>
            </div>
        </a>
        <div class="view_ewm" id="view_ewm">
            <img src="{{$_STATIC_}}main/images/f_code.jpg">
            <p>扫一扫</p>
            <p>关注牛微官方微信</p>
        </div>
    </div>
</div>

<script>
    /**
     * 这里是浏览器高度变化时 footer 的定位
     */
    function footer_fn()
    {
        var footer = document.getElementById( 'footer' );
        var window_h = window.innerHeight || document.documentElement.clientHeight;
        var footer_h = footer.offsetHeight;
        var html_h = document.getElementsByTagName( 'body' )[ 0 ].offsetHeight;
        var class_name = footer.className;
        var class_str = 'footer_bot';
        var flag = class_name.indexOf( class_str ) >= 0;
        if ( flag && ( html_h + footer_h > window_h ) ) {
            footer.className = class_name.replace( class_str, '' );
        } else if ( !flag && ( html_h < window_h ) ) {
            footer.className += ' ' + class_str;
        }
    }
    footer_fn();
    window.onresize = footer_fn;
</script>