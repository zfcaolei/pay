<?php
namespace app\common\lib;



/*
 * 支付宝支付类
 */

class AliPay{

    var $aop;

    private $alipay_gatewayUrl = "https://openapi.alipay.com/gateway.do"; //网关网址
    private $alipay_appId = 'alipay_appId';  //appId
    private $alipay_rsaPrivateKey = '';//私有密钥;
    private $alipay_rsaPublicKey = ''; //共有密钥


    public function __construct()
    {
        $this->aop    =    new \AopClient();
        $this->aop->gatewayUrl             = $this->alipay_gatewayUrl;   //config('alipay_gatewayUrl');
        $this->aop->appId                 =  $this->alipay_appId; //config('alipay_appId');
        $this->aop->rsaPrivateKey         =  $this->alipay_rsaPrivateKey;  //config('alipay_rsaPrivateKey');//私有密钥
        $this->aop->format                 = "JSON";
        $this->aop->charset                = "utf-8";
        $this->aop->signType            = "RSA2";
        $this->aop->alipayrsaPublicKey     =  $this->alipay_rsaPublicKey;  //config('alipay_rsaPublicKey');//共有密钥
    }




    /**
     * 创建APP支付订单
     *
     * @param string $body 对一笔交易的具体描述信息。
     * @param string $subject 商品的标题/交易标题/订单标题/订单关键字等。
     * @param string $order_sn 商户网站唯一订单号
     * @return array 返回订单信息
     */
    public function tradeAppPay($body, $subject, $order_sn, $total_amount)
    {
        require_once(EXTEND_PATH . 'alipay/aop/request/AlipayTradeAppPayRequest.php');
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent    =    [
            'body'                =>    $body,
            'subject'            =>    $subject,
            'out_trade_no'        =>    $order_sn,
            'timeout_express'    =>    '1d',//失效时间为 1天
            'total_amount'        =>    $total_amount,//价格
            'product_code'        =>    'QUICK_MSECURITY_PAY',
        ];
        //商户外网可以访问的异步地址 (异步回掉地址，根据自己需求写)
        $request->setNotifyUrl(url("/pay/AliPayNotify"));
        $request->setBizContent(json_encode($bizcontent));
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $this->aop->sdkExecute($request);
        return $response;
        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
    }



    /**
     * 异步通知验签
     *
     * @param string $params 参数
     * @param string $signType 签名类型：默认RSA
     * @return bool 是否通过
     */
    public function rsaCheck($params, $signType)
    {
        return $this->aop->rsaCheckV1($params, NULL, $signType);
    }



//创建支付订单参数
//$alipay        = new AliPay();
////参数自己看上面AliPay.php的tradeAppPay函数
//$orderInfo    = $alipay->tradeAppPay($orderBody, $orderTitle, $out_trade_no, (float)$price);
////将获取的参数返回给app端 jsonReturn是自定义的方法
//jsonReturn(1, 2101003, ['payinfo'=>$orderInfo], '操作成功');

    //支付成功回调回调
    public function AliPayNotify()
    {
        $request    = input('post.');

        //写入文件做日志 调试用
//        $log = "<br />\r\n\r\n".'==================='."\r\n".date("Y-m-d H:i:s")."\r\n".json_encode($request);
//        @file_put_contents('upload/alipay.html', $log, FILE_APPEND);

        $signType = $request['sign_type'];
        $alipay = new AliPayApp();
        $flag = $alipay->rsaCheck($request, $signType);

        if ($flag) {
            //支付成功:TRADE_SUCCESS   交易完成：TRADE_FINISHED
            if ($request['trade_status'] == 'TRADE_SUCCESS' || $request['trade_status'] == 'TRADE_FINISHED') {
                //这里根据项目需求来写你的操作 如更新订单状态等信息 更新成功返回'success'即可
                $object =  json_decode(($request['fund_bill_list']),true);
                $trade_type     =   $object[0]['fundChannel'];
                $data    =    [
                    'pay_status'        =>   1,
                    'pay_type'          =>   1,
                    'trade_type'        =>   $trade_type,
                    'pay_time'          =>   strtotime($request['gmt_payment'])
                ];
                $buyer_pay_amount = $request['buyer_pay_amount'];
                $out_trade_no   =   $request['out_trade_no'];
                $saveorder      =   model('orders')->successPay($out_trade_no,$buyer_pay_amount,$data);
                if ($saveorder==1) {
                    exit('success'); //成功处理后必须输出这个字符串给支付宝
                } else {
                    exit('fail');
                }
            } else {
                exit('fail');
            }
        } else {
            exit('fail');
        }
    }

}
