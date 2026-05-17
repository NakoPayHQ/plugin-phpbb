<?php
/**
 * English language file for NakoPay phpBB extension.
 *
 * @package nakopay/phpbb-extension
 */

if (!defined('IN_PHPBB')) {
    exit;
}

if (empty($lang) || !is_array($lang)) {
    $lang = [];
}

$lang = array_merge($lang, [
    'ACP_NAKOPAY_TITLE'          => 'NakoPay Bitcoin Payments',
    'ACP_NAKOPAY_SETTINGS'       => 'Settings',
    'ACP_NAKOPAY_API_KEY'        => 'API Key',
    'ACP_NAKOPAY_API_KEY_DESC'   => 'Your secret key. Use sk_test_... while testing, sk_live_... in production.',
    'ACP_NAKOPAY_WEBHOOK_SECRET' => 'Webhook Signing Secret',
    'ACP_NAKOPAY_WEBHOOK_DESC'   => 'Paste the signing secret shown when you created the webhook endpoint in NakoPay.',
    'ACP_NAKOPAY_CURRENCY'       => 'Currency',
    'ACP_NAKOPAY_CURRENCY_DESC'  => 'Fiat currency for pricing (e.g. USD, EUR, GBP).',
    'ACP_NAKOPAY_TEST_MODE'      => 'Test Mode',
    'ACP_NAKOPAY_TEST_MODE_DESC' => 'Cosmetic label - the API key prefix already determines live vs test mode.',
    'ACP_NAKOPAY_SAVED'          => 'NakoPay settings saved.',
    'ACP_NAKOPAY_VERSION'        => 'Plugin version',
    'NAKOPAY_INVALID_AMOUNT'     => 'Invalid payment amount.',
    'NAKOPAY_PAY_WITH_BITCOIN'   => 'Pay with Bitcoin',
    'NAKOPAY_CHECKOUT_TITLE'     => 'Bitcoin Payment',
    'NAKOPAY_SCAN_QR'            => 'Scan the QR code or copy the address below to send your payment.',
    'NAKOPAY_ADDRESS'            => 'Bitcoin Address',
    'NAKOPAY_AMOUNT'             => 'Amount',
    'NAKOPAY_STATUS'             => 'Status',
    'NAKOPAY_STATUS_PENDING'     => 'Waiting for payment...',
    'NAKOPAY_STATUS_PAID'        => 'Payment received!',
    'NAKOPAY_STATUS_EXPIRED'     => 'Invoice expired.',
    'NAKOPAY_COPIED'             => 'Copied!',
]);
