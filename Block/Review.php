<?php

namespace Teslaamazing\ApplePay\Block;

use Magento\Paypal\Block\Express;

class Review extends Express\Review
{
    /**
     * @var string
     */
    protected $_template = 'review.phtml';

    /**
     * Controller path
     *
     * @var string
     */
    protected $_controllerPath = 'applepay/payment';

    /**
     * @return string
     */
    public function getEditUrl()
    {
        return $this->getUrl("checkout");
    }

    /**
     * @return bool
     */
    public function getCanEditShippingAddress()
    {
        return $this->_quote->getMayEditShippingAddress();
    }
}
