<?php
/**
 * ACP module for NakoPay phpBB extension.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\payments\acp;

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

        /** @var \nakopay\payments\controller\admin_controller $controller */
        $controller = $phpbb_container->get('nakopay.payments.admin_controller');
        $controller->set_page_url($this->u_action);
        $controller->display_settings();
    }
}
