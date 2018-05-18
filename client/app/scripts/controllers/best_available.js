'use strict';
/**
 * @ngdoc BestAvailableController
 * @name tixmall.controller:BestAvailableController
 * @description
 * # BestAvailableController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('BestAvailableController', ['bookings', '$state', 'bestAvailableSeats', '$filter', 'flash', '$rootScope', 'CountDown', '$window', '$scope', 'md5', function(bookings, $state, bestAvailableSeats, $filter, flash, $rootScope, CountDown, $window, $scope, md5) {
        // used controller as syntax, assigned current scope to variable best_available
        /*jshint -W117 */
        var best_available = this;
        var flashMessage;
        best_available.zone_details = [];
        best_available.details = {};
        best_available.details.price_type = [];
        /**
         * @ngdoc method
         * @name best_available.init
         * @methodOf module.BestAvailableController
         * @description
         * This method loads event zone deatils based on venues
         * This method will be called initially on template loading
         * bookings.get endpoint results event, venue, event zones and event schedule information of current event.
         *
         */
        best_available.init = function() {
            bookings.get({
                    event_id: $state.params.event,
                    venue_id: $state.params.venue
                })
                .$promise.then(function(response) {
                    best_available.zone_details = response.data;
                    var event_schedule = best_available.zone_details.EventSchedule;
                    //Set normal thumb for attachment
                    if (angular.isDefined(best_available.zone_details.Event[0].attachments) && best_available.zone_details.attachments !== null) {
                        var normal_thumb_hash = md5.createHash('Event' + best_available.zone_details.Event[0].id + 'png' + 'normal_thumb');
                        best_available.zone_details.Event[0].normal_image_name = '/images/normal_thumb/Event/' + best_available.zone_details.Event[0].id + '.' + normal_thumb_hash + '.png';
                    }
                    // check minimum price is null, set to zero
                    if (angular.isDefined(best_available.zone_details.Event.min_event_price)) {
                        if (best_available.zone_details.Event.min_event_price === null) {
                            best_available.zone_details.Event.min_event_price = 0;
                        }
                    }
                    //finding start date
                    $scope.startdate = _.find(event_schedule, function(item) {
                        return parseInt($state.params.schedule_id) === item.id;
                    });
                });
        };
        best_available.init();
        /**
         * @ngdoc method
         * @name best_available.addPriceDetails
         * @methodOf module.BestAvailableController
         * @description
         * This a method will selected items to cart if its available
         *
         */
        best_available.checkAvailability = function() {
            var session_id = $window.localStorage.getItem("session_id");
            if (angular.isDefined(session_id) && (session_id === null || session_id === '')) {
                session_id = $rootScope.generateSession();
                $window.localStorage.setItem("session_id", session_id);
            }
            best_available.details.event_id = best_available.zone_details.Event[0].id;
            best_available.details.event_schedule_id = $scope.startdate.id;
            best_available.details.session_id = session_id;
            bestAvailableSeats.check_availabilty(best_available.details, function(response) {
                if (angular.isDefined(response.error) && parseInt(response.error.code) === 0) {
                    flashMessage = $filter("translate")("Items added to cart successfully.");
                    flash.set(flashMessage, "success", false);
                    if (!$rootScope.timerStarted) {
                        CountDown.stopTimer();
                        CountDown.startTimer(60 * 20);
                    }
                    $state.go('booking_basket', {});
                } else {
                    flashMessage = $filter("translate")("Sorry tickets are not available now.");
                    flash.set(flashMessage, "error", false);
                }
            });
        };
        /**
         * @ngdoc method
         * @name best_available.addPriceDetails
         * @methodOf module.BestAvailableController
         * @description
         * This a method to add choosed seats through dropdown
         * Its a ng-change function in select box in each zones
         *
         */
        best_available.addPriceDetails = function(price_type, price, event_zone_id, tickets) {
            if (tickets === "") {
                angular.forEach(best_available.details.price_type, function(value, key) {
                    if (value.id === price_type.id) {
                        best_available.details.price_type.splice(key, 1);
                    }
                });
            } else {
                best_available.details.price_type.push({
                    'id': price_type.id,
                    'price': price,
                    'tickets': tickets
                });
            }
        };
        /**
         * @ngdoc method
         * @name best_available.switchZone
         * @methodOf module.BestAvailableController
         * @description
         * This a method to switch zones by clicking radio buttons
         * Its a ng-change function in input radio button
         *
         */
        best_available.switchZone = function(zone_id) {
            $('.zones_wrapper')
                .removeClass('selected');
            $('#zones_' + zone_id)
                .addClass('selected');
            best_available.event_zone_select = {};
            best_available.details.price_type = [];
        };
        /**
         * @ngdoc method
         * @name  best_available.changeZoneOnParentClick
         * @methodOf module.BestAvailableController
         * @description
         * This a method to switch zones by clicking radio buttons on parent div 
         * This method purpose is to show content mobile screen arrow click
         *
         */
        best_available.changeZoneOnParentClick = function(zone_id) {
            $('.zones_wrapper')
                .removeClass('selected');
            $('#zones_' + zone_id)
                .addClass('selected');
            best_available.event_zone_select = {};
            best_available.details.event_zone_id = zone_id;
            best_available.details.price_type = [];
        };
    }]);