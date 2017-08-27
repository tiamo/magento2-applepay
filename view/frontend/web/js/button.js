define([
  'jquery',
  'underscore',
  'uiComponent',
  'Magento_Customer/js/model/authentication-popup',
  'Magento_Customer/js/customer-data',
  'Magento_Checkout/js/model/full-screen-loader',
  'Teslaamazing_ApplePay/js/form-builder',
  'Teslaamazing_ApplePay/js/adapter'
], function($, _, Component, authenticationPopup, customerData, fullScreenLoader, formBuilder, applePay) {
  'use strict';

  return Component.extend({

    defaults: {
      displayName: null,
      clientToken: null,
      clientConfig: {}
    },

    /**
     * @returns {exports}
     */
    initialize: function() {
      this._super()
      .initComponent();

      return this;
    },

    /**
     * @returns {exports}
     */
    initComponent: function() {

      var $button = $('#' + this.id);
      var merchantName = this.displayName;
      var actionSuccess = this.actionSuccess;

      if (applePay.canMakePayments()) {

        applePay.configure({
          clientToken: this.clientToken,
          displayName: merchantName,
          onPaymentAuthorized: function(payload, event) {
            $('body').trigger('processStart');
            if (!payload.details) {
              payload.details = {};
            }
            try {
              // add shipping address to payload
              if (event.payment.shippingContact) {
                payload.details.shippingContact = event.payment.shippingContact;
              }
              if (event.payment.billingContact) {
                payload.details.billingContact = event.payment.billingContact;
              }
            } catch (e) {
            }
            formBuilder.build({
              action: actionSuccess,
              fields: {
                result: JSON.stringify(payload)
              }
            }).submit();
          }
        });

        fullScreenLoader.stopLoader(true);
        fullScreenLoader.startLoader();

        applePay.init(function() {

          fullScreenLoader.stopLoader();

          // show Apple Pay button
          $button.parent().show();
          $button.off('click').on('click', function(event) {

            var cart = customerData.get('cart'),
              customer = customerData.get('customer');

            event.preventDefault();

            if (!customer().firstname && cart().isGuestCheckoutAllowed === false) {
              authenticationPopup.showModal();
              return false;
            }

            applePay.process({
              total: {
                label: merchantName,
                amount: $button.data('amount')
              },
              currencyCode: $button.data('currency')
            });

          });
        });
      }

      return this;
    }

    // getCode: function() {
    //   return 'braintree_applepay';
    // },
    //
    // /**
    //  * Check if need to skip order review
    //  * @returns {Boolean}
    //  */
    // getClientToken: function() {
    //   return window.checkoutConfig.payment['braintree'].clientToken;
    // }


  });

});
