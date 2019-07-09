<?php
namespace pay\Library;
require_once('../alipay/aop/AopClient.php');

class Loader{

    public function index()
    {
        var_dump(new \AopClient());
    }



}




?>