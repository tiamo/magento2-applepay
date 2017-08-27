<?php

namespace Teslaamazing\ApplePay\Model\Helper;

use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;

class ShippingMethodUpdater extends AbstractHelper
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * ShippingMethodUpdater constructor.
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Execute operation
     *
     * @param string $shippingMethod
     * @param Quote $quote
     * @return void
     * @throws \InvalidArgumentException
     */
    public function execute($shippingMethod, Quote $quote)
    {
        if (empty($shippingMethod)) {
            throw new \InvalidArgumentException('The "shippingMethod" field does not exists.');
        }

        if (!$quote->getIsVirtual()) {

            $shippingAddress = $quote->getShippingAddress();
            if ($shippingMethod !== $shippingAddress->getShippingMethod()) {

                $this->disabledQuoteAddressValidation($quote);

                $shippingAddress->setShippingMethod($shippingMethod);
                $shippingAddress->setCollectShippingRates(true);

                $quote->collectTotals();

                $this->quoteRepository->save($quote);
            }
        }
    }
}
