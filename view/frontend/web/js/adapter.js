define([
  'underscore',
  'braintreeClient',
  'braintreeApplePay'
], function(_, braintree, applepay) {
  'use strict';

  /**
   * @returns {boolean}
   */
  function isFunction(fn) {
    return typeof fn === 'function';
  }

  var base = {
    clientInstance: null,
    applePayInstance: null,
    /**
     * Example:
     * {
     *  displayName: 'Apple Pay',
     *  onValidateMerchant: function() {},
     *  onShippingContactSelected: function(event, applePayInstance, session) {},
     *  onShippingMethodSelected: function(event, applePayInstance, session) {},
     *  onPaymentMethodSelected: function(event, applePayInstance, session) {},
     *  onPaymentAuthorized: function() {payload, event},
     * }
     */
    config: {
      clientToken: null,
      payment: {
        currencyCode: 'USD',
        supportedNetworks: ['amex', 'discover', 'masterCard', 'visa'],
        merchantCapabilities: ['supports3DS'],
        requiredShippingContactFields: ["postalAddress", "email", "phone"]
      }
    }
  };

  base.configure = function(config) {
    this.config = _.extend(this.config, config);
  };

  /**
   * Initialize Braintree Client
   * @param {Function} callbackFn
   */
  base.initClient = function(callbackFn) {
    if (base.clientInstance !== null) {
      callbackFn(base.clientInstance);
    } else {
      braintree.create({
        authorization: base.config.clientToken
      }, function(clientErr, clientInstance) {
        if (clientErr) {
          console.error('Error creating client: ' + clientErr);
          return;
        }
        base.clientInstance = clientInstance;
        callbackFn(clientInstance);
      });
    }
  };

  /**
   * Initialize Braintree ApplePay
   * @param {Function} callbackFn
   */
  base.init = function(callbackFn) {
    if (base.applePayInstance !== null) {
      callbackFn(base.applePayInstance);
    } else {
      base.initClient(function(clientInstance) {
        applepay.create({
          client: clientInstance
        }, function(applePayErr, applePayInstance) {
          if (applePayErr) {
            console.error('Error creating applePayInstance: ' + applePayErr);
            return;
          }
          base.applePayInstance = applePayInstance;
          callbackFn(applePayInstance);
        });
      });
    }
  };

  /**
   * @returns {boolean}
   */
  base.canMakePayments = function() {
    if (!window.ApplePaySession) {
      console.info('This device does not support Apple Pay');
      return false;
    }
    if (!ApplePaySession.canMakePayments()) {
      console.info('This device is not capable of making Apple Pay payments');
      return false;
    }
    return true;
  };

  /**
   * Use Apple Pay merchant identifier to check if payments can be made.
   * @param {Function} callbackFn
   */
  base.canMakePaymentsWithActiveCard = function(callbackFn) {
    this.init(function(applePayInstance) {
      var promise = ApplePaySession.canMakePaymentsWithActiveCard(applePayInstance.merchantIdentifier);
      promise.then(function(canMakePaymentsWithActiveCard) {
        if (isFunction(callbackFn)) {
          callbackFn(canMakePaymentsWithActiveCard);
        }
      });
    });
  };

  /**
   * @param {Object} data
   *
   * Example:
   * {
   *  total: {
   *    label: 'test',
   *    amount: 1.5
   *  },
   *  currencyCode: 'USD',
   *  countryCode: 'US',
   *  requiredShippingContactFields: ['postalAddress', 'email', 'phone'],
   *  supportedNetworks: ['amex', 'discover', 'masterCard', 'visa'],
   *  requiredBillingContactFields: ['name'],
   * }
   */
  base.process = function(data) {
    base.init(function(applePayInstance) {

      // Build our payment request
      var paymentRequest = applePayInstance.createPaymentRequest(_.extend(base.config.payment, data));
      var session = new ApplePaySession(1, paymentRequest);

      if (isFunction(base.config.onShippingContactSelected)) {
        session.onshippingcontactselected = function(event) {
          base.config.onShippingContactSelected(event, applePayInstance, session);
          // session.completeShippingContactSelection(ApplePaySession.STATUS_SUCCESS, newShippingMethods, newTotal, newLineItems);
        };
      }

      if (isFunction(base.config.onShippingMethodSelected)) {
        session.onshippingmethodselected = function(event) {
          base.config.onShippingMethodSelected(event, applePayInstance, session);
          // session.completeShippingMethodSelection(status, newTotal, newLineItems);
        }
      }

      if (isFunction(base.config.onPaymentMethodSelected)) {
        session.onpaymentmethodselected = function(event) {
          base.config.onPaymentMethodSelected(event, applePayInstance, session);
          // session.completePaymentMethodSelection(newTotal, newLineItems);
        }
      }

      session.onvalidatemerchant = function(event) {
        applePayInstance.performValidation({
          validationURL: event.validationURL,
          displayName: base.config.displayName
        }, function(validationErr, validationData) {
          if (validationErr) {
            console.log('Error validating merchant: ' + validationErr);
            session.abort();
            return;
          }
          if (isFunction(base.config.onValidateMerchant)) {
            base.config.onValidateMerchant(event, applePayInstance, session);
          }
          session.completeMerchantValidation(validationData);
        });
      };

      session.onpaymentauthorized = function(event) {
        applePayInstance.tokenize({
          token: event.payment.token
        }, function(tokenizeErr, payload) {
          if (tokenizeErr) {
            console.log('Error tokenizing Apple Pay: ' + tokenizeErr);
            session.completePayment(ApplePaySession.STATUS_FAILURE);
            return;
          }
          session.completePayment(ApplePaySession.STATUS_SUCCESS);
          if (isFunction(base.config.onPaymentAuthorized)) {
            base.config.onPaymentAuthorized(payload, event);
          }
        });
      };

      session.begin();

    });
  };

  return base;

});