define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list',
    'Teslaamazing_ApplePay/js/adapter'
  ], function(Component, rendererList, applePay) {
    'use strict';

    var config = window.checkoutConfig.payment,
      type = 'braintree_applepay';

    if (config[type].isActive && applePay.canMakePayments()) {
      rendererList.push({
        type: type,
        component: 'Teslaamazing_ApplePay/js/view/payment/method-renderer/applepay'
      });
    }

    /** Add view logic here if needed */
    return Component.extend({});
  }
);
