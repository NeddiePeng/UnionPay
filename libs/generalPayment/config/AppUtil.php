<?php
class AppUtil{



	/**
	 * 将参数数组签名
	 */
	public static function SignArray(array $array,$appkey){
        // 将key放到数组中一起进行排序和组装
		$array['key'] = $appkey;
		ksort($array);
		$blankStr = AppUtil::ToUrlParams($array);
		$sign = md5($blankStr);
		return $sign;
	}


	/**
	 * 拼接参数
     */
	public static function ToUrlParams(array $array)
	{
		$buff = "";
		foreach ($array as $k => $v)
		{
			if($v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}



	
    /**
	 * 校验签名
	 * @param array 参数
	 * @param unknown_type appkey
	 */
	public static function ValidSign(array $array,$appkey){
		$sign = $array['sign'];
		unset($array['sign']);
		$array['key'] = $appkey;
		$mySign = AppUtil::SignArray($array, $appkey);
		return strtolower($sign) == strtolower($mySign);
	}









    /**
     * 生成唯一订单号
     */
    public function Order_id(){
        //生成24位唯一订单号码，格式：YYYY-MMDD-HHII-SS-NNNN,NNNN-CC，其中：YYYY=年份，MM=月份，
        //DD=日期，HH=24格式小时，II=分，SS=秒，NNNNNNNN=随机数，CC=检查码
        @date_default_timezone_set("PRC");
        //订购日期
        $order_date = date('Y-m-d');
        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
        $order_id_main = date('YmdHis') . rand(10000000,99999999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for($i=0; $i<$order_id_len; $i++){
            $order_id_sum += (int)(substr($order_id_main,$i,1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100,2,'0',STR_PAD_LEFT);
        return $order_id;
    }





    /**
     * 发送请求操作
     * @param     string     $url     请求地址
     * @param     string     $params  请求参数
     * @return    object
     */
    public function request($url,$params){
        $ch = curl_init();
        $this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
        curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        //如果不加验证,就设false,商户自行处理
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $output = curl_exec($ch);
        curl_close($ch);
        return  $output;
    }



    /**
     * 处理成功验签
     * @param     array     $array    交易返回结果
     * @return    string
     */
    function Success_validSign($array){
        var_dump($array);
        exit;
        if("SUCCESS" == $array["retcode"] && $array['trxstatus'] == "0000"){
            $signRsp = strtolower($array["sign"]);
            $array["sign"] = "";
            $sign =  strtolower(AppUtil::SignArray($array, AppConfig::APPKEY));
            if($sign == $signRsp){
                return TRUE;
            }
            else {
                return "验签失败:".$signRsp."--".$sign;
            }
        } else{
            return $array["retmsg"];
        }
        return FALSE;
    }





    /**
     * 返回处理结果
     * @param     string    $code   返回状态
     * @param     string    $msg    返回信息
     * @param     array     $data   返回结果数据
     * @return    array
     */
    public function Return_result($code,$msg,$data = null){
        if($data){
            $Re_data = $data;
        }else{
            $Re_data = new stdClass();
        }
        $data = [
            'code' => $code,
            'msg' => $msg,
            'data' => $Re_data
        ];
        return $data;
    }






    /**
     * 随机字符串
     * @param     string     $len   字符长度
     * @return    string
     */
    public function rend_str($len){
        $str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
        $new_str = "";
        for ($i = 0; $i < $len; $i++){
            $new_str .= $str[mt_rand(0,strlen($str) - 1)];
        }
        return $new_str;
    }





	
}
?>