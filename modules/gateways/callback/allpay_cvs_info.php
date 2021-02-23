<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../allpay/allpay.php';

$params = getGatewayVariables('allpay_cvs');
if (!$params['type']) {
    die('Module Not Activated');
}

$res = new AllPay_Response('CVS');
if ($params['testMode'] == 'on') {
    $res->setTestMode();
} else {
    $res->MerchantID = $params['MerchantID'];
    $res->HashKey = $params['HashKey'];
    $res->HashIV  = $params['HashIV'];
}
$res->Verify();
$res->CheckInfo();

$invoiceId = substr($res->MerchantTradeNo, strlen($params['InvoicePrefix'])+10);
$invoiceId = checkCbInvoiceID($invoiceId, $params['name']);

if ($res->isSuccess()) {
    $transactionInfo = json_encode( array(
        'PaymentNo'  => $res->PaymentNo,
        'ExpireDate' => $res->ExpireDate
    ) );
    logTransaction($params['name'], $transactionInfo, 'Info Data #'.$invoiceId);
}

echo '0|OK';
