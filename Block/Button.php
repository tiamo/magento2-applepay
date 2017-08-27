<?php

namespace Teslaamazing\ApplePay\Block;

use Magento\Braintree\Model\Ui\ConfigProvider;
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\MethodInterface;
use Teslaamazing\ApplePay\Gateway\Config;

class Button extends Template implements ShortcutInterface
{
    const ALIAS_ELEMENT_INDEX = 'alias';
    const BUTTON_ELEMENT_INDEX = 'button_id';

    /**
     * @var string
     */
    protected $_template = 'button.phtml';

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var MethodInterface
     */
    protected $payment;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Button constructor.
     * @param Context $context
     * @param ResolverInterface $localeResolver
     * @param PriceCurrencyInterface $priceCurrency
     * @param MethodInterface $payment
     * @param ConfigProvider $configProvider
     * @param Session $checkoutSession
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        PriceCurrencyInterface $priceCurrency,
        MethodInterface $payment,
        ConfigProvider $configProvider,
        Session $checkoutSession,
        Config $config,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->localeResolver = $localeResolver;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->configProvider = $configProvider;
        $this->payment = $payment;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * @return string
     */
    public function getContainerId()
    {
        return $this->getData(self::BUTTON_ELEMENT_INDEX);
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return strtolower($this->localeResolver->getLocale());
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->checkoutSession->getQuote()->getCurrency()->getGlobalCurrencyCode();
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->priceCurrency->convertAndRound(
            $this->checkoutSession->getQuote()->getBaseGrandTotal()
        );
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->payment->isAvailable($this->checkoutSession->getQuote()) &&
            $this->config->isDisplayShoppingCart();
    }

    /**
     * @return string
     */
    public function getMerchantName()
    {
        return $this->config->getMerchantName();
    }

    /**
     * @return string|null
     */
    public function getClientToken()
    {
        return $this->configProvider->getClientToken();
    }

    /**
     * @return string
     */
    public function getActionSuccess()
    {
        return $this->getUrl('applepay/payment/review', ['_secure' => true]);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->isActive()) {
            return parent::_toHtml();
        }

        return '';
    }
}
