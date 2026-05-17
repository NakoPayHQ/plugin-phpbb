<?php
/**
 * Customer-facing payment controller for NakoPay phpBB extension.
 *
 * POST /nakopay/pay   - create invoice and show checkout
 * GET  /nakopay/poll/in_xxx - JSON status poll (called every 5s by checkout JS)
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\payments\controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class payment_controller
{
    /** @var \phpbb\config\config */
    protected $config;
    /** @var \phpbb\request\request */
    protected $request;
    /** @var \phpbb\template\template */
    protected $template;
    /** @var \phpbb\user */
    protected $user;
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;
    /** @var \nakopay\payments\core\client */
    protected $client;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\db\driver\driver_interface $db,
        \nakopay\payments\core\client $client
    ) {
        $this->config   = $config;
        $this->request  = $request;
        $this->template = $template;
        $this->user     = $user;
        $this->db       = $db;
        $this->client   = $client;
    }

    public function handle(): Response
    {
        if ($this->user->data['user_id'] == ANONYMOUS) {
            login_box('', $this->user->lang('LOGIN_REQUIRED'));
        }

        $amount      = (float) $this->request->variable('amount', '0');
        $description = $this->request->variable('description', 'phpBB payment');
        $group_id    = (int) $this->request->variable('group_id', 0);
        $item_id     = $this->request->variable('item_id', '');
        $currency    = $this->config['nakopay_currency'] ?: 'USD';
        $user_id     = (int) $this->user->data['user_id'];
        $user_email  = $this->user->data['user_email'] ?? '';

        if ($amount <= 0) {
            trigger_error('NAKOPAY_INVALID_AMOUNT');
        }

        // Check for existing open order
        $sql = 'SELECT * FROM ' . NAKOPAY_ORDERS_TABLE .
               ' WHERE phpbb_user_id = ' . $user_id .
               " AND phpbb_item_id = '" . $this->db->sql_escape($item_id) . "'" .
               " AND status NOT IN ('paid', 'expired', 'cancelled')" .
               ' ORDER BY order_id DESC';
        $result = $this->db->sql_query_limit($sql, 1);
        $order  = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$order) {
            $resp = $this->client->create_invoice([
                'amount'         => $amount,
                'currency'       => $currency,
                'coin'           => 'BTC',
                'description'    => $description,
                'customer_email' => $user_email,
                'phpbb_user_id'  => $user_id,
                'phpbb_item_id'  => $item_id,
            ]);

            if (empty($resp['_ok']) || empty($resp['id'])) {
                $err = $resp['error']['message'] ?? $resp['_error'] ?? 'unknown error';
                trigger_error('NAKOPAY_API_ERROR: ' . htmlspecialchars($err));
            }

            $order_data = [
                'phpbb_user_id'      => $user_id,
                'phpbb_item_id'      => $item_id,
                'phpbb_group_id'     => $group_id,
                'nakopay_invoice_id' => $resp['id'],
                'address'            => $resp['address'] ?? '',
                'coin'               => $resp['coin'] ?? 'BTC',
                'currency'           => $resp['currency'] ?? $currency,
                'amount_fiat'        => $resp['amount'] ?? $amount,
                'amount_crypto'      => $resp['amount_crypto'] ?? 0,
                'status'             => $resp['status'] ?? 'pending',
                'checkout_url'       => $resp['checkout_url'] ?? '',
                'bip21'              => $resp['bip21'] ?? '',
                'created_at'         => time(),
                'updated_at'         => time(),
            ];

            $this->db->sql_query('INSERT INTO ' . NAKOPAY_ORDERS_TABLE . ' ' .
                $this->db->sql_build_array('INSERT', $order_data));

            $sql = 'SELECT * FROM ' . NAKOPAY_ORDERS_TABLE .
                   " WHERE nakopay_invoice_id = '" . $this->db->sql_escape($resp['id']) . "'";
            $result = $this->db->sql_query($sql);
            $order  = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
        }

        $this->template->assign_vars([
            'NAKOPAY_ADDRESS'       => $order['address'],
            'NAKOPAY_AMOUNT_CRYPTO' => $order['amount_crypto'],
            'NAKOPAY_AMOUNT_FIAT'   => $order['amount_fiat'],
            'NAKOPAY_COIN'          => $order['coin'],
            'NAKOPAY_CURRENCY'      => $order['currency'],
            'NAKOPAY_INVOICE_ID'    => $order['nakopay_invoice_id'],
            'NAKOPAY_STATUS'        => $order['status'],
            'NAKOPAY_BIP21'         => $order['bip21'],
            'NAKOPAY_CHECKOUT_URL'  => $order['checkout_url'],
            'NAKOPAY_POLL_URL'      => append_sid(generate_board_url() . '/nakopay/poll/' . $order['nakopay_invoice_id']),
        ]);

        return $this->helper_render('checkout_body.html', 'Bitcoin Payment');
    }

    public function poll(string $invoice_id): JsonResponse
    {
        $invoice_id = preg_replace('/[^a-zA-Z0-9_]/', '', $invoice_id);

        $sql = 'SELECT * FROM ' . NAKOPAY_ORDERS_TABLE .
               " WHERE nakopay_invoice_id = '" . $this->db->sql_escape($invoice_id) . "'";
        $result = $this->db->sql_query($sql);
        $order  = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$order) {
            return new JsonResponse(['error' => 'unknown invoice'], 404);
        }

        // Refresh from API
        $api = $this->client->get_invoice($invoice_id);
        if (!empty($api['_ok'])) {
            $update = ['status' => $api['status'] ?? $order['status'], 'updated_at' => time()];
            if (!empty($api['tx_hash'])) {
                $update['tx_hash'] = $api['tx_hash'];
            }
            $this->db->sql_query('UPDATE ' . NAKOPAY_ORDERS_TABLE . ' SET ' .
                $this->db->sql_build_array('UPDATE', $update) .
                " WHERE nakopay_invoice_id = '" . $this->db->sql_escape($invoice_id) . "'");
            $order = array_merge($order, $update);
        }

        return new JsonResponse([
            'status'        => $order['status'],
            'address'       => $order['address'],
            'amount_crypto' => $order['amount_crypto'],
            'coin'          => $order['coin'],
            'currency'      => $order['currency'],
            'amount_fiat'   => $order['amount_fiat'],
            'tx_hash'       => $order['tx_hash'] ?? null,
            'redirect'      => in_array($order['status'], ['paid', 'completed'], true)
                ? generate_board_url() . '/index.php'
                : null,
        ]);
    }

    private function helper_render(string $template, string $title): Response
    {
        $this->template->set_filenames(['body' => '@nakopay_payments/' . $template]);
        page_header($title);
        page_footer();
        // phpBB's page_footer() outputs and exits - this return is for type safety
        return new Response('');
    }
}
