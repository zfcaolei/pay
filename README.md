# pay

**//支付宝支付创建支付订单参数**

     $alipay  = new AliPay();

     $alipay -> alipay_gatewayUrl = "https://openapi.alipay.com/gateway.do"; //网关网址
  
     $alipay -> $alipay_appId = 'alipay_appId';  //appId
  
     $alipay -> $alipay_rsaPrivateKey = '' //私有密钥;

    $alipay->alipay_rsaPublicKey = ''; //共有密钥



    //$orderInfo    = $alipay->tradeAppPay($orderBody, $orderTitle, $out_trade_no, (float)$price,$return_url);

    //将获取的参数返回给app端 jsonReturn是自定义的方法
    jsonReturn(1, 2101003, ['payinfo'=>$orderInfo], '操作成功');



//支付成功回调回调
   
   
    public function AliPayNotify()
    {
    
        $request    = input('post.');

        //写入文件做日志 调试用
        $log = "<br />\r\n\r\n".'==================='."\r\n".date("Y-m-d H:i:s")."\r\n".json_encode($request);
        @file_put_contents('upload/alipay.html', $log, FILE_APPEND);

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


