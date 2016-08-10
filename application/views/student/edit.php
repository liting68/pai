<link type="text/css" rel="stylesheet" href="<?php echo base_url();?>css/texture.css" />
<script type="text/javascript">
$(document).ready(function(){
    $('.closeBtn').click(function(){$('#conWindow').hide();});
    $('select[name=department_parent_id]').change(function(){
        var departmentid=$(this).val();
        $.ajax({
                type:"post",
                url:'<?php echo site_url('department/ajaxDepartmentAndStudent') ?>',
                data:{'departmentid':departmentid},
                datatype:'jsonp',
                success:function(res){
                        var json_obj = $.parseJSON(res);
                        var count=0;
                        var str='<option value="'+departmentid+'">请选择</option>';
                        $.each(json_obj.departs,function(i,item){
                            str+='<option value="'+item.id+'">'+item.name+'</option>';
                            ++count;
                        });
                        $('select[name=department_id]').html(str);
                }
        });
    });
    $('#addDeart').click(function(){
        $('#conMessage input[name=departid]').val('');
        $('#conMessage input[name=departname]').val('');
        $('#title_divSpan').text('增加一级部门');
        $('#conWindow').show();});
    $('a.okBtn').click(function(){
        act=$('#conMessage input[name=act]').val();
        departid=$('#conMessage input[name=departid]').val();
        departname=$('#conMessage input[name=departname]').val();
        $.ajax({
                type:"post",
                url:'<?php echo site_url('department/add') ?>',
                data:{'parentid':departid,'departname':departname},
                success:function(res){
                        if(res==0){
                            alert('添加失败');
                        }else{
                            id=res;
                            $('.textureSide').append('<div class="fnavi"><a href="<?php echo base_url() ?>department/index/'+res+'.html" class="flink"><i class="iup"></i>'+departname+'</a><ul class="clink departChildren'+res+'"></ul></div>');
                            $('#conWindow').hide();
                        }
                }
        })
        return false;
    });
    jQuery.validator.addMethod("isMobile", function(value, element) { 
        var length = value.length; 
        var mobile = /^((1[0-9]{2})+\d{8})$/; 
        return this.optional(element) || (length == 11 && mobile.test(value)); 
    }, "请正确填写您的手机号码");
    $("input[name=role]").change(function(){
        if($("input[name=role]:checked").val()!=1){
            $('input[name=user_name]').removeAttr('readonly').css('color','#666');
        }else{
            $('input[name=user_name]').val($('input[name=mobile]').val()).attr('readonly','readonly').css('color','#ccc');
        }
            
    });
    $('input[name=mobile]').keyup(function(){
        //if($("input[name=role]:checked").val()==1){
            $('input[name=user_name]').val($('input[name=mobile]').val()).attr('readonly','readonly');
        //}
    });
    $( "#editForm" ).validate( {
        rules: {
                name: {
                        required: true
                },
                job_code: {
                        required: true
                },
                job_name: {
                        required: true
                },
                mobile: {
                        required: true,
                        digits:true,
                        isMobile: true
                },
                email: {
                        required: true,
                        email: true
                },
                user_name: {
                        required: true
                },
                user_pass: {
                        required: true
                }
        },
        messages: {
                name: {
                        required: "请输入学员姓名"
                },
                job_code: {
                        required: "请输入学员工号"
                },
                job_name: {
                        required: "请输入职位名称"
                },
                mobile: {
                        required: "请输入您的电话号码",
                        digits: "只能输入数字",
                        isMobile: "请输入正确的手机号码",
                },
                email: {
                        required: "请输入您的邮箱地址",
                        email: "请输入正确的邮箱地址",
                },
                user_name: {
                        required: "请输入登录账号"
                },
                user_pass: {
                        required: "请输入登录密码"
                }
        },
        errorPlacement: function ( error, element ) {
                error.addClass( "ui red pointing label transition" );
                error.insertAfter( element.parent() );
        },
        highlight: function ( element, errorClass, validClass ) {
                $( element ).parents( ".row" ).addClass( errorClass );
        },
        unhighlight: function (element, errorClass, validClass) {
                $( element ).parents( ".row" ).removeClass( errorClass );
        },
        submitHandler:function(form){
            $('input[type=submit]').val('请稍后..').attr('disabled','disabled');
            form.submit();
        }
    });
});
</script>
<div class="wrap clearfix">
    <div class="textureSide">
        <a id="addDeart" href="javascript:void(0)" class="topbtn">新增一级部门</a>
        <div class="fnavi">
            <a class="flink mb10 <?php echo empty($current_department['id'])?'on':'' ?>" href="<?php echo site_url('department/index') ?>">所有学员</a>
        </div>
            <?php foreach ($departments as $d){ ?>
                <div class="fnavi">
                        <a href="<?php echo site_url('department/index/'.$d['id']) ?>" class="flink <?php echo $current_department['id']==$d['id']?'on':'' ?>"><i class="iup"></i><?php echo $d['name'] ?></a>
                        <ul class="clink departChildren<?php echo $d['id'] ?>">
                                <?php if(!empty($d['departs'])){
                                    foreach ($d['departs'] as $dp){ ?>
                                <li class="<?php echo $current_department['id']==$dp['id']?'on':'' ?>"><a href="<?php echo site_url('department/index/'.$dp['id']) ?>"><?php echo $dp['name'] ?></a></li>
                                <?php }
                                } ?>
                        </ul>
                </div>
            <?php } ?>
        <a href="<?php echo site_url('ability/index') ?>" class="toporangebtn mt20">能力评估管理</a>

    </div>
    <div class="textureCont">
            <input type="hidden" id="current_department_id" value="<?php echo $current_department['id'] ?>" />
            <input type="hidden" id="current_department_name" value="<?php echo $current_department['name'] ?>" />
            <div class="texturetip clearfix"><span class="fLeft"><?php echo empty($student)?'增加':'编辑' ?>学员<?php echo !empty($current_department['name'])?'('.$current_department['name'].')':'' ?></span>
                    <div class="fRight"><a href="<?php echo site_url('department/index/'.$current_department['id']) ?>" class="borBlueBtnH28">返回<?php echo $current_department['name'] ?></a></div>
            </div>

            <div class="p15">
                    <div class="tiph3">基本信息</div>
                    <form id="editForm" method="post" action=""  enctype="multipart/form-data">
                    <input name="act" type="hidden" value="act" />
                    <p class="red"><?php echo $msg ?></p>
                    <table cellspacing="0" class="comTable">
                            <colgroup><col width="100">
                            </colgroup><tbody><tr>
                                    <th><span class="red">*</span>学员姓名</th>
                                    <td>
                                        <input name="name" value="<?php echo $student['name']?>" type="text" class="iptH37">
                                            <ul class="lineUl">
                                                    <li>
                                                        <label><input name="sex" value="1" type="radio" checked="">男</label></li>
                                                    <li>
                                                        <label><input name="sex" value="2" type="radio" <?php if($student['sex']==2){ echo 'checked'; } ?>>女</label></li>
                                            </ul>

                                    </td>
                            </tr>
                            <tr>
                                    <th><span class="red">*</span>学员工号</th>
                                    <td>
                                        <input name="job_code" value="<?php echo $student['job_code'] ?>" type="text" class="iptH37 w250">

                                    </td>
                            </tr>
                            <tr>
                                    <th><span class="red">*</span>职位名称</th>
                                    <td>
                                        <input name="job_name" value="<?php echo $student['job_name'] ?>" type="text" class="iptH37 w250">


                                    </td>
                            </tr>
                            <tr>
                                    <th>所在部门</th>
                                    <td>
                                        <select name="department_parent_id" class="iptH37">
                                            <option value="">请选择</option>
                                        <?php foreach($departments as $d){ ?>
                                            <option <?php if(!empty($student['department_parent_id'])&&$d['id']==$student['department_parent_id'] || empty($student['department_parent_id'])&&($d['id']==$current_department['id']||$d['id']==$current_parent_department['id'])){ ?>selected=""<?php } ?> value="<?php echo $d['id'] ?>"><?php echo $d['name'] ?></option>
                                        <?php } ?>
                                        </select>
                                        <select name="department_id" class="iptH37">
                                            <option value="<?php echo $student['department_parent_id'] ?>">请选择</option>
                                        <?php foreach($second_departments as $d){ ?>
                                            <option <?php if(!empty($student['department_parent_id'])&&$d['id']==$student['department_id'] || empty($student['department_parent_id'])&&$d['id']==$current_department['id']){ ?>selected=""<?php } ?> value="<?php echo $d['id'] ?>"><?php echo $d['name'] ?></option>
                                        <?php } ?>
                                        </select>
                                    </td>
                            </tr>
                            <tr>
                                    <th><span class="red">*</span>手机号码</th>

                                    <td>
                                        <input name="mobile" value="<?php echo $student['mobile'] ?>" type="text" class="iptH37 w250">

                                    </td>
                            </tr><tr>
                                    <th><span class="red">*</span>电子邮件</th>

                                    <td>
                                        <input name="email" value="<?php echo $student['email'] ?>" type="text" class="iptH37 w250">

                                    </td>
                            </tr>

                    </tbody></table>

                    <div class="tiph3">账号信息</div>
                    <table cellspacing="0" class="comTable">
                            <colgroup><col width="100">
                            </colgroup><tbody><tr>
                                    <th><span class="red">*</span>登录账号</th>
                                    <td>
                                        <input type="text" name="user_name" value="" disabled style="display: none;"><input type="password" name="user_pass" value="" disabled style="display: none;">
                                        <input style="color:#ccc" name="user_name" value="<?php echo $student['user_name'] ?>" type="text" class="iptH37 w250" readonly >


                                    </td>
                            </tr>
                            <tr>
                                    <th><span class="red">*</span>登录密码</th>
                                    <td>
                                        <input name="user_pass" value="<?php echo $student['user_pass'] ?>" type="password" class="iptH37 w250" autocomplete="off" <?php if($student['role']==9){ echo 'style="color:#ccc" readonly'; }?> >

                                    </td>
                            </tr>
                            <tr>
                                    <th></th>
                                    <td>
                                        <?php if($student['role']==9){?>
                                            <label><input name="role" value="9" type="hidden" />系统管理员</label>
                                        <?php }else{?>
                                        <label><input name="role" value="1" type="radio" checked class="mr10" />普通学员</label>
                                        <label><input name="role" value="2" type="radio" <?php if($student['role']==2){echo 'checked';} ?> class="mr10" />助理管理员<span class="gray9 f14">(公司培训负责人)</span> </label>
                                        <label><input name="role" value="3" type="radio" <?php if($student['role']==3){echo 'checked';} ?> class="mr10" />员工经理<span class="gray9 f14">(部门负责人、部门经理)</span> </label>
                                        <?php } ?>
                                    </td>
                            </tr>

                            <tr>
                                    <th></th>
                                    <td>

                                            <input type="submit" value="保存" class="coBtn">
                                    </td>
                            </tr>
                    </tbody></table>
                    </form>
            </div>

    </div>
</div>

<!--tankuang de yangshi -->
<div id="conWindow" style="z-index: 99999; display: none;" class="popWinBox">
        <div class="pop_div" style="z-index: 100001;">
                <div class="title_div"><a class="closeBtn" href="javascript:;">X</a><span id="title_divSpan" class="title_divText">增加一级部门</span> </div>
                <div id="conMessage" class="pop_txt01">
                        <table class="comTable">
                                <col width="150" />
                                <tr>
                                        <th>部门名称</th>
                                        <td class="aLeft">
                                            <input name="act" value="add" type="hidden" >
                                            <input name="departid" type="hidden" >
                                            <input name="departname" type="text" class="ipt w250"></td>
                                </tr><tr>
                                        <th></th>
                                        <td class="aLeft"><a jsbtn="okBtn" href="javascript:;" class="okBtn">保存设置</a></td>
                                </tr>
                        </table>


                </div>

        </div>
        <div class="popmap" style="z-index: 100000;"></div>
</div>
