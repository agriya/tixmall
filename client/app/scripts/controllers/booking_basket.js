'use strict';
/**
 * @ngdoc BookingBasketController
 * @name tixmall.controller:BookingBasketController
 * @description
 * # BookingBasketController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('BookingBasketController', ['$state', 'Cart', '$scope', 'DonationAmt', '$filter', '$window', 'CountDown', 'events', 'md5', 'carts', '$rootScope', '$cookies', function($state, Cart, $scope, DonationAmt, $filter, $window, CountDown, events, md5, carts, $rootScope, $cookies) {
        //assigning Cart service to scope
        $scope.Cart = Cart;
        // used controller as syntax, assigned current scope to variable booking_basket
        var booking_basket = this;
        /*jshint -W117 */
        //jshint unused:false
        booking_basket.booking_basket = [];
        booking_basket.interested_in = [];
        booking_basket.cart_details = [];
        $scope.donation_amount_id = '';
        /**
         * @ngdoc method
         * @name booking_basket.proceedToCheckout
         * @methodOf module.BookingBasketController
         * @description
         * This method will redirect a user from cart page to deleivery page if user authenticated else it will be redirected to login page
         *
         */
        booking_basket.proceedToCheckout = function() {
            if (($cookies.get("token") !== null && $cookies.get("token") !== undefined) && ($cookies.get("auth") !== null && $cookies.get("auth") !== undefined)) {
                if (booking_basket.cart_details.length === 1 && $scope.giftvoucherAdded) {
                    $state.go('secure_payment');
                } else {
                    $state.go('secure_delivery');
                }
            } else {
                if (booking_basket.cart_details.length === 1 && $scope.giftvoucherAdded) {
                    $window.localStorage.setItem("redirect_url", "/secure/payment");
                } else {
                    $window.localStorage.setItem("redirect_url", "/secure/delivery");
                }
                $state.go('users_login');
            }
        };
        /**
         * @ngdoc method
         * @name booking_basket.ClearCart
         * @methodOf module.BookingBasketController
         * @description
         * This method will clear cart and running timer on cancel order button action.
         *
         */
        booking_basket.ClearCart = function() {
            Cart.empty("clear");
            $window.localStorage.removeItem('session_id');
            CountDown.stopTimer();
            $state.go('booking_basket');
        };
        /**
         * @ngdoc method
         * @name booking_basket.donate
         * @methodOf module.BookingBasketController
         * @description
         * This method will add donation amount as new cart item with dismissable alert message.
         *
         */
        booking_basket.donate = function() {
            $scope.donated = true;
            var selected_donation_amt = $('#donation_amt option:selected')
                .text();
            selected_donation_amt = selected_donation_amt.split($rootScope.selectedCurrency.currency_symbol)[1];
            selected_donation_amt = selected_donation_amt.split('.')[0];
            Cart.setDonationAmount(selected_donation_amt);
        };
        if (angular.isDefined(Cart.getItems()[0]) && Cart.getItems()[0].getData()
            .category_id !== "") {
            var eventparams = {
                limit: 3,
                is_active: true,
                category_id: Cart.getItems()[0].getData()
                    .category_id
            };
        }
        /**
         * @ngdoc method
         * @name booking_basket.init
         * @methodOf module.BookingBasketController
         * @description
         * This method will load cart items that user added in current session_id
         * Cart items are stored in local storage using cart and cart_item service
         * This method will set available doantion amounts in a dropdown
         */
        booking_basket.init = function() {
            /* you may also intereseted in block*/
            /**It binds 3 events based on currently added items category */
            if (angular.isDefined(Cart.getItems()[0]) && Cart.getItems()[0].getData()
                .category_id !== "") {
                events.get(eventparams, function(response) {
                    if (angular.isDefined(response.data)) {
                        booking_basket.interested_in = response.data;
                        angular.forEach(booking_basket.interested_in, function(value) {
                            if (angular.isDefined(value.attachments) && value.attachments !== null) {
                                var hash = md5.createHash('Event' + value.id + 'png' + "extra_medium_thumb");
                                value.image_name = '/images/' + "extra_medium_thumb" + '/Event/' + value.id + '.' + hash + '.png';
                            }
                        });
                    }
                });
            }
            /* Listing donation amounts*/
            DonationAmt.get()
                .$promise.then(function(response) {
                    booking_basket.donation_amounts = response.data;
                    angular.forEach(response.data, function(value) {
                        var stored_donation_amt = Cart.getDonationAmount();
                        if (parseInt(value.amount) === parseInt(stored_donation_amt)) {
                            $scope.donation_amount_id = value.id;
                        }
                    });
                    $scope.donation_amount_id = ($scope.donation_amount_id !== '') ? $scope.donation_amount_id : booking_basket.donation_amounts[0].id;
                });
            var session_id = $window.localStorage.getItem("session_id");
            var event_name;
            // bind cart items in a page, if current session id exist
            if (angular.isDefined(session_id) && session_id !== '' && session_id !== null) {
                carts.get({
                    "session_id": session_id
                }, function(response) {
                    booking_basket.cart_details = response.data;
                    var sessionId = Cart.getSessionId();
                    var deliveryMethod = Cart.getDeliveryMethod();
                    Cart.empty();
                    angular.forEach(booking_basket.cart_details, function(value) {
                        var price = {};
                        var cart_data = {};
                        var quantity;
                        $scope.giftvoucherAdded = false;
                        if (angular.isDefined(value.gift_voucher_id) && (value.gift_voucher_id === 0 || value.gift_voucher_id === null)) {
                            price.price = value.price;
                            price.price_type_id = value.price_type.id;
                            price.name = value.price_type.name;
                            if (angular.isDefined(value.venue_zone_section_seats) && value.venue_zone_section_seats !== null) {
                                cart_data.seat_name = value.venue_zone_section_seats.seat_number;
                                cart_data.seat_id = value.venue_zone_section_seats.id;
                                cart_data.venue_zone_section_name = value.venue_zone_section_seats.venue_zone_section.name;
                                cart_data.venue_zone_section_row_name = value.venue_zone_section_seats.venue_zone_section_row.name;
                                cart_data.zone_name = value.venue_zone_section_seats.venue_zone.name;
                            }
                            cart_data.price = price;
                            cart_data.event_start_date = value.events.event_schedule[0].start_date;
                            cart_data.event_end_date = value.events.event_schedule[0].end_date;
                            cart_data.event_id = value.events.id;
                            cart_data.category_id = value.events.category_id;
                            event_name = value.events.name;
                        } else {
                            price.price = value.price;
                            event_name = "Gift Voucher";
                            $scope.giftvoucherAdded = true;
                        }
                        // Without seat map ticket booking with mulitple quantity 
                        if (angular.isDefined(value.is_choose_best_availability) && value.is_choose_best_availability === 1) {
                            quantity = value.quantity;
                        } else {
                            quantity = 1;
                        }
                        Cart.addItem(value.id, event_name, value.price, quantity, cart_data);
                    });
                    Cart.setSessionId(sessionId);
                    Cart.setDeliveryMethod(deliveryMethod);
                    Cart.$restore(Cart.getCart());
                    $scope.Cart = Cart;
                });
            }
        };
        booking_basket.init();
    }]);