<?php

namespace Teslaamazing\ApplePay\Observer;

use Magento\Catalog\Block\ShortcutButtons;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Teslaamazing\ApplePay\Block\Button;

class AddApplePayShortcuts implements ObserverInterface
{
    /**
     * Add Braintree ApplePay shortcut buttons
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Remove button from catalog pages
        if ($observer->getData('is_catalog_product')) {
            return;
        }

        /** @var ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        /** @var Button $shortcut */
        $shortcut = $shortcutButtons->getLayout()->createBlock(Button::class);

        $shortcutButtons->addShortcut($shortcut);
    }
}
