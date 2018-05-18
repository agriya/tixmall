'use strict';
/**
 * @ngdoc SecureDeliveryController
 * @name tixmall.controller:SecureDeliveryController
 * @description
 * # SecureDeliveryController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('SecureDeliveryController', ['$state', 'Cart', '$scope', 'deliverymethod', 'checkout', '$parse', '$rootScope', 'userSettings', 'updatecart', '$window', 'CountDown', 'flash', '$filter', '$cookies', function($state, Cart, $scope, deliverymethod, checkout, $parse, $rootScope, userSettings, updatecart, $window, CountDown, flash, $filter, $cookies) {
        // Assigning Cart service to scope to use the values in template
        $scope.Cart = Cart;
        /*jshint -W117 */
        // used controller as syntax, assigned current scope to variable delivery
        var delivery = this;
        delivery.imagenamedetails = [];
        $scope.selected_delivery_method = '';
        delivery.chekout_details = {};
        delivery.user = {};
        delivery.available_delivery_methods = [];
        var model;
        /**
         * @ngdoc method
         * @name SecureDeliveryController.delivery.delivery
         * @methodOf module.SecureDeliveryController
         * @description
         * This is onclik function for delievry methods, it will changes current delivery method
         * Created dynamic model to achieve diffent style on hover of delivery methods
         * @param {string,string} Description of parameter
         * 
         */
        delivery.delivery = function(id, event) {
            $scope.selected_delivery_method = id;
            angular.forEach(delivery.imagenamedetails, function(value) {
                // to highlight image for current delievry method and normal image for other methods.
                // dynamically creating scope and assigning values.
                if (value.id === "deliver_method_image" + id) {
                    model = $parse(value.id);
                    // Assigns a value to it
                    model.assign($scope, true);
                } else {
                    model = $parse(value.id);
                    // Assigns a value to it
                    model.assign($scope, false);
                }
            });
            $(event.target)
                .parents()
                .find('.deliveryMethod')
                .removeClass('selected-color');
            $(event.target)
                .parents()
                .find('#deliveryMethod_' + id)
                .addClass('selected-color');
        };
        /**
         * @ngdoc method
         * @name SecureDeliveryController.delivery.ClearCart
         * @methodOf module.SecureDeliveryController
         * @description
         * It clears cart and stop the timer running
         * 
         */
        delivery.ClearCart = function() {
            Cart.empty("clear");
            $window.localStorage.removeItem('session_id');
            CountDown.stopTimer();
            $state.go('booking_basket');
        };
        /**
         * @ngdoc method
         * @name SecureDeliveryController.delivery.init
         * @methodOf module.SecureDeliveryController
         * @description
         * This method binds available delivery methods and user address form his/her profiles initially
         * After user can update his/her address by clicking update address button
         * 
         */
        delivery.init = function() {
            deliverymethod.get()
                .$promise.then(function(response) {
                    delivery.available_delivery_methods = response.data;
                    $scope.selected_delivery_method = response.data[0].id;
                    angular.forEach(response.data, function(value, key) {
                        delivery.imagenamedetails.push({
                            'id': "deliver_method_image" + value.id
                        });
                        // to make first delivery method active
                        if (key === 0) {
                            model = $parse("deliver_method_image" + value.id);
                            // Assigns a value to it
                            model.assign($scope, true);
                        }
                    });
                });
            var params = {};
            params.id = $rootScope.user.id;
            // User profiles details
            userSettings.get(params, function(response) {
                delivery.user.country_id = response.data.country_id;
                delivery.user.address = response.data.address;
                delivery.user.address1 = response.data.address1;
                delivery.user.zip_code = response.data.zip_code;
                delivery.user.city_id = response.data.city_id;
                delivery.user.city_name = response.data.city;
                delivery.user.state_name = response.data.state;
                delivery.user.country = response.data.country;
            });
        };
        /**
         * @ngdoc method
         * @name SecureDeliveryController.delivery.checkout
         * @methodOf module.SecureDeliveryController
         * @description
         * This method stores billing and delivery method details in local storage
         * 
         */
        delivery.checkout = function() {
            if (delivery.user.address !== '' && delivery.user.address1 !== '' && delivery.user.zip_code !== '' && delivery.user.state_name !== '' && delivery.user.city_name !== '' && delivery.user.city_name !== null && delivery.user.state_name !== null) {
                Cart.setDeliveryMethod($scope.selected_delivery_method);
                Cart.$restore(Cart.getCart());
                $window.localStorage.setItem('billingAddress', JSON.stringify(delivery.user));
                delivery.chekout_details.delivery_method_id = $scope.selected_delivery_method;
                var session_id = $window.localStorage.getItem("session_id");
                delivery.chekout_details.session_id = session_id;
                checkout.createcheckout(delivery.chekout_details, function() {});
                $state.go('secure_payment');
            } else {
                var message = $filter("translate")("Please update address with all details");
                flash.set(message, "error", false);
            }
        };
        //Updating cart with user_id
        delivery.updateCartDatails = {};
        var auth = JSON.parse($cookies.get('auth'));
        var session_id = $window.localStorage.getItem("session_id");
        delivery.updateCartDatails.session_id = session_id;
        delivery.updateCartDatails.user_id = auth.id;
        updatecart.update(delivery.updateCartDatails, function() {});
        delivery.update_billing_address = false;
        delivery.updateBilling = function() {
            delivery.update_billing_address = true;
        };
        delivery.init();
        //google address location tracker initialization
        var inputFrom = document.getElementById('goo-place');
        /*jshint -W117 */
        var autocompleteFrom = new google.maps.places.Autocomplete(inputFrom); //jslint vars:false       
        google.maps.event.addListener(autocompleteFrom, 'place_changed', function() { //jslint vars:false
            var place = autocompleteFrom.getPlace();
            //delivery.user.latitude = place.geometry.location.lat();
            //delivery.user.longitude = place.geometry.location.lng();
            var k = 0;
            angular.forEach(place.address_components, function(value, key) {
                //jshint unused:false
                if (value.types[0] === 'locality' || value.types[0] === 'administrative_area_level_2') {
                    if (k === 0) {
                        delivery.user.city_name = value.long_name;
                    }
                    if (value.types[0] === 'locality') {
                        k = 1;
                    }
                }
                if (value.types[0] === 'administrative_area_level_1') {
                    delivery.user.state_name = value.long_name;
                }
                if (value.types[0] === 'sublocality_level_1') {
                    delivery.user.address = value.long_name;
                }
                if (value.types[0] === 'country') {
                    delivery.user.country_iso_alpha2 = value.short_name;
                }
                if (value.types[0] === 'postal_code') {
                    delivery.user.zip_code = parseInt(value.long_name);
                }
            });
        });
    }]);