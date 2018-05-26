<?php
/**
 * 银联网关支付.
 * User: PengFan
 * Date: 2018/5/26
 * Time: 11:41
 */
namespace app\controllers;
use Yii;
use yii\web\Controller;

class GatewayController extends Controller
{



    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }





    /**
     * 禁止加载公共部分
     */
    public $layout = false;



    /**
     * 防止表单多次提交
     */
    public $enableCsrfValidation = false;



    /**
     * 网关支付页面支付
     */
    public function actionUnion_pay_index(){
        $param = Yii::$app->request->post();
        $channelType = "08";
        if($param['source'] != "PC"){
            $channelType = "07";
        }
        $gatePay = new \Gateway_pay_api($channelType);
        $gatePay->frontConsume($param);
    }



    /**
     * 交易状态查询
     */
    public function actionQuery_pay(){
        $response = Yii::$app->response;
        $response->format = yii\web\Response::FORMAT_JSON;
        $param = Yii::$app->request->post();
        $channelType = "08";
        if($param['source'] != "PC"){
            $channelType = "07";
        }
        $gatePay = new \Gateway_pay_api($channelType);
        $formQuery = $gatePay->formQuery($param);
        return $formQuery;
    }



    /**
     * 消费撤销
     */
    public function actionConsumeUndo(){
        $response = Yii::$app->response;
        $response->format = yii\web\Response::FORMAT_JSON;
        $param = Yii::$app->request->post();
        $channelType = "08";
        if($param['source'] != "PC"){
            $channelType = "07";
        }
        $gatePay = new \Gateway_pay_api($channelType);
        $consumeUndo = $gatePay->consumeUndo($param);
        return $consumeUndo;
    }



    /**
     * 消费退货
     */
    public function actionRefund(){
        $response = Yii::$app->response;
        $response->format = yii\web\Response::FORMAT_JSON;
        $param = Yii::$app->request->post();
        $channelType = "08";
        if($param['source'] != "PC"){
            $channelType = "07";
        }
        $gatePay = new \Gateway_pay_api($channelType);
        $refund = $gatePay->refund($param);
        return $refund;
    }


}