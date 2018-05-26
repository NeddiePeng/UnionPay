<?php
/**
 * 支付Controller.
 * User: Pengfan
 * Date: 2018/5/21
 * Time: 17:49
 */
namespace app\controllers;
use Yii;
use yii\web\Controller;

class PaymentController extends Controller
{


    /**
     * 初始化
     */
    public function init(){

    }




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
     * 支付页面
     */
    public function actionIndex(){
        return $this->render('index');
    }





    /**
     * 支付调用并且生成二维码
     */
    public function actionPay(){
        $response = Yii::$app->response;
        $response->format = yii\web\Response::FORMAT_JSON;
        $pay_obj = new \AggregatePay();
        $param = Yii::$app->request->post();
        $pay = $pay_obj->WeChat_pay($param);
        if($pay['code'] > 0){
            $url_code = $pay['data'];
            $res = $pay_obj->Code($url_code);
            return $res;
        }else{
            return $pay;
        }
    }




    /**
     * 支付成功后跳转页面
     */
    public function actionSuccess_jump(){
        $response = Yii::$app->response;
        $response->format = yii\web\Response::FORMAT_JSON;
        $status = true;
        $url = Yii::$app->urlManager->createUrl("");
        if($status){
            return ['code' => 1,'msg' => "支付成功",'data' => $url];
        }else{
            $OrderDetailsUrl = Yii::$app->urlManager->createUrl("");
            return ['code' => -1,'msg' => "支付失败",'data' => $OrderDetailsUrl];
        }
    }




    /**
     * 交易退款/撤销
     */
    public function actionSuccess_refund(){
        $response = Yii::$app->response;
        $response->format = yii\web\Response::FORMAT_JSON;
        $pay_obj = new \AggregatePay();
        $param = Yii::$app->request->post();
        $payCancel = $pay_obj->payCancel($param);
        return $payCancel;
    }




    /**
     * 交易查询
     */
    public function actionPay_query(){
        $response = Yii::$app->response;
        $response->format = yii\web\Response::FORMAT_JSON;
        $pay_obj = new \AggregatePay();
        $param = Yii::$app->request->post();
        $payQuery = $pay_obj->payQuery($param);
        return $payQuery;
    }




    /**
     * 异步回掉通知
     */
    public function actionNotify(){
        $response = Yii::$app->response;
        $response->format = yii\web\Response::FORMAT_JSON;
        $param = Yii::$app->request->post();
        $pay_obj = new \AggregatePay();
        $notify = $pay_obj->notify($param);
        return $notify ? ['code' => 1,'msg' => "验证成功"] :
                         ['code' => -1,'msg' => "验证失败"];
    }







}