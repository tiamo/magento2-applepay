<?php

namespace Teslaamazing\ApplePay\Gateway\Response;

use Braintree\Transaction\ApplePayCardDetails;
use Magento\Braintree\Gateway\Config\Config;
use Magento\Braintree\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class DetailsHandler implements HandlerInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * Constructor
     *
     * @param Config $config
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        Config $config,
        SubjectReader $subjectReader
    )
    {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $transaction = $this->subjectReader->readTransaction($response);

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        /** @var ApplePayCardDetails $details */
        $details = $transaction->applePayCardDetails;

        try {
            $payment->setCcExpMonth($details->expirationMonth);
            $payment->setCcExpYear($details->expirationYear);
            $payment->setCcType($details->cardType);
//            $payment->setCcType($this->getCreditCardType($details->cardType));

            // set card details to additional info
            $payment->setAdditionalInformation(OrderPaymentInterface::CC_TYPE, $details->cardType);
            if (isset($details->paymentInstrumentName)) {
                $payment->setAdditionalInformation('paymentInstrumentName', $details->paymentInstrumentName);
            }
            if (isset($details->sourceDescription)) {
                $payment->setAdditionalInformation('sourceDescription', $details->sourceDescription);
            }
            if (isset($details->cardholderName)) {
                $payment->setAdditionalInformation('cardholderName', $details->cardholderName);
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * Get type of credit card mapped from Braintree
     *
     * @param string $type
     * @return array
     */
    private function getCreditCardType($type)
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCctypesMapper();

        return $mapper[$replaced];
    }
}
