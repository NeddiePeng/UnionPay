<?php
/**
 * 常量配置
 */
 class AppConfig{

     //应用ID【00019453】【00024657】
	 const APPID = '00024657';
	 //商户ID【55079104816NGZB】【55079105712RJ1B】
	 const CUSID = '55079105712RJ1B';
	 //支付秘钥【allinpay】【87f62fdde7f54b36b64ddc4b8d544c8b】
	 const APPKEY = '87f62fdde7f54b36b64ddc4b8d544c8b';
	 //支付API，生产环境
     const APIURL = "https://vsp.allinpay.com/apiweb/unitorder";
     //接口版本
     const APIVERSION = '11';
     //异步通知回调地址
     const NOTIFY_URL = "http://www.1ang.cn/payment/notify_index.html";
 }
?>