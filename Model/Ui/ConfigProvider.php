<?php

namespace Teslaamazing\ApplePay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Teslaamazing\ApplePay\Gateway\Config;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'braintree_applepay';
    const VAULT_CODE = 'braintree_applepay_vault';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param ResolverInterface $resolver
     */
    public function __construct(
        Config $config,
        ResolverInterface $resolver
    )
    {
        $this->config = $config;
        $this->resolver = $resolver;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'title' => $this->config->getTitle(),
                    'merchantName' => $this->config->getMerchantName(),
                    'locale' => strtolower($this->resolver->getLocale()),
                    'vaultCode' => self::VAULT_CODE,
                    'skipOrderReview' => $this->config->isSkipOrderReview(),
                    'paymentIcon' => $this->config->getApplePayIcon(),
                ]
            ]
        ];
    }
}
