'use strict';
/**
 * @ngdoc BookingBasketController
 * @name tixmall.controller:CheckGiftVouchersController
 * @description
 * # BookingBasketController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('PurchaseGiftCertificateController', ['DonationAmt', 'giftVoucher', '$scope', '$window', 'flash', '$filter', 'carts', '$rootScope', 'CountDown', '$state', '$cookies', function(DonationAmt, giftVoucher, $scope, $window, flash, $filter, carts, $rootScope, CountDown, $state, $cookies) {
        // used controller as syntax, assigned current scope to variable purchasegift
        var purchasegift = this;
        purchasegift.donation_amounts = [];
        purchasegift.details = {};
        $scope.sent_to_me = true;
        var auth = JSON.parse($cookies.get('auth'));
        purchasegift.disable_enter_amount = true;
        purchasegift.details.is_general = true;
        var flashMessage;
        // This will watch radio button input and automatically it will be refilled based on the input     
        $scope.$watch('sent_to_me', function(value) {
            if (String(value) === 'true') {
                purchasegift.details.to_email = auth.email;
                purchasegift.confirm_email = auth.email;
            } else {
                purchasegift.details.to_email = '';
            }
        });
        /**
         * @ngdoc method
         * @name PurchaseGiftCertificateController.purchasegift.addGiftToBasket
         * @methodOf module.PurchaseGiftCertificateController
         * @description
         * This method adds gift voucher as cart item
         *
         */
        purchasegift.addGiftToBasket = function() {
            purchasegift.details.is_used = false;
            if (purchasegift.disable_enter_amount === false) {
                purchasegift.details.amount = purchasegift.own_amount;
            }
            var session_id = $window.localStorage.getItem("session_id");
            if (angular.isDefined(session_id) && (session_id === null || session_id === '')) {
                session_id = $rootScope.generateSession();
                $window.localStorage.setItem("session_id", session_id);
            }
            purchasegift.details.session_id = session_id;
            giftVoucher.create(purchasegift.details, function(response) {
                if (parseInt(response.error.code) === 0) {
                    //$window.localStorage.setItem("session_id", response.data.session_id);
                    flash.set("Added items to basket successfully", "success", false);
                    if (!$rootScope.timerStarted) {
                        CountDown.stopTimer();
                        CountDown.startTimer(60 * 20);
                    }
                    $state.go('booking_basket', {});
                } else {
                    flashMessage = $filter("translate")("Gift voucher could not be added. Please try again later");
                    flash.set(flashMessage, "error", false);
                }
            });
        };
        /**
         * @ngdoc method
         * @name PurchaseGiftCertificateController.purchasegift.ownAmount
         * @methodOf module.PurchaseGiftCertificateController
         * @description
         * This method enables user defined gift voucher amount text box.
         *
         */
        purchasegift.ownAmount = function() {
            purchasegift.disable_enter_amount = false;
        };
        /**
         * @ngdoc method
         * @name PurchaseGiftCertificateController.purchasegift.defaultAmount
         * @methodOf module.PurchaseGiftCertificateController
         * @description
         * This method disables user defined gift voucher amount text box initially.
         *
         */
        purchasegift.defaultAmount = function() {
            purchasegift.disable_enter_amount = true;
            purchasegift.own_amount = "";
        };
        /**
         * @ngdoc method
         * @name PurchaseGiftCertificateController.purchasegift.init
         * @methodOf module.PurchaseGiftCertificateController
         * @description
         * This method will load available gift voucher amount(here we used donation amount for gift voucher too)
         *
         */
        purchasegift.init = function() {
            /* Listing donation amounts*/
            DonationAmt.get()
                .$promise.then(function(response) {
                    purchasegift.donation_amounts = response.data;
                    purchasegift.details.amount = purchasegift.donation_amounts[0].amount;
                });
        };
        purchasegift.init();
    }]);