<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>培训派</title>
		<link rel="icon" href="favicon.ico" type="image/x-icon" />
        <link type="text/css" rel="stylesheet" href="<?php echo base_url();?>css/jquery-ui.css" />
        <link type="text/css" rel="stylesheet" href="<?php echo base_url();?>css/common.css?<?php echo $this->config->item('version');?>" />
		<link type="text/css" rel="stylesheet" href="<?php echo base_url();?>css/jquery.simple-dtpicker.css" />
		<link type="text/css" rel="stylesheet" href="<?php echo base_url();?>css/font-awesome.min.css" />

		<script type="text/javascript" src="<?php echo base_url();?>js/jquery1.83.js"></script>
		<script type="text/javascript" src="<?php echo base_url();?>js/jquery.validate.min.1.8.0.1.js"></script>
		<script type="text/javascript" src="<?php echo base_url();?>js/additional-methods.min.js"></script>
        <script type="text/javascript" src="<?php echo base_url();?>js/jquery.simple-dtpicker.js"></script>
        <script type="text/javascript" src="<?php echo base_url();?>js/jquery.placeholder.min.js"></script>
        <script type="text/javascript"  src="<?php echo base_url() ?>js/trainingpie.common.js"></script>
<script type="text/javascript">
//var _hmt = _hmt || [];
//(function() {
//  var hm = document.createElement("script");
//  hm.src = "//hm.baidu.com/hm.js?9432a72cc245c2b9cafed658f471d489";
//  var s = document.getElementsByTagName("script")[0];
//  s.parentNode.insertBefore(hm, s);
//})();
    var src =window.location+'';
    $(window.parent.document).find('.nav-list li a').each(function(i){
        var href=$(this).attr('href');
        if(src.indexOf(href)!=-1){
            $(window.parent.document).find('.nav-list li a').removeClass('on');
            $(window.parent.document).find('.nav-list li').removeClass('on');
            $(this).addClass('on').parent().addClass('on');
        }
    });
</script>

	</head>

	<body>