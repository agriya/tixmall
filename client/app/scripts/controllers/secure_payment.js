'use strict';
/**
 * @ngdoc SecurePaymentController
 * @name tixmall.controller:SecurePaymentController
 * @description
 * # SecurePaymentController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('SecurePaymentController', ['$state', 'Cart', '$scope', 'orders', '$window', 'flash', '$filter', 'CountDown', 'giftVoucherCheck', 'creditcard', '$cookies', function($state, Cart, $scope, orders, $window, flash, $filter, CountDown, giftVoucherCheck, creditcard, $cookies) {
        //Assingning Cart service in scope to use cart values in template
        $scope.Cart = Cart;
        // used controller as syntax, assigned current scope to variable payment
        var payment = this;
        payment.paymentDetails = {};
        payment.gift_voucher_applied = false;
        payment.show_payment_form = false;
        payment.gift_voucher_available_amount = 0;
        payment.total_amount = ((2 / 100) * Cart.totalCost()) + Cart.totalCost() + Cart.getDonationAmount();
        var flashMessage;
        var auth = JSON.parse($cookies.get('auth'));
        if ($window.localStorage.getItem('billingAddress') !== '') {
            var billing_address = JSON.parse($window.localStorage.getItem('billingAddress'));
        }
        var session_id = $window.localStorage.getItem("session_id");
        /**
         * @ngdoc method
         * @name SecurePaymentController.payment.confirmPurchase
         * @methodOf module.SecurePaymentController
         * @description
         * Payment method, This method created payment order for cart items
         * 
         */
        payment.confirmPurchase = function() {
            if ($scope.creditcard.$valid || !payment.show_payment_form || payment.giftvoucherValid) {
                if (payment.paymentDetails.customer_id === "other") {
                    delete payment.paymentDetails.customer_id;
                }
                // getting billing, session id and delivery method details from local storage
                payment.paymentDetails.user_id = auth.id;
                if (angular.isDefined(billing_address) && billing_address !== null) {
                    payment.paymentDetails.address1 = billing_address.address1;
                    payment.paymentDetails.address = billing_address.address;
                    payment.paymentDetails.zip_code = billing_address.zip_code;
                    payment.paymentDetails.city_name = billing_address.city_name;
                    payment.paymentDetails.state_name = billing_address.state_name;
                    payment.paymentDetails.country_iso_alpha2 = billing_address.country_iso_alpha2;
                    payment.paymentDetails.delivery_method_id = Cart.getDeliveryMethod();
                    payment.paymentDetails.delivery_amount = Cart.getDonationAmount();
                }
                payment.paymentDetails.total_amount = payment.total_amount;
                payment.paymentDetails.session_id = session_id;
                payment.paymentDetails.email = auth.email;
                orders.createorder(payment.paymentDetails, function(response) {
                    if (response.error.code === "0") {
                        flashMessage = $filter("translate")("Payment completed successfully.");
                        flash.set(flashMessage, 'success', false);
                        $window.localStorage.removeItem('session_id');
                        CountDown.stopTimer();
                        $window.localStorage.setItem('orderId', response.data.id);
                        $window.localStorage.setItem('billingAddress', '');
                        $state.go('payment_result');
                    } else {
                        flashMessage = $filter("translate")("Payment could not be completed.");
                        flash.set(flashMessage, 'error', false);
                    }
                });
            }
        };
        /**
         * @ngdoc method
         * @name SecurePaymentController.payment.check
         * @methodOf module.SecurePaymentController
         * @description
         * It checks gift voucher validaluty and availble amount
         * 
         */
        payment.check = function(code) {
            if (angular.isDefined(code) && code !== '') {
                giftVoucherCheck.get({
                    'coupon_code': code
                }, function(response) {
                    if (angular.isDefined(response.data)) {
                        payment.paymentDetails.gift_voucher_id = response.data[0].id;
                        payment.gift_voucher_available_amount = response.data[0].avaliable_amount;
                        var total_amount = response.data[0].avaliable_amount - payment.total_amount;
                        if (total_amount < 0) {
                            payment.total_amount = Math.abs(total_amount);
                            flashMessage = $filter("translate")("Voucher amount deducted in basket payment. Please pay pending amount using creditcard!..");
                            flash.set(flashMessage, "error", false);
                            payment.gift_voucher_applied = false;
                        } else {
                            payment.gift_voucher_applied = true;
                            payment.total_amount = 0;
                            payment.confirmPurchase();
                        }
                    } else {
                        flashMessage = $filter("translate")("No records found");
                        flash.set(flashMessage, "error", false);
                    }
                });
            } else {
                flashMessage = $filter("translate")("gift voucher code is empty");
                flash.set(flashMessage, "error", false);
            }
        };
        /**
         * @ngdoc method
         * @name SecurePaymentController.payment.selectOther
         * @methodOf module.SecurePaymentController
         * @description
         * This method opens payment form, for the user who doesn't want to use already used cards
         * 
         */
        payment.selectOther = function() {
            payment.show_payment_form = true;
        };
        /**
         * @ngdoc method
         * @name SecurePaymentController.payment.selectsavedCard
         * @methodOf module.SecurePaymentController
         * @description
         * It will procedd payment with selected saved card, here we sent customer_id to get saved card details in payfort(server end)
         * 
         */
        payment.selectsavedCard = function(customer_id) {
            payment.paymentDetails.customer_id = customer_id;
            payment.show_payment_form = false;
        };
        /**
         * @ngdoc method
         * @name SecurePaymentController.payment.init
         * @methodOf module.SecurePaymentController
         * @description
         * This method will lists available saved credit cards with radio button
         * 
         */
        payment.init = function() {
            creditcard.get()
                .$promise.then(function(response) {
                    payment.saved_card_details = response.data;
                    if (payment.saved_card_details.length > 0) {
                        payment.show_payment_form = false;
                        payment.paymentDetails.customer_id = response.data[0].customer_id;
                    } else {
                        payment.show_payment_form = true;
                    }
                });
        };
        payment.init();
    }]);