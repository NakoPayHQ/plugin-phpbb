# Changelog

## 0.1.0 - 2026-05-08

- Initial release.
- Full payment flow: Pay with Bitcoin button -> NakoPay invoice -> QR + address checkout -> 5s status polling -> automatic redirect on paid.
- Signed webhook receiver (X-NakoPay-Signature, HMAC-SHA256, 5-minute replay window).
- ACP settings page (API key, webhook secret, currency, test mode).
- Local nakopay_orders table via phpBB migration for idempotency and reuse of in-flight orders.
- Optional group membership activation on successful payment.
