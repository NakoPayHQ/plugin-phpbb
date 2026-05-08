<?php
/**
 * ACP module for NakoPay phpBB extension.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\btcpay\acp;

class main_module
{
    public $page_title;
    public $tpl_name;
    public $u_action;

    public function main(string $id, string $mode): void
    {
        global $phpbb_container;

        $this->tpl_name   = 'acp_nakopay_settings';
        $this->page_title = 'ACP_NAKOPAY_TITLE';

        /** @var \nakopay\btcpay\controller\admin_controller $controller */
        $controller = $phpbb_container->get('nakopay.btcpay.admin_controller');
        $controller->set_page_url($this->u_action);
        $controller->display_settings();
    }
}
