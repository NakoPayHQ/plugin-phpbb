<?php
/**
 * Database migration for NakoPay phpBB extension.
 *
 * Creates the nakopay_orders table and ACP module entries.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\payments\migrations;

class install_nakopay extends \phpbb\db\migration\migration
{
    public function effectively_installed(): bool
    {
        return $this->db_tools->sql_table_exists($this->table_prefix . 'nakopay_orders');
    }

    public static function depends_on(): array
    {
        return ['\phpbb\db\migration\data\v330\v330'];
    }

    public function update_schema(): array
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'nakopay_orders' => [
                    'COLUMNS' => [
                        'order_id'            => ['UINT', null, 'auto_increment'],
                        'phpbb_user_id'       => ['UINT', 0],
                        'phpbb_item_id'       => ['VCHAR:128', ''],
                        'phpbb_group_id'      => ['UINT', 0],
                        'nakopay_invoice_id'   => ['VCHAR:64', ''],
                        'address'             => ['VCHAR:128', ''],
                        'coin'                => ['VCHAR:16', 'BTC'],
                        'currency'            => ['VCHAR:8', 'USD'],
                        'amount_fiat'         => ['VCHAR:32', '0'],
                        'amount_crypto'       => ['VCHAR:32', '0'],
                        'status'              => ['VCHAR:32', 'pending'],
                        'tx_hash'             => ['VCHAR:128', ''],
                        'checkout_url'        => ['TEXT', ''],
                        'bip21'               => ['TEXT', ''],
                        'created_at'          => ['UINT:11', 0],
                        'updated_at'          => ['UINT:11', 0],
                    ],
                    'PRIMARY_KEY' => 'order_id',
                    'KEYS' => [
                        'idx_invoice'  => ['UNIQUE', 'nakopay_invoice_id'],
                        'idx_user'     => ['INDEX', 'phpbb_user_id'],
                        'idx_status'   => ['INDEX', 'status'],
                    ],
                ],
            ],
        ];
    }

    public function revert_schema(): array
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'nakopay_orders',
            ],
        ];
    }

    public function update_data(): array
    {
        return [
            // Config entries
            ['config.add', ['nakopay_api_key', '']],
            ['config.add', ['nakopay_webhook_secret', '']],
            ['config.add', ['nakopay_currency', 'USD']],
            ['config.add', ['nakopay_test_mode', 0]],

            // ACP module
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_NAKOPAY_TITLE',
            ]],
            ['module.add', [
                'acp',
                'ACP_NAKOPAY_TITLE',
                [
                    'module_basename' => '\nakopay\payments\acp\main_module',
                    'modes'           => ['settings'],
                ],
            ]],
        ];
    }
}
