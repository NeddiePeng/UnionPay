<?php
/**
 * 通联支付【微信】.
 * User: Pengfan
 * Date: 2018/5/21
 * Time: 13:50
 */
header("Content-type:text/html;charset=utf-8");
require_once 'config/AppConfig.php';
require_once 'config/AppUtil.php';
require_once 'phpqrcode/phpqrcode.php';
class AggregatePay
{


    /**
     * 构造函数
     */
    public function __construct()
    {

    }



    /**
     * 生成支付二维码
     * @param     array    $data    支付参数
     * @return    array
     */
    public function Code($data){
        if(!$data){
            return ['code' => -1,'msg' => "二维码地址为空，请重新请求支付"];
        }
        //二维码内容
        $value = $data;
        //容错级别
        $errorCorrectionLevel = 'L';
        //生成图片大小
        $matrixPointSize = 6;
        //生成二维码图片
        \QRcode::png($value, 'qrcode.png', $errorCorrectionLevel, $matrixPointSize, 2);
        //准备好的logo图片
        $logo = 'http://www.1ang.cn/images/bg.png';
        //已经生成的原始二维码图
        $QR = 'qrcode.png';
        if ($logo !== FALSE) {
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            //二维码图片宽度
            $QR_width = imagesx($QR);
            //二维码图片高度
            $QR_height = imagesy($QR);
            //logo图片宽度
            $logo_width = imagesx($logo);
            //logo图片高度
            $logo_height = imagesy($logo);
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        imagepng($QR, 'pay.png');
        return [
            'code' => 1,
            'msg' => "支付二维码生成成功",
            'url' => "http://".$_SERVER['HTTP_HOST']."/pay.png"
        ];
    }




    /**
     * 统一支付
     * @param    array     $data    支付参数
     * @return   array
     */
    public function WeChat_pay($data){
        $common_obj = new AppUtil();
        $order_id = $common_obj->Order_id();
        $pay_params = $data;
        if(empty($pay_params)){
            return ['code' => -1,'msg' => "支付参数错误",'data' => new stdClass()];
        }
        if($pay_params['pay_method'] == 'WeChat'){
            $paytype = "W01";
        }else{
            $paytype = 'A01';
        }
        //支付秘钥
        $app_key = AppConfig::APPKEY;
        $params = [];
        $params['cusid'] = AppConfig::CUSID;
        $params['appid'] = AppConfig::APPID;
        $params["version"] = AppConfig::APIVERSION;
        //交易金额
        $params['trxamt'] = $pay_params['amount_money'];
        //商户交易订单号
        $params['reqsn'] = $order_id;
        //支付方式
        $params['paytype'] = $paytype;
        $params['randomstr'] = $order_id;
        $params['body'] = $pay_params['body'];
        $params['remark'] = isset($pay_params['remark']) ? $pay_params['remark'] : "备注";
        $params['validtime'] = 30;
        //异步通知回调地址
        $params['notify_url'] = AppConfig::NOTIFY_URL;
        $params['limit_pay'] = "no_credit";
        //签名
        $params['sign'] = AppUtil::SignArray($params,$app_key);
        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL.'/pay';
        $res = $common_obj->request($url, $paramsStr);
        $resArray = json_decode($res,true);
        $success_verify = $common_obj->Success_validSign($resArray);
        if(is_bool($success_verify) && $success_verify){
            return $common_obj->Return_result(1,"验签成功",$resArray['payinfo']);
        }elseif(is_bool($success_verify) && !$success_verify){
            return $common_obj->Return_result(-1,"验签失败");
        }else{
            return $common_obj->Return_result(-1,$success_verify);
        }
    }





    /**
     * 交易撤销【及时退款，全额】
     * 交易退款【部分金额退款，隔天交易退款】
     * @param    array   $data   交易订单数据
     * @return   array
     */
    public function payCancel($data){
        $app_key = AppConfig::APPKEY;
        $common_obj = new AppUtil();
        $order_id = $common_obj->Order_id();
        $params = [];
        $params['cusid'] = AppConfig::CUSID;
        $params['appid'] = AppConfig::APPID;
        $params['version'] = AppConfig::APIVERSION;
        //订单号
        $params['reqsn'] = $order_id;
        //原订单交易金额
        $params['trxamt'] = $data['amount_money'];
        //原来的订单号码
        $params['oldreqsn'] = $data['old_order_id'];
        $params['randomstr'] = $common_obj->rend_str(6);
        $params['sign'] = AppUtil::SignArray($params,$app_key);
        $paramsStr = AppUtil::ToUrlParams($params);
        $dir = "/cancel";
        if($data['refund_type'] != "cancel"){
            $dir = "/refund";
        }
        $url = AppConfig::APIURL . $dir;
        $rsp = $common_obj->request($url, $paramsStr);
        $resArray = json_decode($rsp,true);
        $success_verify = $common_obj->Success_validSign($resArray);
        if(is_bool($success_verify) && $success_verify){
            return $common_obj->Return_result(1,"退款成功");
        }elseif(is_bool($success_verify) && !$success_verify){
            return $common_obj->Return_result(-1,"退款失败");
        }else{
            return $common_obj->Return_result(-1,$success_verify);
        }

    }





    /**
     * 交易查询
     * @param    string    $old_order_id   订单号
     * @return   array
     */
    public function payQuery($old_order_id){
        $common_obj = new AppUtil();
        $params = [];
        $params["cusid"] = AppConfig::CUSID;
        $params["appid"] = AppConfig::APPID;
        $params["version"] = AppConfig::APIVERSION;
        $params["reqsn"] = $old_order_id;
        $params["randomstr"] = $common_obj->rend_str(6);
        $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);
        $paramsStr = AppUtil::ToUrlParams($params);
        $url = AppConfig::APIURL . "/query";
        $rsp = $common_obj->request($url, $paramsStr);
        $resArray = json_decode($rsp,true);
        $success_verify = $common_obj->Success_validSign($resArray);
        if(is_bool($success_verify) && $success_verify){
            return $common_obj->Return_result(1,"查询成功");
        }elseif(is_bool($success_verify) && !$success_verify){
            return $common_obj->Return_result(-1,"查询失败");
        }else{
            return $common_obj->Return_result(-1,$success_verify);
        }
    }







    /**
     * 异步回调通知
     * @param     array     $data    通联支付的回调参数
     * @return    boolean
     */
    public function notify($data){
        $params = array();
        foreach($data as $key => $val) {
            $params[$key] = $val;
        }
        if(count($params) < 1){
            return false;
        }
        if(AppUtil::ValidSign($params, AppConfig::APPKEY)){
            return true;
        } else{
            return false;
        }
    }


}