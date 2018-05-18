'use strict';
/**
 * @ngdoc SecurePaymentController
 * @name tixmall.controller:SecurePaymentController
 * @description
 * # SecurePaymentController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('SecurePaymentResultController', ['getordersById', '$window', '$scope', 'Cart', '$cookies', function(getordersById, $window, $scope, Cart, $cookies) {
        // used controller as syntax, assigned current scope to variable paymentresult
        var paymentresult = this;
        paymentresult.order_details = [];
        var orderParams = {};
        /**
         * @ngdoc method
         * @name SecurePaymentResultController.paymentresult.print
         * @methodOf module.SecurePaymentResultController
         * @description
         * This method taken current page to print view
         *
         */
        paymentresult.print = function() {
            $window.print();
        };
        var auth = JSON.parse($cookies.get('auth'));
        paymentresult.user = auth;
        /**
         * @ngdoc method
         * @name paymentresult.model.init
         * @methodOf module.SecurePaymentResultController
         * @description
         * This method will get order details and it show payment confrmation details
         *
         */
        paymentresult.init = function() {
            orderParams.orderid = $window.localStorage.getItem("orderId");
            getordersById.get(orderParams)
                .$promise.then(function(response) {
                    paymentresult.order_details = response.data;
                });
        };
        paymentresult.init();
        /**
         * @ngdoc method
         * @name paymentresult.model.destroy
         * @methodOf module.SecurePaymentResultController
         * @description
         * This method will clear cart on current controller destroy
         *
         */
        $scope.$on("$destroy", function() {
            Cart.empty();
        });
    }]);