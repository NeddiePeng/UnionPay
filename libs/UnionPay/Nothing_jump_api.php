<?php
/**
 * 无跳转token支付APi.
 * User: Pengfan
 * Date: 2018/5/22
 * Time: 17:48
 */
header ('Content-type:text/html;charset=utf-8' );
include_once 'sdk/acp_service.php';
include_once 'Utils.php';
use com\unionpay\acp\sdk\SDKConfig;
use com\unionpay\acp\sdk\AcpService;
class Nothing_jump_api
{

    //版本号
    private $version;
    //签名方式
    private $signMethod;
    //编码方式
    private $encoding = "utf-8";
    //业务类型【000902：token支付】
    private $bizType = "000902";
    //接入类型
    private $accessType = "0";
    //渠道类型【07-PC，08-移动】
    private $channelType;
    //交易币种
    private $currencyCode = '156';
    //验签证书序列号
    private $encryptCertId;
    //前台通知地址
    private $frontUrl;
    //后台通知地址
    private $backUrl;
    //证件类型，01-身份证
    private $certifTp = "01";


    /******************以下需要填写配置**************/

    //商户号
    private $merId = "821610157127520";




    /**
     * 构造函数
     * @param    string    $channelType    渠道类型
     */
    public function __construct($channelType)
    {
        $this->version = SDKConfig::getSDKConfig()->version;
        $this->signMethod = SDKConfig::getSDKConfig()->signMethod;
        $this->channelType = $channelType;
        $this->encryptCertId =  AcpService::getEncryptCertId();
        $this->frontUrl = SDKConfig::getSDKConfig()->frontUrl;
        $this->backUrl = SDKConfig::getSDKConfig()->backUrl;
    }



    /**
     * 银联侧开通  1-开通
     * @param     array     $data    支付参数
     * @return    string
     */
    public function frontOPen($data){
        $utils = new Utils();
        //订单号
        $order_id = $utils->orderId();
        //卡号
        $accNo = $data['account_number'];
        $customerInfo = [
            'phoneNo' => $data['mobile'],
            'certifTp' => $this->certifTp,
            'certifId' => $data['id_card'],
            'customerNm' => $data['person_name'],
            //cvn2
            //'cvn2' => '248',
            //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
            //'expired' => '1912',
        ];
        //加密卡号
        $encryptAccNo = AcpService::encryptData($accNo);
        //持卡人身份信息
        $encryptCustomerInfo = AcpService::getCustomerInfoWithEncrypt($customerInfo);
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "79",
            'txnSubType' => "00",
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'encryptCertId' => $this->encryptCertId,
            'frontUrl' => $this->frontUrl,
            'backUrl' => $this->backUrl,
            'merId' => $this->merId,
            'orderId' => $order_id,
            'txnTime' => date("YmdHis"),
            'tokenPayData' => "{trId=62000000001&tokenType=01}",
            'accNo' => $encryptAccNo,
            'customerInfo' => $encryptCustomerInfo,
            'payTimeout' => date('YmdHis', strtotime('+15 minutes'))
        ];
        //参数签名
        AcpService::sign ( $params );
        //交易请求地址
        $uri = SDKConfig::getSDKConfig()->frontTransUrl;
        $html_form = AcpService::createAutoFormHtml( $params, $uri );
        return $html_form;
    }






    /**
     * 商户侧开通 1-获取开通验证码
     * @param     array    $data    参数集合
     * @return   array
     */
    public function smsOpen($data){
        $utils = new Utils();
        $order_id = $utils->orderId();
        //银行卡号
        $accNo = $data['account_number'];
        $customerInfo = [
            'phoneNo' => $data['mobile']
        ];
        //加密卡号
        $encryptAccNo = AcpService::encryptData($accNo);
        //持卡人身份信息
        $encryptCustomerInfo = AcpService::getCustomerInfoWithEncrypt($customerInfo);
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "77",
            'txnSubType' => "00",
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'encryptCertId' => $this->encryptCertId,
            'merId' => $this->merId,
            'orderId' => $order_id,
            'txnTime' => date("YmdHis"),
            'tokenPayData' => "{trId=62000000001&tokenType=01}",
            'accNo' => $encryptAccNo,
            'customerInfo' => $encryptCustomerInfo
        ];
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr) <= 0) {
            echo $utils->printResult ( $url, $params, "" );
            return $utils->returnData(-1,"请求通信失败");
        }
        echo $utils->printResult ($url, $params, $result_arr );
        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,"应答报文验签失败",$result_arr);
        }
        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            echo "交易成功。<br>";
            return $utils->returnData(1,"交易成功",$result_arr);
        }else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,"交易失败：".$result_arr["respMsg"],$result_arr);
        }
    }




    /**
     * 商户侧开通  2-开通
     * @param     array      $data    订单数据集合
     * @return   array
     */
    public function backOpen($data){
        $utils = new Utils();
        $accNo = $data['account_number'];
        $encryptAccNo = AcpService::encryptData($accNo);
        $customerInfo = [
            'phoneNo' => $data['mobile'],
            'cvn2' => "248",
            'expired' => '1912',
            'smsCode' => $data['smsCode']
        ];
        $encryptCustomerInfo = AcpService::getCustomerInfoWithEncrypt($customerInfo);
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "79",
            'txnSubType' => "00",
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'encryptCertId' => $this->encryptCertId,
            'merId' => $this->merId,
            'orderId' => $data['order_id'],
            'txnTime' => $data['txnTime'],
            'tokenPayData' => "{trId=62000000001&tokenType=01}",
            'accNo' => $encryptAccNo,
            'customerInfo' => $encryptCustomerInfo
        ];
        AcpService::sign($params);
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr) <=0 ) {
            echo $utils->printResult ( $url, $params, "" );
            return $utils->returnData(-1,"请求通信失败");
        }
        echo $utils->printResult ($url, $params, $result_arr );
        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,"应答报文验签失败",$result_arr);
        }
        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            //开通成功
            echo "开通成功。<br>";
            $tokenPayData = $result_arr["tokenPayData"];
            $tokenPayData = com\unionpay\acp\sdk\convertStringToArray(substr($tokenPayData, 1, strlen($tokenPayData)-2));
            $token = "";
            if(array_key_exists("token", $tokenPayData)){
                $token = $tokenPayData["token"];
            }
            foreach ($tokenPayData as $key => $value){
                echo $key . "=" . $value . "<br>";
            }
            $returnData = [
                'token' => $token
            ];
            return $utils->returnData(1,"开通成功",$result_arr,$returnData);
        } else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,"失败:".$result_arr['respMsg'],$result_arr);
        }
    }




    /**
     * 银联侧开通 2-开通状态查询
     * @param      array     $data  订单数据
     * @return   array
     */
    public function openQuery($data){
        $utils = new Utils();
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "78",
            'txnSubType' => "01",
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'encryptCertId' => $this->encryptCertId,
            'merId' => $this->merId,
            'orderId' => $data['order_id'],
            'txnTime' => $data['txnTime']
        ];
        //签名
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        //发送请求
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr) <= 0) {
            echo $utils->printResult ( $url, $params, "" );
            return $utils->returnData(-1,"请求通信失败");
        }
        //页面打印请求应答数据
        echo $utils->printResult ($url, $params, $result_arr );
        //验签
        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,"应答验签失败",$result_arr);
        }
        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            //开通成功
            echo "开通成功。<br>";
            echo "tokenPayData子域：<br>";
            $tokenPayData = $result_arr["tokenPayData"];
            $tokenPayData = com\unionpay\acp\sdk\convertStringToArray(substr($tokenPayData, 1, strlen($tokenPayData)-2));
            $token = "";
            if(array_key_exists("token", $tokenPayData)){
                $token = $tokenPayData["token"];
            }
            $returnTokenPayData = "";
            foreach ($tokenPayData as $key => $value){
                $returnTokenPayData = $key . "=" . $value;
                echo $key . "=" . $value . "<br>";
            }
            echo "customerInfo子域：<br>";
            $customerInfo = AcpService::parseCustomerInfo ( $result_arr ["customerInfo"] );
            if (array_key_exists ( "phoneNo", $_POST )) {
                $phoneNo = $customerInfo ["phoneNo"];
            }
            $returnCustomerInfo = "";
            foreach ( $customerInfo as $key => $value ) {
                $returnCustomerInfo = $key . "=" . $value;
                echo $key . "=" . $value . "<br>";
            }
            $returnData = [
                'token' => $token,
                'tokenPayData' => $returnTokenPayData,
                'customerInfo' => $returnCustomerInfo
            ];
            return $utils->returnData(1,"开通成功",$result_arr,$returnData);
        } else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,"失败:".$result_arr['respMsg'],$result_arr);
        }
    }




    /**
     * 银联侧开通 3-获取消费验证码
     * @param     array    $data    消费验证数据集合
     * @return   array
     */
    public function sms_consume($data){
        $utils = new Utils();
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "77",
            'txnSubType' => '02',
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'currencyCode' => $this->currencyCode,
            'encryptCertId' => $this->encryptCertId,
            'merId' => $this->merId,
            'orderId' => $data['order_id'],
            'txnTime' => $data['txnTime'],
            //金额
            'txnAmt' => $data['txnAmt'],
            //token号从开通和开通查询借口获取，trId和开通接口时上送的相同
            'tokenPayData' => "{trId=62000000001&token=" . $data["token"]. "}",
        ];
        // 签名
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr)<=0) {
            echo $utils->printResult ( $url, $params, "" );
            return $utils->returnData(-1,"请求通信失败");
        }

        echo $utils->printResult ($url, $params, $result_arr );

        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,"应答验签失败",$result_arr);
        }
        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            echo "交易成功。<br>";
            return $utils->returnData(1,"交易成功",$result_arr);
        }else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,"失败:".$result_arr['respMsg'],$result_arr);
        }

    }





    /**
     * 银联侧开通  4-消费
     * @param     array    $data   消费参数
     * @return   array
     */
    public function consume($data){
        $utils = new Utils();
        $customerInfo = [
            'smsCode' => $data['smsCode']
        ];
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "01",
            'txnSubType' => "01",
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'currencyCode' => $this->currencyCode,
            'encryptCertId' => $this->encryptCertId,
            'backUrl' => $this->backUrl,
            'merId' => $this->merId,
            'orderId' => $data['order_id'],
            'txnTime' => $data['txnTime'],
            //金额
            'txnAmt' => $data['txnAmt'],
            'tokenPayData' => "{trId=62000000001&token=" . $_POST["token"]. "}",
            //持卡人身份信息，新规范请按此方式填写
            'customerInfo' => AcpService::getCustomerInfoWithEncrypt($customerInfo)
        ];
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr) <=0 ) {
            echo $utils->printResult ( $url, $params, "" );
            return $utils->returnData(-1,"请求通信失败");
        }

        echo $utils->printResult ($url, $params, $result_arr ); //页面打印请求应答数据

        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,"应答验签失败",$result_arr);
        }
        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
            echo "受理成功。<br>";
            return $utils->returnData(1,"受理成功",$result_arr);
        } else if ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05" ){
            //后续需发起交易状态查询交易确定交易状态
            echo "处理超时，请稍后查询。<br>";
            return $utils->returnData(-1,"处理超时，请稍后重试",$result_arr);
        } else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,"受理失败:".$result_arr['respMsg'],$result_arr);
        }


    }




    /**
     * 银联侧开通  5-交易状态查询
     * @param     array     $data   订单数据集合
     * @return   array
     */
    public function consumeQuery($data){
        $utils = new Utils();
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "00",
            'txnSubType' => "00",
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'orderId' => $data['order_id'],
            'merId' => $this->merId,
            'txnTime' => $data['txnTime']
        ];
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->singleQueryUrl;
        $result_arr = AcpService::post ( $params, $url);
        if(count($result_arr) <= 0) {
            echo $utils->printResult ( $url, $params, "" );
            return $utils->returnData(-1,"请求通信失败");
        }
        echo $utils->printResult ($url, $params, $result_arr );
        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,"应答验签失败",$result_arr);
        }

        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            if ($result_arr["origRespCode"] == "00"){
                //交易成功
                echo "交易成功。<br>";
                return $utils->returnData(1,"交易成功",$result_arr);
            } else if ($result_arr["origRespCode"] == "03"
                || $result_arr["origRespCode"] == "04"
                || $result_arr["origRespCode"] == "05"){
                //后续需发起交易状态查询交易确定交易状态
                echo "交易处理中，请稍微查询。<br>";
                return $utils->returnData(0,"交易处理中，请稍后查询",$result_arr);
            } else {
                //其他应答码做以失败处理
                echo "交易失败：" . $result_arr["origRespMsg"] . "。<br>";
                return $utils->returnData(-1,'交易失败:'.$result_arr['origRespMsg'],$result_arr);
            }
        } else if ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05" ){
            //后续需发起交易状态查询交易确定交易状态
            echo "处理超时，请稍微查询。<br>";
            return $utils->returnData(-1,'处理超时，请稍微查询',$result_arr);
        } else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,'失败:'.$result_arr['respMsg'],$result_arr);
        }
    }





    /**
     * 消费撤销
     * @param   array     $data    消费订单数据
     * @return   array
     */
    public function consumeUndo($data){
        $utils = new Utils();
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "31",
            'txnSubType' => "00",
            'bizType' => "000301",
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'backUrl' => $this->backUrl,
            'orderId' => $data['order_id'],
            'merId' => $this->merId,
            'origQryId' => $data['origQryId'],
            'txnTime' => $data['txnTime'],
            'txnAmt' => $data['txnAmt']
        ];
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr) <= 0) {
            echo $utils->printResult ( $url, $params, "" );
            return $utils->returnData(-1,'请求通信失败');
        }

        echo $utils->printResult ($url, $params, $result_arr );

        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,'应答验签失败',$result_arr);
        }

        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
            echo "受理成功。<br>";
            return $utils->returnData(1,'受理成功',$result_arr);
        } else if ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05" ){
            //后续需发起交易状态查询交易确定交易状态
            echo "处理超时，请稍微查询。<br>";
            return $utils->returnData(-1,'处理超时，请稍后查询',$result_arr);
        } else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,'失败:'.$result_arr['respMsg'],$result_arr);
        }


    }





    /**
     * 退货
     * @param     array     $data    订单数据集合
     * @return   array
     */
    public function refund($data){
        $utils = new Utils();
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "04",
            'txnSubType' => "00",
            'bizType' => "000301",
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'backUrl' => $this->backUrl,
            'orderId' => $data['order_id'],
            'merId' => $this->merId,
            'origQryId' => $data['origQryId'],
            'txnTime' => $data['txnTime'],
            'txnAmt' => $data['txnAmt']
        ];
        //签名
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr) <= 0){
            echo $utils->printResult($url,$params,"");
            return $utils->returnData(-1,'请求通信失败');
        }

        echo $utils->printResult ($url, $params, $result_arr );

        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,'应答验签失败',$result_arr);
        }
        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
            echo "受理成功。<br>";
            return $utils->returnData(1,'受理成功',$result_arr);
        } else if ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05" ){
            //后续需发起交易状态查询交易确定交易状态
            echo "处理超时，请稍微查询。<br>";
            return $utils->returnData(-1,'处理超时，请稍后查询',$result_arr);
        } else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,'失败：'.$result_arr['respMsg'],$result_arr);
        }

    }




    /**
     * 解除标记
     * @param     array    $data   订单参数集合
     * @return   array
     */
    public function deleteToken($data){
        $utils = new Utils();
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "74",
            'txnSubType' => "01",
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'encryptCertId' => $this->encryptCertId,
            'merId' => $this->merId,
            'orderId' => $data['orderId'],
            'txnTime' => $data['txnTime'],
            'tokenPayData' => "{trId=62000000001&token=" . $_POST["token"]. "}"
        ];
        //签名
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr) <= 0){
            echo $utils->printResult($url,$params,"");
            return $utils->returnData(-1,'请求通信失败');
        }

        echo $utils->printResult ($url, $params, $result_arr );

        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,'应答验签失败',$result_arr);
        }
        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
            echo "受理成功。<br>";
            return $utils->returnData(1,'受理成功',$result_arr);
        } else if ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05" ){
            //后续需发起交易状态查询交易确定交易状态
            echo "处理超时，请稍微查询。<br>";
            return $utils->returnData(-1,'处理超时，请稍后查询',$result_arr);
        } else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,'失败：'.$result_arr['respMsg'],$result_arr);
        }

    }




    /**
     * 更新标记
     * @param    array   $data    订单数据集合
     * @return   array
     */
    public function updateToken($data){
        $utils = new Utils();
        $customerInfo = [
            'phoneNo' => $data['mobile'],
            'cvn2' => "248",
            'expired' => "1912",
            'smsCode' => $data['smsCode']
        ];
        $params = [
            'version' => $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'txnType' => "79",
            'txnSubType' => "03",
            'bizType' => $this->bizType,
            'accessType' => $this->accessType,
            'channelType' => $this->channelType,
            'encryptCertId' => $this->encryptCertId,
            'merId' => $this->merId,
            'orderId' => $data['orderId'],
            'txnTime' => $data['txnTime'],
            'tokenPayData' => $data['tokenPayData'],
            'customerInfo' => AcpService::getCustomerInfoWithEncrypt($customerInfo)
        ];
        AcpService::sign ( $params );
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post ( $params, $url );
        if(count($result_arr) <=0 ) {
            echo $utils->printResult ( $url, $params, "" );
            return $utils->returnData(-1,'请求通信失败');
        }
        echo $utils->printResult ($url, $params, $result_arr );
        if (!AcpService::validate ($result_arr) ){
            echo "应答报文验签失败<br>";
            return $utils->returnData(-1,'应答验签失败',$result_arr);
        }
        echo "应答报文验签成功<br>";
        if ($result_arr["respCode"] == "00"){
            //更新标记成功
            echo "更新标记成功。<br>";
            $tokenPayData = $result_arr["tokenPayData"];
            $tokenPayData = com\unionpay\acp\sdk\convertStringToArray(substr($tokenPayData, 1, strlen($tokenPayData)-2));
            $token = "";
            if(array_key_exists("token", $tokenPayData)){
                $token = $tokenPayData["token"];
            }
            foreach ($tokenPayData as $key => $value){
                echo $key . "=" . $value . "<br>";
            }
            $returnData = [
                'token' => $token
            ];
            return $utils->returnData(1,'成功',$result_arr,$returnData);
        } else {
            //其他应答码做以失败处理
            echo "失败：" . $result_arr["respMsg"] . "。<br>";
            return $utils->returnData(-1,'失败:'.$result_arr['respMsg'],$result_arr);
        }
    }




}