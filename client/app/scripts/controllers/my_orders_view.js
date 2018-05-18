'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:MyOrdersController
 * @description
 * # MyOrdersController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('MyOrdersViewController', ['getordersById', '$state', 'sendTickets', '$filter', 'flash', function(getordersById, $state, sendTickets, $filter, flash) {
            // used controller as syntax, assigned current scope to variable myordersview
            var myordersview = this;
            var orderParams = {};
            var params = {};
            var flashMessage;
            /**
             * @ngdoc method
             * @name MyOrdersViewController.myordersview.init
             * @methodOf module.MyOrdersViewController
             * @description
             * This method loads individual orders details based on order id
             *
             */
            myordersview.init = function() {
                orderParams.orderid = $state.params.id;
                getordersById.get(orderParams)
                    .$promise.then(function(response) {
                        myordersview.order_details = response.data;
                    });
            };
            myordersview.init();
            myordersview.resendTicket = function() {
                params.orderid = $state.params.id;
                sendTickets.get(params)
                    .$promise.then(function(response) {
                        if (angular.isDefined(response.error) && response.error.code === 0) {
                            flashMessage = $filter("translate")("E-Tickets send to your email successfully.");
                            flash.set(flashMessage, "success", false);
                        } else {
                            flashMessage = $filter("translate")("There is a problem on sending your E-tickets, Please try again later");
                            flash.set(flashMessage, "error", false);
                        }
                    });
            };
    }
]);