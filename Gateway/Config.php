<?php

namespace Teslaamazing\ApplePay\Gateway;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\CcConfig;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const KEY_TITLE = 'title';
    const KEY_DISPLAY_ON_SHOPPING_CART = 'display_on_shopping_cart';
    const KEY_MERCHANT_NAME_OVERRIDE = 'merchant_name_override';
    const KEY_REQUIRE_BILLING_ADDRESS = 'require_billing_address';

    private $scopeConfig;
    private $ccConfig;
    private $icon = [];

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param CcConfig $ccConfig
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CcConfig $ccConfig,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    )
    {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->scopeConfig = $scopeConfig;
        $this->ccConfig = $ccConfig;
    }

    /**
     * Get Payment configuration status
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getValue(self::KEY_ACTIVE);
    }

    /**
     * @return bool
     */
    public function isDisplayShoppingCart()
    {
        return (bool)$this->getValue(self::KEY_DISPLAY_ON_SHOPPING_CART);
    }

    /**
     * Get merchant name to display in ApplePay popup
     *
     * @return string
     */
    public function getMerchantName()
    {
        $merchantName = $this->getValue(self::KEY_MERCHANT_NAME_OVERRIDE);
        if (empty($this->getValue(self::KEY_MERCHANT_NAME_OVERRIDE))) {
            return $this->scopeConfig->getValue(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME);
        }
        return $merchantName;
    }

    /**
     * Is billing address can be required
     *
     * @return string
     */
    public function isRequiredBillingAddress()
    {
        return $this->getValue(self::KEY_REQUIRE_BILLING_ADDRESS);
    }

    /**
     * Is need to skip order review
     * @return bool
     */
    public function isSkipOrderReview()
    {
        return (bool)$this->getValue('skip_order_review');
    }

    /**
     * Get title of payment
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getValue(self::KEY_TITLE);
    }

    /**
     * Get Apple Pay icon
     * @return array
     */
    public function getApplePayIcon()
    {
        if (empty($this->icon)) {
            $asset = $this->ccConfig->createAsset('Teslaamazing_ApplePay::images/apple-pay.png');
            list($width, $height) = getimagesize($asset->getSourceFile());
            $this->icon = [
                'url' => $asset->getUrl(),
                'width' => $width,
                'height' => $height
            ];
        }

        return $this->icon;
    }
}
