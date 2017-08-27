define([
  'jquery',
  'underscore',
  'Magento_Checkout/js/view/payment/default',
  'Teslaamazing_ApplePay/js/adapter',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/full-screen-loader',
  'Magento_Checkout/js/model/payment/additional-validators',
  'Magento_Vault/js/view/payment/vault-enabler',
  'Magento_Checkout/js/action/create-billing-address'
], function($,
            _,
            Component,
            applePay,
            quote,
            fullScreenLoader,
            additionalValidators,
            VaultEnabler,
            createBillingAddress) {
  'use strict';

  return Component.extend({
    defaults: {
      template: 'Teslaamazing_ApplePay/payment/applepay',
      code: 'braintree_applepay',
      active: false,
      paymentMethodNonce: null,
      grandTotalAmount: null,
      isReviewRequired: false,
      customerEmail: null,

      /**
       * Additional payment data
       *
       * {Object}
       */
      additionalData: {},

      imports: {
        onActiveChange: 'active'
      }
    },

    /**
     * Set list of observable attributes
     * @returns {exports}
     */
    initObservable: function() {

      var self = this;

      this._super().observe(['active', 'isReviewRequired', 'customerEmail']);

      this.vaultEnabler = new VaultEnabler();
      this.vaultEnabler.setPaymentCode(this.getVaultCode());
      this.vaultEnabler.isActivePaymentTokenEnabler.subscribe(function() {
        self.onVaultPaymentTokenEnablerChange();
      });

      this.grandTotalAmount = quote.totals()['base_grand_total'];

      quote.totals.subscribe(function() {
        if (self.grandTotalAmount !== quote.totals()['base_grand_total']) {
          self.grandTotalAmount = quote.totals()['base_grand_total'];
        }
      });

      // for each component initialization need update property
      this.isReviewRequired(false);
      this.initApplePay();

      return this;
    },

    /**
     * Triggers when payment method change
     * @param {Boolean} isActive
     */
    onActiveChange: function (isActive) {
      if (!isActive) {
        return;
      }
      this.initApplePay();
    },

    /**
     * Init config
     */
    initApplePay: function() {

      applePay.configure({
        clientToken: this.getClientToken(),
        displayName: this.getMerchantName(),
        onPaymentAuthorized: function(payload, event) {
          this.enableButton();
          this.beforePlaceOrder(payload);
        }.bind(this)
      });

      this.disableButton();
      applePay.init(function() {

        this.enableButton();

        // this.beforePlaceOrder({
        //   nonce: '1234-1234-1234',
        //   details: {
        //     emailAddress: 'test@test.com'
        //   }
        // });

      }.bind(this));

    },

    /**
     * Get payment name
     *
     * @returns {String}
     */
    getCode: function() {
      return this.code;
    },

    /**
     * Get payment title
     *
     * @returns {String}
     */
    getTitle: function() {
      return window.checkoutConfig.payment[this.getCode()].title;
    },

    /**
     * Get client token
     *
     * @returns {String}
     */
    getClientToken: function() {
      return window.checkoutConfig.payment['braintree'].clientToken;
    },

    /**
     * Check if payment is active
     *
     * @returns {Boolean}
     */
    isActive: function() {
      var active = this.getCode() === this.isChecked();
      this.active(active);
      return active;
    },

    /**
     * Set payment nonce
     * @param {String} paymentMethodNonce
     */
    setPaymentMethodNonce: function(paymentMethodNonce) {
      this.paymentMethodNonce = paymentMethodNonce;
    },

    /**
     * Update quote billing address
     * @param {Object}customer
     * @param {Object}address
     */
    setBillingAddress: function(customer, address) {
      var billingAddress = {
        street: address.addressLines,
        city: address.locality,
        postcode: address.postalCode,
        countryId: address.countryCode,
        email: customer.emailAddress,
        firstname: customer.givenName,
        lastname: customer.familyName,
        telephone: customer.phoneNumber
      };

      billingAddress['region'] = address.administrativeArea;
      billingAddress = createBillingAddress(billingAddress);
      quote.billingAddress(billingAddress);
    },

    /**
     * Prepare data to place order
     * @param {Object} data
     */
    beforePlaceOrder: function(data) {
      this.setPaymentMethodNonce(data.nonce);

      if (quote.billingAddress() === null && typeof data.details.billingContact !== 'undefined') {
        this.setBillingAddress(data.details, data.details.billingContact);
      }

      if (this.isSkipOrderReview()) {
        this.placeOrder();
      } else {
        this.customerEmail(data.details.emailAddress);
        this.isReviewRequired(true);
      }
    },

    /**
     * Get shipping address
     * @returns {Object}
     */
    getShippingContact: function() {
      var address = quote.shippingAddress();

      if (address.postcode === null) {
        return {};
      }

      return {
        givenName: address.firstname,
        familyName: address.lastname,
        addressLines: address.street,
        countryCode: address.countryId,
        locality: address.city,
        administrativeArea: address.region,
        postalCode: address.postcode,
        phoneNumber: address.telephone
      };
    },

    /**
     * Get merchant name
     * @returns {String}
     */
    getMerchantName: function() {
      return window.checkoutConfig.payment[this.getCode()].merchantName;
    },

    /**
     * Get data
     * @returns {Object}
     */
    getData: function() {
      var data = {
        'method': this.getCode(),
        'additional_data': {
          'payment_method_nonce': this.paymentMethodNonce
        }
      };

      data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

      this.vaultEnabler.visitAdditionalData(data);

      return data;
    },

    /**
     * @returns {String}
     */
    getVaultCode: function() {
      return window.checkoutConfig.payment[this.getCode()].vaultCode;
    },

    /**
     * Check if need to skip order review
     * @returns {Boolean}
     */
    isSkipOrderReview: function() {
      return window.checkoutConfig.payment[this.getCode()].skipOrderReview;
    },

    /**
     * Returns payment icon url
     * @returns {String}
     */
    getPaymentIconUrl: function() {
      var icon = window.checkoutConfig.payment[this.getCode()].paymentIcon;
      if (icon && icon.url) {
        return icon.url;
      }
    },

    /**
     * Checks if vault is active
     * @returns {Boolean}
     */
    isActiveVault: function() {
      return this.vaultEnabler.isVaultEnabled() && this.vaultEnabler.isActivePaymentTokenEnabler();
    },

    /**
     * Disable submit button
     */
    disableButton: function() {
      // stop any previous shown loaders
      fullScreenLoader.stopLoader(true);
      fullScreenLoader.startLoader();
      $('[data-button="applepay-continue"]').attr('disabled', 'disabled');
    },

    /**
     * Enable submit button
     */
    enableButton: function() {
      $('[data-button="applepay-continue"]').removeAttr('disabled');
      fullScreenLoader.stopLoader();
    },

    /**
     * Triggers when customer click "Continue to PayPal" button
     */
    payWithApplePay: function() {
      if (additionalValidators.validate()) {
        var totals = quote.totals();
        applePay.process({
          total: {
            label: this.getMerchantName(),
            amount: this.grandTotalAmount
          },
          currencyCode: totals['base_currency_code'],
          shippingContact: this.getShippingContact()
        });
      }
    },

    /**
     * Get button title
     * @returns {String}
     */
    getButtonTitle: function() {
      return this.isSkipOrderReview() ? 'Pay with Apple Pay' : 'Continue to Apple Pay';
    },

    /**
     * Get button id
     * @returns {String}
     */
    getButtonId: function() {
      return this.getCode() + (this.isSkipOrderReview() ? '_pay_with' : '_continue_to');
    }
  });
});
