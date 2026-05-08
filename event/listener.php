<?php
/**
 * Event listener for NakoPay phpBB extension.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\btcpay\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
    /** @var \phpbb\config\config */
    protected $config;
    /** @var \phpbb\request\request */
    protected $request;
    /** @var \phpbb\template\template */
    protected $template;
    /** @var \phpbb\user */
    protected $user;
    /** @var \nakopay\btcpay\core\client */
    protected $client;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \nakopay\btcpay\core\client $client
    ) {
        $this->config   = $config;
        $this->request  = $request;
        $this->template = $template;
        $this->user     = $user;
        $this->client   = $client;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'core.page_header' => 'on_page_header',
        ];
    }

    /**
     * Inject NakoPay availability flag into templates.
     */
    public function on_page_header($event): void
    {
        $api_key = trim((string) $this->config['nakopay_api_key']);
        $this->template->assign_vars([
            'NAKOPAY_ENABLED' => $api_key !== '',
        ]);
    }
}
