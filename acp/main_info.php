<?php
/**
 * ACP module info for NakoPay phpBB extension.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\btcpay\acp;

class main_info
{
    public function module(): array
    {
        return [
            'filename' => '\nakopay\btcpay\acp\main_module',
            'title'    => 'ACP_NAKOPAY_TITLE',
            'modes'    => [
                'settings' => [
                    'title' => 'ACP_NAKOPAY_SETTINGS',
                    'auth'  => 'ext_nakopay/btcpay && acl_a_board',
                    'cat'   => ['ACP_NAKOPAY_TITLE'],
                ],
            ],
        ];
    }
}
