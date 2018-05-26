<?php
/**
 * 公共方法文件.
 * User: Pengfan
 * Date: 2018/5/23
 * Time: 9:10
 */
class Utils
{


    //构造函数
    public function __construct()
    {

    }



    /**
     * 生成订单号
     * @return   string
     */
    public function orderId(){
        @date_default_timezone_set("PRC");
        $order_id_main = date("YmdHis").rand(10000000,99999999);
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for($i= 0; $i < $order_id_len; $i++){
            $order_id_sum += (int)(substr($order_id_main,$i,1));
        }
        $str = str_pad((100 - $order_id_sum % 100) % 100,2,0,STR_PAD_LEFT);
        $order_id = $order_id_main.$str;
        return $order_id;
    }


    /**
     * 打印请求应答
     * @param     $url
     * @param     $req
     * @param     $resp
     * @return    string
     */
    public function printResult($url, $req, $resp) {
        $str = "=============<br>";
        $str .= "地址：" . $url . "<br>";
        $str .= "请求：" . str_replace ( "\n", "\n<br>", htmlentities ( com\unionpay\acp\sdk\createLinkString ( $req, false, true ) ) ) . "<br>";
        $str .= "应答：" . str_replace ( "\n", "\n<br>", htmlentities ( com\unionpay\acp\sdk\createLinkString ( $resp , false, false )) ) . "<br>";
        $str .= "=============<br>";
        return $str;
    }



    /**
     * 返回请求数据
     * @param    string     $code    状态码
     * @param    string     $msg     返回状态信息
     * @param    string      $resp    请求通信返回数据
     * @param    array      $data    返回状态数据
     * @return   array
     */
    public function returnData($code,$msg,$resp = "",$data = null){
        $retmsg = str_replace ( "\n", "\n<br>", htmlentities ( com\unionpay\acp\sdk\createLinkString ( $resp , false, false )) );
        if(!$data){
            $data = new stdClass();
        }
        return [
            'code' => $code,
            'msg' => $msg,
            'retmsg' => $retmsg,
            'data' => $data
        ];
    }


}




