<?php
/**
 * 网关支付Api.
 * User: PengFan
 * Date: 2018/5/24
 * Time: 16:54
 */
header ('Content-type:text/html;charset=utf-8' );
include_once 'sdk/acp_service.php';
include_once 'Utils.php';
use com\unionpay\acp\sdk\SDKConfig;
use com\unionpay\acp\sdk\AcpService;
class Gateway_pay_api
{

    //版本号
    private $version;
    //签名方式
    private $signMethod;
    //编码方式
    private $encoding = "utf-8";
    //业务类型【000201：B2C网关支付】
    private $bizType = "000201";
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
    //公共参数
    private $common = [];


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
        $this->common = $common = [
            'version' =>  $this->version,
            'encoding' => $this->encoding,
            'signMethod' => $this->signMethod,
            'channelType' => $this->channelType,
            'accessType' => $this->accessType,
            'currencyCode' => $this->currencyCode,
            'merId' => $this->merId
        ];
    }




    /**
     * 跳转网关支付页面支付
     * @param     array     $data   订单信息
     * @return    string
     */
    public function frontConsume($data){
        $params = $this->common;
        $params['txnType'] = "01";
        $params['txnSubType'] = "01";
        $params['bizType'] = $this->bizType;
        $params['orderId'] = $data['order_id'];
        $params['txnTime'] = date("YmdHis");
        $params['txnAmt'] = $data['money_total'];
        $params['frontUrl'] = $this->frontUrl;
        $params['backUrl'] = $this->backUrl;
        $params['payTimeout'] = date('YmdHis', strtotime('+15 minutes'));
        AcpService::sign ($params);
        $uri = SDKConfig::getSDKConfig()->frontTransUrl;
        $html_form = AcpService::createAutoFormHtml($params,$uri);
        echo $html_form;
    }




    /**
     * 交易状态查询
     * @param     array     $data    订单参数集合
     * @return    array
     */
    public function formQuery($data){
        $utils = new Utils();
        $params = $this->common;
        $params['txnType'] = "00";
        $params['txnSubType'] = "00";
        $params['bizType'] = "000000";
        $params['orderId'] = $data['order_id'];
        $params['txnTime'] = $data["overTime"];
        AcpService::sign ($params);
        $url = SDKConfig::getSDKConfig()->singleQueryUrl;
        $result_arr = AcpService::post ($params,$url);
        if(count($result_arr) <=0 ) {
            return $utils->returnData(-1,'请求通信失败');
        }
        if (!AcpService::validate ($result_arr) ){
            return $utils->returnData(-2,'应答报文验签失败',$result_arr);
        }
        if ($result_arr["respCode"] == "00"){
            if ($result_arr["origRespCode"] == "00"){
                return $utils->returnData(1,'交易成功',$result_arr);
            } else if ($result_arr["origRespCode"] == "03"
                || $result_arr["origRespCode"] == "04"
                || $result_arr["origRespCode"] == "05"){
                //后续需发起交易状态查询交易确定交易状态
                return $utils->returnData(0,'交易处理中，请稍后查询',$result_arr);
            } else {
                //其他应答码做以失败处理
                return $utils->returnData(-1,'交易失败:'.$result_arr['origRespMsg'],$result_arr);
            }
        } else if ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05" ){
            //后续需发起交易状态查询交易确定交易状态
            return $utils->returnData(0,'处理超时，请稍后查询',$result_arr);
        } else {
            //其他应答码做以失败处理
            return $utils->returnData(-1,'失败:'.$result_arr['respMsg'],$result_arr);
        }

    }





    /**
     * 消费撤销
     * @param     array      $data     需要查询的订单数据
     * @return    array
     */
    public function consumeUndo($data){
        $utils = new Utils();
        $params = $this->common;
        $params['txnType'] = "31";
        $params['txnSubType'] = "00";
        $params['bizType'] = "000201";
        $params['orderId'] = $data['order_id'];
        $params['txnTime'] = $data["overTime"];
        $params['origQryId'] = $data['origQryId'];
        $params['txnAmt'] = $data["money_total"];
        //签名
        AcpService::sign ($params);
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post($params,$url);
        if(count($result_arr) <= 0){
            return $utils->returnData(-1,"请求通信失败");
        }
        if(AcpService::validate($result_arr)){
            return $utils->returnData(-1,'应答报文验签失败',$result_arr);
        }
        echo "应答报文验签成功";
        if($result_arr['respCode'] == "00"){
            return $utils->returnData(1,'受理成功',$result_arr);
        }elseif ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05" ){
            return $utils->returnData(0,'受理超时，请稍后查询',$result_arr);
        }else{
            return $utils->returnData(-1,'受理失败:'.$result_arr['respMsg'],$result_arr);
        }
    }




    /**
     * 退货
     * @param      array      $data     订单数据
     * @return     array
     */
    public function refund($data){
        $utils = new Utils();
        $params = $this->common;
        $params['txnType'] = "04";
        $params['txnSubType'] = "00";
        $params['bizType'] = $this->bizType;
        $params['orderId'] = $data['order_id'];
        $params['txnTime'] = $data["overTime"];
        $params['origQryId'] = $data['origQryId'];
        $params['txnAmt'] = $data["money_total"];
        AcpService::sign($params);
        $url = SDKConfig::getSDKConfig()->backTransUrl;
        $result_arr = AcpService::post($params,$url);
        if(count($result_arr) <= 0){
            return $utils->returnData(-1,'请求通信失败');
        }
        if(AcpService::validate($result_arr)){
            return $utils->returnData(-1,'应答报文验签失败',$result_arr);
        }
        if($result_arr['respCode'] == "00"){
            return $utils->returnData(1,'受理成功',$result_arr);
        }elseif ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05" ){
            return $utils->returnData(0,'受理超时，请稍后查询',$result_arr);
        }else{
            return $utils->returnData(-1,'受理失败:'.$result_arr['respMsg'],$result_arr);
        }
    }


}