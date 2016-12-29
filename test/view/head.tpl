<div class="container">
    <ul class="nav nav-pills" style="margin:20px" id="tool_nav_menu">
        <li act="main" mod="tool">
            <a href="/tool/tool/main/" title="代码调试工具">调试</a>
        </li>
        <li act="utils" mod="tool">
            <a href="/tool/tool/utils/" title="各种工具">小工具</a>
        </li>
        <li act="main" mod="demo">
            <a href="/tool/demo/main/" title="demo">Demo</a>
        </li>
        {{if isset($tool_menu)}}
            {{foreach from=$tool_menu item=rs}}
                <li>
                    <a href="/tool/tool/dev_tool/func={{$rs.func}}">{{$rs.name}}</a>
                </li>
            {{/foreach}}
        {{/if}}
    </ul>
</div>