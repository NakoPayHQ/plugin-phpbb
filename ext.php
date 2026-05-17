<?php
/**
 * NakoPay phpBB extension entry point.
 *
 * @package nakopay/phpbb-extension
 */

namespace nakopay\payments;

class ext extends \phpbb\extension\base
{
    /**
     * Check whether the extension can be enabled.
     */
    public function is_enableable()
    {
        return phpbb_version_compare(PHPBB_VERSION, '3.3.0', '>=');
    }
}
