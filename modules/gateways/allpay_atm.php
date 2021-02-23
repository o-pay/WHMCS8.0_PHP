<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;
require_once __DIR__ . '/allpay/allpay.php';

function allpay_atm_MetaData() {
    return array(
        'DisplayName' => '歐付寶 - ATM',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => false,
        'TokenisedStorage' => false,
    );
}

function allpay_atm_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'ATM',
        ),
        'MerchantID' => array(
            'FriendlyName' => '會員編號',
            'Type' => 'text',
            'Size' => '7',
            'Default' => '',
            'Description' => '歐付寶會員編號。',
        ),
        'HashKey' => array(
            'FriendlyName' => 'HashKey',
            'Type' => 'password',
            'Size' => '16',
            'Default' => '',
            'Description' => '於廠商管理後台->系統開發管理->系統介接設定中取得',
        ),
        'HashIV' => array(
            'FriendlyName' => 'HashIV',
            'Type' => 'password',
            'Size' => '16',
            'Default' => '',
            'Description' => '於廠商管理後台->系統開發管理->系統介接設定中取得',
        ),
        'ExpireDate' => array(
            'FriendlyName' => '繳費有效天數',
            'Type' => 'text',
            'Size' => '3',
            'Default' => '7',
            'Description' => '',
        ),
        'InvoicePrefix' => array(
            'FriendlyName' => '帳單前綴',
            'Type' => 'text',
            'Default' => '',
            'Description' => '選填（只能為數字、英文，且與帳單 ID 合併總字數不能超過 20）',
            'Size' => '5',
        ),
        'testMode' => array(
            'FriendlyName' => '測試模式',
            'Type' => 'yesno',
            'Description' => '測試模式',
        ),
    );
}

function allpay_atm_link($params) {

    # check if in log
    $log = Capsule::table('tblgatewaylog')
            ->where('gateway', $params['name'])
            ->where('result', 'Info Data #'.$params['invoiceid'])
            ->orderBy('id', 'desc')
            ->first();
    if ($log) {
        $log = json_decode($log->data, true);
        $BankCode = $log['BankCode'];
        $vAccount = $log['vAccount'];
        $ExpireDateStr = $log['ExpireDate'];
        $ExpireDate = strtotime($ExpireDateStr);
        if ($ExpireDate >= date()) {
            return '<div class="text-left alert alert-info"><p><b>銀行代碼：</b><code>'.$BankCode.'</code></p>'.
            '<p><b>帳號：</b><code>'.chunk_split($vAccount, 4, ' ').'</code></p>'.
            '<p><b>帳號繳費期限：</b><code>'.$ExpireDateStr.'</code></p></div>';
        }
    }

    # Invoice Variables
    $TimeStamp = time();
    $TradeNo = $params['InvoicePrefix'].$TimeStamp.$params['invoiceid'];
    $amount = $params['amount']; # Format: ##.##
    $TotalAmount = round($amount); # Format: ##

    # System Variables
    $systemurl = $params['systemurl'];

    # 交易設定
    $ExpireDate = $params['ExpireDate'];
    if (!$params['ExpireDate']) {
        $ExpireDate = 7; //預設7天
    }

    $transaction = new AllPay_Pay('ATM');

    # 是否為測試模式
    if ($params['testMode'] == 'on') {
        $transaction->setTestMode();
    } else {
        $transaction->MerchantID = $params['MerchantID'];
        $transaction->HashKey = $params['HashKey'];
        $transaction->HashIV  = $params['HashIV'];
    }

    $transaction->MerchantTradeNo = $TradeNo;
    $transaction->TotalAmount = $TotalAmount;
    $transaction->TradeDesc = $params['description'];
    $transaction->ItemName = $params['description'];
    $transaction->ReturnURL = rtrim($systemurl, '/').'/modules/gateways/callback/allpay_atm.php';
    $transaction->PaymentInfoURL = rtrim($systemurl, '/').'/modules/gateways/callback/allpay_atm_info.php';
    $transaction->ClientBackURL = $params['returnurl'];
    $transaction->ExpireDate = $ExpireDate;

    return $transaction->GetHTML($params['langpaynow']);
}
