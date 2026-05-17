<?php
/**
 * ACP settings controller for NakoPay phpBB extension.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\payments\controller;

class admin_controller
{
    /** @var \phpbb\config\config */
    protected $config;
    /** @var \phpbb\request\request */
    protected $request;
    /** @var \phpbb\template\template */
    protected $template;
    /** @var \phpbb\user */
    protected $user;
    /** @var \phpbb\language\language */
    protected $language;
    /** @var \nakopay\payments\core\client */
    protected $client;

    protected $u_action;

    public function __construct(
        \phpbb\config\config $config,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        \phpbb\language\language $language,
        \nakopay\payments\core\client $client
    ) {
        $this->config   = $config;
        $this->request  = $request;
        $this->template = $template;
        $this->user     = $user;
        $this->language = $language;
        $this->client   = $client;
    }

    public function set_page_url(string $u_action): void
    {
        $this->u_action = $u_action;
    }

    public function display_settings(): void
    {
        $this->language->add_lang('common', 'nakopay/payments');

        if ($this->request->is_set_post('submit')) {
            if (!check_form_key('nakopay_payments')) {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }

            $this->config->set('nakopay_api_key', trim($this->request->variable('nakopay_api_key', '')));
            $this->config->set('nakopay_webhook_secret', trim($this->request->variable('nakopay_webhook_secret', '')));
            $this->config->set('nakopay_currency', strtoupper(trim($this->request->variable('nakopay_currency', 'USD'))));
            $this->config->set('nakopay_test_mode', $this->request->variable('nakopay_test_mode', 0));

            trigger_error($this->language->lang('ACP_NAKOPAY_SAVED') . adm_back_link($this->u_action));
        }

        add_form_key('nakopay_payments');

        $this->template->assign_vars([
            'U_ACTION'               => $this->u_action,
            'NAKOPAY_API_KEY'        => $this->config['nakopay_api_key'] ?? '',
            'NAKOPAY_WEBHOOK_SECRET' => $this->config['nakopay_webhook_secret'] ?? '',
            'NAKOPAY_CURRENCY'       => $this->config['nakopay_currency'] ?? 'USD',
            'NAKOPAY_TEST_MODE'      => !empty($this->config['nakopay_test_mode']),
            'NAKOPAY_VERSION'        => $this->client->get_version(),
        ]);
    }
}
