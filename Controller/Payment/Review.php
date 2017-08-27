<?php

namespace Teslaamazing\ApplePay\Controller\Payment;

use Magento\Braintree\Observer\DataAssignObserver;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Teslaamazing\ApplePay\Gateway\Config;
use Teslaamazing\ApplePay\Model\Helper\QuoteUpdater;

class Review extends AbstractAction
{
    /**
     * @var QuoteUpdater
     */
    private $quoteUpdater;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Config $config
     * @param Session $checkoutSession
     * @param QuoteUpdater $quoteUpdater
     */
    public function __construct(
        Context $context,
        Config $config,
        Session $checkoutSession,
        QuoteUpdater $quoteUpdater
    )
    {
        parent::__construct($context, $config, $checkoutSession);
        $this->quoteUpdater = $quoteUpdater;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_objectManager->get('Psr\Log\LoggerInterface');
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $requestData = json_decode($this->getRequest()->getPostValue('result', '{}'), true);

//        $requestData = [
//            'nonce' => '95c76328-863f-0c74-522d-c8584274398a',
//            'details' => [
//                "cardType" => "MasterCard",
//                "cardholderName" => null,
//                "paymentInstrumentName" => "MasterCard 1234",
//                "dpanLastTwo" => "03",
//                'shippingContact' => [
//                    "addressLines" => [
//                        "Pushkina 80"
//                    ],
//                    "administrativeArea" => "",
//                    "country" => "Ukraine",
//                    "countryCode" => "ua",
//                    "emailAddress" => "vk.tiamo@gmail.com",
//                    "familyName" => "Korniienko",
//                    "givenName" => "Vladyslav",
//                    "locality" => "Kyiv",
//                    "phoneNumber" => "+380951997704",
//                    "postalCode" => "27013"
//                ]
//            ],
//        ];
//        $this->getLogger()->debug('ApplePay Request', $requestData);

        $quote = $this->checkoutSession->getQuote();

        try {
            $this->validateQuote($quote);

            if ($this->validateRequestData($requestData)) {
                $this->quoteUpdater->execute(
                    $requestData['nonce'],
                    $requestData['details'],
                    $quote
                );
            } elseif (!$quote->getPayment()->getAdditionalInformation(DataAssignObserver::PAYMENT_METHOD_NONCE)) {
                throw new LocalizedException(__('We can\'t initialize checkout.'));
            }

            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

            /** @var \Magento\Braintree\Block\Paypal\Checkout\Review $reviewBlock */
            $reviewBlock = $resultPage->getLayout()->getBlock('braintree.applepay.review');
            $reviewBlock->setQuote($quote);
            $reviewBlock->getChildBlock('shipping_method')->setData('quote', $quote);

            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }

    /**
     * @param array $requestData
     * @return boolean
     */
    private function validateRequestData(array $requestData)
    {
        return !empty($requestData['nonce']) && !empty($requestData['details']);
    }
}
