<?php
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../allpay/allpay.php';

$params = getGatewayVariables('allpay_credit');
if (!$params['type']) {
    die('Module Not Activated');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid method');
}

$res = new AllPay_Response('Credit');
if ($params['testMode'] == 'on') {
    $res->setTestMode();
} else {
    $res->MerchantID = $params['MerchantID'];
    $res->HashKey = $params['HashKey'];
    $res->HashIV  = $params['HashIV'];
}
$res->Verify();
$res->CheckPay();

$invoiceId = substr($res->MerchantTradeNo, strlen($params['InvoicePrefix'])+10);
$invoiceId = checkCbInvoiceID($invoiceId, $params['name']);
$transactionId = $res->getTransactionID();
$paymentAmount = $res->TradeAmt;
$paymentFee = $res->PaymentTypeChargeFee;
checkCbTransID($transactionId);
logTransaction($params['name'], $res->getRaw(), $res->getStatus());

if ($res->isSuccess()) {
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        $paymentFee,
        'allpay_credit'
    );
}

echo '1|OK';
