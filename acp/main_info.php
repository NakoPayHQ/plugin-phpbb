<?php
/**
 * ACP module info for NakoPay phpBB extension.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\payments\acp;

class main_info
{
    public function module(): array
    {
        return [
            'filename' => '\nakopay\payments\acp\main_module',
            'title'    => 'ACP_NAKOPAY_TITLE',
            'modes'    => [
                'settings' => [
                    'title' => 'ACP_NAKOPAY_SETTINGS',
                    'auth'  => 'ext_nakopay/payments && acl_a_board',
                    'cat'   => ['ACP_NAKOPAY_TITLE'],
                ],
            ],
        ];
    }
}
