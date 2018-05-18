'use strict';
/**
 * @ngdoc BookingBasketController
 * @name tixmall.controller:CheckGiftVouchersController
 * @description
 * # BookingBasketController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('CheckGiftVouchersController', ['giftVoucherCheck', 'flash', '$filter', function(giftVoucherCheck, flash, $filter) {
        // used controller as syntax, assigned current scope to variable chkgift
        var chkgift = this;
        chkgift.coupon_code = '';
        var flashMessage;
        chkgift.showCouponDetails = false;
        chkgift.coupon_details = [];
        /**
         * @ngdoc method
         * @name CheckGiftVouchersController.check
         * @methodOf module.CheckGiftVouchersController
         * @description
         * This method will checks whether the user given coupon code is valid or not.
         */
        chkgift.check = function(code) {
            if (angular.isDefined(code) && code !== '') {
                giftVoucherCheck.get({
                    'coupon_code': code
                }, function(response) {
                    if (angular.isDefined(response.error) && parseInt(response.error.code) === 0) {
                        chkgift.showCouponDetails = true;
                        chkgift.coupon_details = response.data;
                        flashMessage = $filter("translate")("Your gift voucher is valid");
                        flash.set(flashMessage, "success", false);
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
    }]);