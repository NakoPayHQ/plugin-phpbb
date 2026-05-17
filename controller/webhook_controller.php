<?php
/**
 * Webhook receiver for NakoPay phpBB extension.
 *
 * POST /nakopay/webhook
 *
 * Verifies X-NakoPay-Signature HMAC, dispatches on event type, updates
 * the local nakopay_orders table, and activates user groups when paid.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\payments\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class webhook_controller
{
    /** @var \phpbb\config\config */
    protected $config;
    /** @var \phpbb\request\request */
    protected $request;
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;
    /** @var \nakopay\payments\core\client */
    protected $client;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\db\driver\driver_interface $db,
        \nakopay\payments\core\client $client
    ) {
        $this->config  = $config;
        $this->request = $request;
        $this->db      = $db;
        $this->client  = $client;
    }

    public function handle(): JsonResponse
    {
        $raw_body = file_get_contents('php://input') ?: '';
        $sig      = $this->request->server('HTTP_X_NAKOPAY_SIGNATURE', '');

        if (!$this->client->verify_webhook($raw_body, $sig)) {
            return new JsonResponse(['error' => 'invalid signature'], 401);
        }

        $payload = json_decode($raw_body, true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'invalid json'], 400);
        }

        $type   = (string) ($payload['type'] ?? $payload['event'] ?? '');
        $data   = $payload['data']['object'] ?? $payload['data'] ?? $payload['invoice'] ?? $payload;
        $inv_id = (string) ($data['id'] ?? '');
        $status = (string) ($data['status'] ?? '');
        $tx_hash = (string) ($data['tx_hash'] ?? '');

        // Update local order status
        if ($inv_id && $status) {
            $update = ['status' => $status, 'updated_at' => time()];
            if ($tx_hash !== '') {
                $update['tx_hash'] = $tx_hash;
            }
            $this->db->sql_query(
                'UPDATE ' . NAKOPAY_ORDERS_TABLE . ' SET ' .
                $this->db->sql_build_array('UPDATE', $update) .
                " WHERE nakopay_invoice_id = '" . $this->db->sql_escape($inv_id) . "'"
            );
        }

        switch ($type) {
            case 'invoice.paid':
            case 'invoice.completed':
                // Look up the order and activate group membership
                $sql = 'SELECT * FROM ' . NAKOPAY_ORDERS_TABLE .
                       " WHERE nakopay_invoice_id = '" . $this->db->sql_escape($inv_id) . "'";
                $result = $this->db->sql_query($sql);
                $order  = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);

                if ($order && !empty($order['phpbb_group_id'])) {
                    // Add user to the paid group
                    group_user_add((int) $order['phpbb_group_id'], [(int) $order['phpbb_user_id']]);
                }
                return new JsonResponse(['received' => true]);

            case 'invoice.expired':
            case 'invoice.cancelled':
                return new JsonResponse(['received' => true]);

            default:
                return new JsonResponse(['received' => true]);
        }
    }
}
