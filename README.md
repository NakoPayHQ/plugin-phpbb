# NakoPay for phpBB

Accept Bitcoin payments on your phpBB forum through [NakoPay](https://nakopay.com).

- Paid memberships, donations, premium group access.
- Stripe-style API: invoices created server-side, polled and webhook-notified.
- Signed webhooks (HMAC-SHA256, 5-minute replay window).
- Clean phpBB extension architecture - no hacks, no core modifications.

## Requirements

- phpBB 3.3.0+
- PHP 8.0+
- cURL extension enabled
- A NakoPay account (free) - <https://nakopay.com/dashboard/api-keys>

## Download

| # | Source | When to use |
|---|--------|-------------|
| 1 | **phpBB Extensions Database** - <https://www.phpbb.com/customise/db/extensions-36> | *Listing pending review - use option 2 in the meantime.* |
| 2 | **GitHub Releases zip** - <https://github.com/NakoPayHQ/plugin-phpbb/releases/latest/download/nakopay-phpbb.zip> | Available today. Download `nakopay-phpbb.zip`. |
| 3 | **Build from source** | See bottom of this file. |

## Install

1. Download `nakopay-phpbb.zip` and unzip it.

2. Upload the `nakopay/btcpay` folder into your phpBB installation's `ext/` directory so the final path is:

   ```
   <phpbb-root>/ext/nakopay/btcpay/
   ```

   Use SFTP (FileZilla, WinSCP, Cyberduck) or your hosting panel's File Manager. Make sure files keep `0644` permissions and folders `0755`.

3. In phpBB admin go to **Customise -> Manage extensions**, find **NakoPay Bitcoin Payments**, click **Enable**.

4. Go to **Extensions -> NakoPay Bitcoin Payments -> Settings** and paste:
   - **API Key** - `sk_live_...` (or `sk_test_...` for testing). Get one at <https://nakopay.com/dashboard/api-keys>.
   - **Webhook Signing Secret** - shown once when you create a webhook endpoint in your NakoPay dashboard.
   - **Currency** - your preferred fiat currency (e.g. USD, EUR, GBP).

5. In your NakoPay dashboard, **Settings -> Webhooks -> Add endpoint**, paste your webhook URL:
   `https://your-forum.example/nakopay/webhook`

   Subscribe to `invoice.paid`, `invoice.completed`, `invoice.expired`, `invoice.cancelled`. Save and copy the signing secret back into step 4.

## How it works

- A "Pay with Bitcoin" button posts to `/nakopay/pay` with the item details (amount, description, group ID).
- The controller creates a NakoPay invoice and renders a checkout page with QR code + Bitcoin address + amount.
- The checkout page polls `/nakopay/poll/{invoice_id}` every 5s. When the invoice flips to `paid`, the user is redirected back.
- The webhook receiver verifies the signature and (optionally) adds the user to a phpBB group for premium access.

## Use cases

- **Paid group access** - charge users to join premium groups (pass `group_id` in the payment form).
- **Donations** - accept Bitcoin donations on a dedicated page.
- **Premium content** - gate content behind a one-time Bitcoin payment.
- **Account upgrades** - sell account perks (custom titles, avatar permissions, etc.).

## Test mode

Use a `sk_test_...` key. Test invoices accept BTC testnet sends - grab funds from any testnet faucet.

## Uninstall

1. phpBB admin -> **Customise -> Manage extensions -> NakoPay Bitcoin Payments -> Disable**, then **Delete data**.
2. Via SFTP, delete `ext/nakopay/btcpay/`.

## Files

| Path | Purpose |
|------|---------|
| `ext.php` | Extension entry point. |
| `composer.json` | Extension metadata. |
| `config/services.yml` | Dependency injection definitions. |
| `config/routing.yml` | URL routes (webhook, payment, poll). |
| `core/client.php` | NakoPay API client + signature verification. |
| `controller/admin_controller.php` | ACP settings controller. |
| `controller/webhook_controller.php` | Webhook receiver. |
| `controller/payment_controller.php` | Customer checkout + polling. |
| `event/listener.php` | Template event listener. |
| `acp/main_info.php` | ACP module info. |
| `acp/main_module.php` | ACP module handler. |
| `migrations/install_nakopay.php` | DB migration (orders table + config). |
| `language/en/common.php` | English language strings. |
| `styles/prosilver/template/acp_nakopay_settings.html` | Admin settings template. |
| `styles/prosilver/template/checkout_body.html` | Customer checkout template. |

## Build from source

```bash
git clone https://github.com/NakoPayHQ/plugin-phpbb.git
cd plugin-phpbb
zip -r nakopay-phpbb.zip . -x "*.git*" "tests/*" "*.DS_Store"
```

## Support

- Issues: <https://github.com/NakoPayHQ/plugin-phpbb/issues>
- Email: support@nakopay.com

## About phpBB

[phpBB](https://www.phpbb.com/) - the most widely used open-source forum solution. Visit their website to learn more about the platform and its features.

## License

MIT.
