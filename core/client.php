<?php
/**
 * NakoPay API client for phpBB.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\payments\core;

class client
{
    const VERSION       = '0.1.0';
    const BASE_URL      = 'https://daslrxpkbkqrbnjwouiq.supabase.co/functions/v1/';
    const SIG_TOLERANCE = 300;

    /** @var \phpbb\config\config */
    protected $config;

    public function __construct(\phpbb\config\config $config)
    {
        $this->config = $config;
    }

    public function get_version(): string
    {
        return self::VERSION;
    }

    public function get_api_key(): string
    {
        $key = trim((string) $this->config['nakopay_api_key']);
        if ($key === '') {
            throw new \RuntimeException('NakoPay API key is not configured.');
        }
        return $key;
    }

    public function get_webhook_secret(): string
    {
        return trim((string) $this->config['nakopay_webhook_secret']);
    }

    /* ----------------------------------------------------------------- HTTP */

    public function request(string $method, string $path, ?array $body = null): array
    {
        $url = self::BASE_URL . ltrim($path, '/');
        $ch  = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->get_api_key(),
            'Accept: application/json',
            'User-Agent: NakoPay-phpBB/' . self::VERSION,
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body !== null ? json_encode($body) : null,
        ]);
        $raw    = curl_exec($ch);
        $err    = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['_ok' => false, '_status' => 0, '_error' => $err ?: 'network error'];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['_ok' => false, '_status' => $status, '_error' => 'invalid json', '_raw' => $raw];
        }
        $decoded['_ok']     = $status >= 200 && $status < 300;
        $decoded['_status'] = $status;
        return $decoded;
    }

    public function create_invoice(array $args): array
    {
        return $this->request('POST', 'invoices-create', [
            'amount'         => (string) $args['amount'],
            'currency'       => strtoupper((string) ($args['currency'] ?? 'USD')),
            'coin'           => strtoupper((string) ($args['coin'] ?? 'BTC')),
            'description'    => (string) ($args['description'] ?? 'phpBB payment'),
            'customer_email' => (string) ($args['customer_email'] ?? ''),
            'metadata'       => array_filter([
                'phpbb_user_id' => $args['phpbb_user_id'] ?? null,
                'phpbb_item_id' => $args['phpbb_item_id'] ?? null,
                'source'        => 'phpbb',
            ], fn($v) => $v !== null && $v !== ''),
        ]);
    }

    public function get_invoice(string $id): array
    {
        return $this->request('GET', 'invoices-get?id=' . rawurlencode($id));
    }

    /* ----------------------------------------------------------- webhook sig */

    public function verify_webhook(string $raw_body, string $sig_header, ?string $secret_override = null): bool
    {
        $secret = $secret_override ?? $this->get_webhook_secret();
        if ($secret === '' || $sig_header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $sig_header) as $kv) {
            $kv = trim($kv);
            if ($kv === '' || strpos($kv, '=') === false) continue;
            [$k, $v] = explode('=', $kv, 2);
            $parts[trim($k)] = trim($v);
        }
        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $t = (int) $parts['t'];
        if (abs(time() - $t) > self::SIG_TOLERANCE) {
            return false;
        }

        $expected = hash_hmac('sha256', $t . '.' . $raw_body, $secret);
        return hash_equals($expected, $parts['v1']);
    }
}
