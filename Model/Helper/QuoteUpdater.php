<?php

namespace Teslaamazing\ApplePay\Model\Helper;

use Magento\Braintree\Gateway\Config\PayPal\Config;
use Magento\Braintree\Observer\DataAssignObserver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Teslaamazing\ApplePay\Model\Ui\ConfigProvider;

class QuoteUpdater extends AbstractHelper
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * QuoteUpdater constructor.
     * @param Config $config
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Config $config,
        CartRepositoryInterface $quoteRepository
    )
    {
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Execute operation
     *
     * @param string $nonce
     * @param array $details
     * @param Quote $quote
     * @return void
     * @throws \InvalidArgumentException
     * @throws LocalizedException
     */
    public function execute($nonce, array $details, Quote $quote)
    {
        if (empty($nonce) || empty($details)) {
            throw new \InvalidArgumentException('The "nonce" and "details" fields does not exists');
        }

        $payment = $quote->getPayment();
        $payment->setMethod(ConfigProvider::CODE);
        $payment->setAdditionalInformation(DataAssignObserver::PAYMENT_METHOD_NONCE, $nonce);

        $this->updateQuote($quote, $details);
    }

    /**
     * Update quote data
     *
     * @param Quote $quote
     * @param array $details
     * @return void
     */
    private function updateQuote(Quote $quote, array $details)
    {
        $quote->setMayEditShippingAddress(true);
        $quote->setMayEditShippingMethod(true);

        $this->updateQuoteAddress($quote, $details);
        $this->disabledQuoteAddressValidation($quote);

        $quote->collectTotals();

        $this->quoteRepository->save($quote);
    }

    /**
     * Update quote address
     *
     * @param Quote $quote
     * @param array $details
     * @return void
     */
    private function updateQuoteAddress(Quote $quote, array $details)
    {
        if (!$quote->getIsVirtual()) {
            if (isset($details['shippingContact'])) {
                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setSaveInAddressBook(true);
                $shippingAddress->setCollectShippingRates(true);
                $this->updateAddressData($shippingAddress, $details['shippingContact']);
            }
        }

        $billingAddress = $quote->getBillingAddress();
        if ($this->config->isRequiredBillingAddress()) {
            if (isset($details['billingContact'])) {
                $this->updateAddressData($billingAddress, $details['billingContact']);
            }
        } else {
            if (isset($details['shippingContact'])) {
                $this->updateAddressData($billingAddress, $details['shippingContact']);
            }
        }
    }

    /**
     * Sets address data from exported address
     *
     * @param Address $address
     * @param array $addressData
     * @return void
     */
    private function updateAddressData(Address $address, array $addressData)
    {
        if (isset($addressData['givenName'])) {
            $address->setFirstname($addressData['givenName']);
        }
        if (isset($addressData['familyName'])) {
            $address->setLastname($addressData['familyName']);
        }
        if (isset($addressData['emailAddress'])) {
            $address->setEmail($addressData['emailAddress']);
        }
        if (isset($addressData['addressLines'])) {
            $address->setStreet((array)$addressData['addressLines']);
        }
        if (isset($addressData['locality'])) {
            $address->setCity($addressData['locality']);
        }
        if (isset($addressData['administrativeArea'])) {
            $address->setRegion($addressData['administrativeArea']);
        }
        if (isset($addressData['countryCode'])) {
            $address->setCountryId(strtoupper($addressData['countryCode']));
        }
        if (isset($addressData['postalCode'])) {
            $address->setPostcode($addressData['postalCode']);
        }
        if (isset($addressData['phoneNumber'])) {
            $address->setTelephone($addressData['phoneNumber']);
        }

        $address->setCustomerAddressId(0);
        $address->save();
    }
}
