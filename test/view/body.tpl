{{newe_head tpl="tool_head" title="Demo-form"}}
<div class="container" style="width:1000px">
    <form class="form-signin" id="demo_form" onsubmit="return demo_post_data()">
        <div class="col-md-6">
            <label>用户名：(测试功能, 带 a 字母的用户名都被使用)</label>

            <div class="relative tc">
                <input type="text" name="name" class="form-control" data-require="1" data-len="5-20"
                       data-len-msg="字符串长度为5-20" data-format="[a-zA-Z0-9]+" data-format-msg="只能由英文字母和数字组成"
                       data-server-check="/tool/demo/form_name_check">
                <span class="newe_input_tip_3 hide" id="name_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>Email：</label>

            <div class="relative tc">
                <input type="email" name="email" class="form-control" data-require="1">
                <span class="newe_input_tip_3 hide" id="email_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>密码：</label>

            <div class="relative tc">
                <input type="password" name="password" class="form-control" data-require="1" data-len="5"
                       data-format="func::password_check" data-format-msg="必须有大写,小写和数字" data-len-msg="长度必须大于5位">
                <span class="newe_input_tip_3 hide" id="password_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>确认密码：</label>

            <div class="relative tc">
                <!--same:passowrd 表示内容必须和password字段内容一样-->
                <input type="password" name="repassword" class="form-control" data-require="1"
                       data-format="same::password" data-format-msg="两次输入的密码不一样">
                <span class="newe_input_tip_3 hide" id="repassword_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>QQ：</label>

            <div class="relative tc">
                <input type="text" name="qq" class="form-control" data-require="weixin" data-type="qq"
                       data-require-msg="QQ号和微信至少要填写一个">
                <span class="newe_input_tip_3 hide" id="qq_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>微信：</label>

            <div class="relative tc">
                <input type="text" name="weixin" class="form-control">
                <span></span>
            </div>
            <label>手机号：</label>

            <div class="relative tc">
                <input type="text" name="mobile" data-type="mobile" class="form-control" data-require="tel,yy">
                <span class="newe_input_tip_3 hide" id="mobile_tip"></span>
            </div>
            <label>电话号码：</label>

            <div class="relative tc">
                <input type="text" name="tel" data-type="tel" class="form-control">
                <span class="newe_input_tip_3 hide" id="tel_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>YY语音：</label>

            <div class="relative tc">
                <input type="text" name="yy" class="form-control">
                <span class="newe_input_tip_3 hide" id="yy_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>喜欢颜色：</label>

            <div class="relative tc">
                <input type="text" name="favor_color" class="form-control" data-type="color" data-require="1">
                <span class="newe_input_tip_3 hide" id="favor_color_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
        </div>
        <div class="col-md-6">
            <label>身高：</label>

            <div class="relative tc">
                <input type="text" name="height" class="form-control" data-require="1" data-type="range" data-min="50"
                       data-max="260" data-format-msg="身高必须是数字并且介于50-240之间">
                <span class="newe_input_tip_3 hide" id="height_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>体重：</label>

            <div class="relative tc">
                <input type="text" name="weight" class="form-control" data-type="float">
                <span class="newe_input_tip_3 hide" id="weight_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>出生日期：</label>

            <div class="relative tc">
                <input type="text" name="birthday" class="laydate-icon form-control" data-type="date"
                       onclick="laydate()">
                <span class="newe_input_tip_3 hide" id="birthday_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>时间：</label>

            <div class="relative tc">
                <input type="text" name="demo_time" class="laydate-icon form-control" data-type="datetime"
                       onclick="laydate({format:'YYYY-MM-DD hh:mm:ss', istime:true,auto_init:true})">
            </div>
            <label>爱好：</label>

            <div>
                <label><input type="checkbox" value="1" name="hobby[]">乒乓球</label>
                <label><input type="checkbox" value="2" name="hobby[]">羽毛球</label>
                <label><input type="checkbox" value="3" name="hobby[]">PaPaPa</label>
                <label><input type="checkbox" value="4" name="hobby[]">Luguan</label>
                <label><input type="checkbox" value="5" name="hobby[]">岛国动作片</label>
                <label><input type="checkbox" value="6" name="hobby[]">Dota</label>
            </div>
            <label>性别：</label>

            <div>
                <label><input type="radio" value="1" name="gendar">宅男</label>
                <label><input type="radio" value="2" name="gendar">腐女</label>
                <label><input type="radio" value="3" name="gendar">人妖</label>
            </div>
            <label>学历：</label>

            <div class="relative tc">
                <select name="education" class="form-control" data-require="1" data-select-default="0"
                        data-require-msg="请选择你的学历">
                    <option value="0">请选择学历</option>
                    >
                    <option value="1">初中</option>
                    >
                    <option value="2">高中</option>
                    >
                    <option value="3">专科</option>
                    >
                    <option value="4">本科</option>
                    >
                    <option value="5">研究生</option>
                    >
                    <option value="6">博士</option>
                    >
                </select>
                <span class="newe_input_tip_3 hide" id="education_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <label>网址：</label>

            <div class="relative tc">
                <input type="text" data-type="url" name="url" class="form-control" data-len="0-20"
                       data-len-msg="网址长度不能超过20">
                <span class="newe_input_tip_3 hide" id="url_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <div class="relative">
                <label>
                    <input type="checkbox" value="1" data-require="1" name="agree" data-require-msg="必须同意协议">我已经阅读并且同意注册条款
                </label>
                <span class="newe_input_tip_3 hide" id="agree_tip">
                    <b class="newe_validate_icon"></b>
                    <a href="javascript:;">X</a>
                    <span class="newe_validate_arrow">
                        <i></i>
                        <em></em>
                    </span>
                </span>
            </div>
            <button class="btn btn-lg btn-primary btn-block" type="submit">提 交</button>
        </div>
    </form>
</div>
{{newe_foot js="newetool/demo_form" tpl="tool_footer"}}