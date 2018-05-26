<?php
	header("Content-type: text/html; charset=gb2312");
	require_once 'config/AppConfig.php';
	require_once 'config/AppUtil.php';
	$params = array();
	//动态遍历获取所有收到的参数,此步非常关键,因为收银宝以后可能会加字段,
	//动态获取可以兼容由于收银宝加字段而引起的签名异常
	foreach($_POST as $key => $val) {
		$params[$key] = $val;
	}
	//如果参数为空,则不进行处理
	if(count($params) < 1){
		echo "error";
		exit();
	}
	//验签成功
	if(AppUtil::ValidSign($params, AppConfig::APPKEY)){
		//此处进行业务逻辑处理
		echo "success";
	} else{
		echo "erro";
	}

?>  
