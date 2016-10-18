<link type="text/css" rel="stylesheet" href="<?php echo base_url();?>css/kecheng.css" />
<script type="text/javascript">
    $(document).ready(function(){
        $('.addoption').live('click',function(){
            var qeul=$(this).parent().prev();
            var type=qeul.find('input.type').val();
            qeul.append('<li><span class="w50 aRight">选项'+ qeul.find('li').length +'</span> <input name="option[]" type="text" class="iptH37 w345 ml10" value=""> <a href="#" class="deloption gray9 ml10">删除</a></li>');
            resetAllQuestion();
            return false;
        });
        $('.deloption').live('click',function(){
            $(this).parent().remove();
            resetAllQuestion();
            return false;
        });
        $('.delquestion').live('click',function(){
            $(this).parent().parent().parent().remove();
            resetAllQuestion();
            return false;
        });
        $('.moveup').live('click',function(){
            $(this).parent().parent().prev().before($(this).parent().parent());
            resetAllQuestion();
            return false;
        });
        $('.movedown').live('click',function(){
            $(this).parent().parent().next().after($(this).parent().parent());
            resetAllQuestion();
            return false;
        });
        $('.copy').live('click',function(){
            $(this).parent().parent().parent().after($(this).parent().parent().parent().clone(true));
            resetAllQuestion();
            return false;
        });
        $('.addQuestion').click(function(){
            if(isEmptyInput()){return false;}
            var type=$(this).attr('rel');
            var queshtml='<div class="p20 borderBottom"><ul class="zuoyeList">' +
                '<li><span class="w50 aRight numtype"></span><input class="type" type="hidden" name="type[]" value="'+type+'"><input class="no" type="hidden" name="no[]" value="">' +
                ' <input name="question[]" type="text" class="iptH37 w345 ml10" value="">' +
                '<select name="required[]" class="iptH37 w75 ml10">' +
                '<option value="1">必答</option>' +
                '<option value="2">选答</option>' +
                '</select> <a href="#" class="copy gray9 ml20">复制</a><a href="#" class="delquestion gray9 ml10">删除</a></li>';
            if(type==1||type==2){
                queshtml+='<li><span class="w50 aRight">选项1</span> <input name="option1[]" type="text" class="iptH37 w345 ml10"></li>';
                queshtml+='<li><span class="w50 aRight">选项2</span> <input name="option2[]" type="text" class="iptH37 w345 ml10"> <a href="#" class="deloption gray9 ml10">删除</a></li>';
            }
            queshtml+='</ul><p class="f14 ml20 operational"></p></div>';
            $(this).parent().before(queshtml);
            resetAllQuestion();
            return false;
        });
        $('#save').click(function(){
            saveFormdata('保存成功');
            return false;
        });
        $('.topNaviUlKec li a').click(function () {
            return isEmptyInput();
        });
        setTimeout(autoSave,15000);
        resetAllQuestion();
    });
    function isEmptyInput(){
        var flag=true;
        $('ul.zuoyeList:last').find('label.red').remove();
        $('ul.zuoyeList:last').find('input').each(function(){
            $(this).css('border-color','#f0f0f0');
            if($(this).val()==''){
                flag=false;
                $(this).css('border-color','#f00');
            }
        });
        if(!flag){
            $('ul.zuoyeList:last').find('li:first').append('<label class="red ml10">问题或选项不能为空</label>');
            $('html,body').animate({scrollTop: $('ul.zuoyeList:last').offset().top}, 600);
        }
        return flag;
    }
    function autoSave(){
        saveFormdata('已自动保存成功');
        var autotime=setTimeout(autoSave,15000);
    }
    function saveFormdata(txt){
        $.post('<?php echo site_url('annualsurvey/saveQa/'.$qatype.'/'.$survey['id'])?>', $('#qsForm').serialize())
            .done(function(res) {
            if(res==1){
                $('.surveySaveMsg').text(txt).show().fadeOut(3000);
            }else if(res==0){
                if(txt=='保存成功'){
                    $('.surveySaveMsg').text('问卷已开始,无法修改').show().fadeOut(3000);
                }
            }
        });
    }
    function resetAllQuestion() {
        $('.zuoyeList').each(function(i){
            var type=$(this).find('input.type').val();
            $(this).find('input.no').val((i+1));
            if(type==1){
                $(this).find('.numtype').text((i+1)+'.单选题');
            }
            if(type==2){
                $(this).find('.numtype').text((i+1)+'.多选题');
            }
            if(type==3){
                $(this).find('.numtype').text((i+1)+'.开放题');
            }
            var num=$(this).find("input[name^='option']").length;
            if(num>0){
                $(this).find("input[name^='option']").each(function(o){
                    $(this).prop('name','option'+(i+1)+'[]').prev().text('选项'+(o+1));
                });
            }
            <?php if(!$isStarted){?>
            var operationalhtml='<a href="#" class="moveup blue mr20">上移</a> <a href="#" class="movedown blue mr20">下移</a>';
            if(type==1||type==2){
                operationalhtml='<a href="#" class="addoption blue mr20">+ 添加选项</a> '+operationalhtml;
            }
            $('.operational').eq(i).html(operationalhtml);
            <?php } ?>
        });
        $('.operational').first().find('.moveup').remove();
        $('.operational').last().find('.movedown').remove();
    }
</script>
<div class="wrap">
    <div class="titCom clearfix">
        <span class="titSpan"><?php echo $survey['title'] ?></span></div>

    <div class="topNaviKec01">
        <?php $this->load->view ( 'annual_survey/top_navi' ); ?>
    </div>
    <div class="comBox">
        <?php if($isStarted){?><p class="yellowTipBox mt20">年度调研问卷开始后,内容不可修改</p><?php } ?>
        <form id="qsForm" method="post" action="<?php echo site_url('annualsurvey/saveQa/'.$qatype.'/'.$survey['id'])?>">
            <?php if(count($questions)>0){
                foreach ($questions as $kq=>$q ){?>
                <div class="p20 borderBottom">
                    <ul class="zuoyeList">
                        <li>
                            <span class="w50 aRight numtype"></span><input class="type" type="hidden" name="type[]" value="<?php echo $q['type'] ?>"><input class="no" type="hidden" name="no[]" value="">
                            <input name="question[]" type="text" class="iptH37 w345 ml10" value="<?php echo $q['title'] ?>" <?php if($isStarted){?>disabled="disabled"<?php } ?>>
                            <select name="required[]" class="iptH37 w75 ml10" <?php if($isStarted){?>disabled="disabled"<?php } ?>>
                                <option value="1" checked >必答</option>
                                <option value="2" <?php if($q['required']==2){echo 'checked';} ?>>选答</option>
                            </select>
                            <?php if(!$isStarted){?>
                            <a href="#" class="copy gray9 ml20">复制</a><a href="#" class="delquestion gray9 ml10">删除</a><?php } ?>
                        </li>
                        <?php if($q['type']==1||$q['type']==2) {
                            foreach ($q['options'] as $ko=>$op){?>
                                <li><span class="w50 aRight">选项<?php echo $ko+1 ?></span>
                                    <input name="option<?php echo ($kq+1) ?>[]" type="text" class="iptH37 w345 ml10" value="<?php echo $op['content'] ?>"<?php if($isStarted){?>disabled="disabled"<?php } ?> ><?php if($ko>0){?> <?php if(!$isStarted){?><a href="#" class="deloption gray9 ml10">删除</a><?php } } ?>
                                </li>
                            <?php }
                        } ?>
                    </ul>
                    <?php if(!$isStarted){?>
                    <p class="f14 ml20 operational">
                        <?php if($q['type']==1||$q['type']==2) {?>
                            <a href="#" class="addoption blue mr20">+ 添加选项</a><?php } ?>
                        <a href="#" class="movedown blue mr20">下移</a>
                    </p>
                    <?php } ?>
                </div>
            <?php }
            }else{ ?>
            <div class="p20 borderBottom">
                <ul class="zuoyeList">
                    <li><span class="w50 aRight numtype">1.单选题</span><input class="type" type="hidden" name="type[]" value="1"><input class="no" type="hidden" name="no[]" value="1">
                        <input name="question[]" type="text" class="iptH37 w345 ml10" value="">
                        <select name="required[]" class="iptH37 w75 ml10">
                            <option value="1">必答</option>
                            <option value="2">选答</option>
                        </select>
                        <a href="#" class="copy gray9 ml20">复制</a><a href="#" class="delquestion gray9 ml10">删除</a>
                    </li>
                    <li><span class="w50 aRight">选项1</span>
                        <input name="option1[]" type="text" class="iptH37 w345 ml10" value="">
                    </li>
                    <li><span class="w50 aRight">选项2</span>
                        <input name="option1[]" type="text" class="iptH37 w345 ml10" value="">
                        <a href="#" class="deloption gray9 ml10">删除</a>
                    </li>
                    <li><span class="w50 aRight">选项3</span>
                        <input name="option1[]" type="text" class="iptH37 w345 ml10" value="">
                        <a href="#" class="deloption gray9 ml10">删除</a>
                    </li>
                    <li><span class="w50 aRight">选项4</span>
                        <input name="option1[]" type="text" class="iptH37 w345 ml10" value="">
                        <a href="#" class="deloption gray9 ml10">删除</a>
                    </li>
                </ul>
                <p class="f14 ml20 operational">
                    <a href="#" class="addoption blue mr20">+ 添加选项</a>
                    <a href="#" class="movedown blue mr20">下移</a>
                </p>
            </div>
            <?php } ?>
            <?php if(!$isStarted){?>
            <p class="f14 p20">
                <a href="#" rel="1" class="addQuestion blue mr10">添加单选题</a>
                <a href="#" rel="2" class="addQuestion blue mr10">添加多选题</a>
                <a href="#" rel="3" class="addQuestion blue">添加开放题</a>
            </p>
            <p class="aCenter p40"><input id="save" type="button" class="coBtn" value="保存"></p>
            <?php } ?>
        </form>
    </div>
</div>
<div class="surveySaveMsg">保存成功</div>