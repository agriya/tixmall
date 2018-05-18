'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:ChooseTicketController
 * @description
 * # ChooseTicketController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('ChooseTicketController', ['$scope', '$state', 'md5', 'bookings', 'preloader', 'priceTypes', 'bestAvailableSeats', '$window', 'flash', '$filter', 'CountDown', '$rootScope', '$stateParams', function($scope, $state, md5, bookings, preloader, priceTypes, bestAvailableSeats, $window, flash, $filter, CountDown, $rootScope, $stateParams) {
        /*jshint -W117 */
        var model = this;
        var hash;
        var zone_details = [];
        var event_schedule = [];
        var image_type = "";
        var flashMessage;
        model.tickets_details = {};
        model.price_types_details = [];
        model.event_price_select = {};
        model.tickets_details.price_type = [];
        $('.venue-images')
            .css({
                "visibility": "hidden"
            });
        $('.loader-image')
            .css({
                "display": "block"
            });
        $scope.loader_is_disabled = true;
        $scope.imageLocations = [];
        bookings.get({
                event_id: $state.params.event_id,
                venue_id: $state.params.venue_id
            })
            .$promise.then(function(response) {
                model.bookings = response.data;
                // Check event id in the venue eveent_zones
                angular.forEach(model.bookings[0].event_venue_zone, function(value) {
                    if (parseInt(value.event_id) === parseInt($stateParams.event_id)) {
                        model.bookings[0].event_venue_zone[0] = value;
                    }
                });
                //Set normal thumb for attachment
                if (angular.isDefined(model.bookings.Event[0].attachments) && model.bookings.Event[0].attachments !== null) {
                    var normal_thumb_hash = md5.createHash('Event' + model.bookings.Event[0].id + 'png' + 'normal_thumb');
                    model.bookings.Event[0].normal_image_name = '/images/normal_thumb/Event/' + model.bookings.Event[0].id + '.' + normal_thumb_hash + '.png';
                }
                // check minimum price is null, set to zero
                if (angular.isDefined(model.bookings.Event.min_event_price)) {
                    if (model.bookings.Event.min_event_price === null) {
                        model.bookings.Event.min_event_price = 0;
                    }
                }
                //Stroing event schedule array to local variable
                event_schedule = model.bookings.EventSchedule;
                //finding start date
                $scope.startdate = _.find(event_schedule, function(item) {
                    return parseInt($state.params.schedule_id) === item.id;
                });
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.allGroupedDate
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method groups all date with key name 'all'
                 */
                $scope.allGroupedDate = _.groupBy(event_schedule, function() {
                    return "all";
                });
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.grouped
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method groups all data based on date like '2016-09-13'
                 */
                $scope.grouped = _.groupBy(event_schedule, function(item) {
                    return item.start_date.substring(0, 10);
                });
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.groupedByMonthAndYear
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method groups all data based on year and month like '2016-09'
                 */
                $scope.groupedByMonthAndYear = _.groupBy(event_schedule, function(item) {
                    return item.start_date.substring(0, 7);
                });
                $scope.active = 0;
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.changeGroupedByYear
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method will group date based on choosed year and month
                 * Its a ng-change function for year and month dropdown
                 */
                $scope.changeGroupedByYear = function(value) {
                    $scope.scheduled_year = value;
                    $scope.groupedByDate = _.groupBy(event_schedule, function(item) {
                        return (value.substring(0, 7) === item.start_date.substring(0, 7)) ? value : false;
                    });
                    // to remove duplicate values in date and month dropdown
                    $scope.groupedByDate = _.uniq($scope.groupedByDate[value], function(value) {
                        return value.start_date.substring(0, 10);
                    });
                    // initially select date and month
                    $scope.scheduled_date = $scope.groupedByDate[0].start_date;
                    $scope.changeGroupedByDate($scope.scheduled_date);
                    // updaing event schedule id on year change
                    $scope.selectedEventSchedule = _.find(event_schedule, function(item) {
                        return $scope.scheduled_date === item.start_date;
                    });
                };
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.changeGroupedByDate
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method will group time based on choosed year and date&day
                 * Its a ng-change function for date and month dropdown
                 */
                $scope.changeGroupedByDate = function(value) {
                    $scope.scheduled_date = value;
                    $scope.groupedByTime = _.groupBy(event_schedule, function(item) {
                        return (value.substring(0, 10) === item.start_date.substring(0, 10)) ? value : false;
                    });
                    $scope.scheduled_time = $scope.groupedByTime[value][0].start_date;
                    // updaing event schedule id on date and month change
                    $scope.selectedEventSchedule = _.find(event_schedule, function(item) {
                        return $scope.scheduled_time === item.start_date;
                    });
                };
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.changeGroupedByDate
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method will group time based on choosed year and date and day
                 * Its a ng-change function for date and month dropdown
                 */
                $scope.changeGroupedByTime = function(value) {
                    // updaing event schedule id on time change
                    $scope.selectedEventSchedule = _.find(event_schedule, function(item) {
                        return value === item.start_date;
                    });
                };
                // initially selecting year and month (first item in dropdown)
                var count = 0;
                var svg_count = 0;
                angular.forEach($scope.groupedByMonthAndYear, function(value) {
                    count++;
                    if (count === 1) {
                        $scope.scheduled_year = value[0].start_date;
                        // initially selecting year and month (first item in dropdown)
                        $scope.changeGroupedByYear($scope.scheduled_year);
                    }
                });
                //console.log($scope.groupedByDate);
                angular.forEach(model.bookings, function(value) {
                    if (angular.isDefined(value.attachments) && value.attachments !== null) {
                        angular.forEach(value.attachments, function(attachment) {
                            image_type = attachment.filename.split(".");
                            if (image_type[1] === "svg") {
                                $scope.svg_image = '/images/VenueSVG/' + value.id + '/' + attachment.filename;
                                $scope.imageLocations.push($scope.svg_image);
                            } else {
                                hash = md5.createHash('Venue' + value.id + 'png' + 'original');
                                $scope.image_name = '/images/original/Venue/' + value.id + '.' + hash + '.png';
                                $scope.imageLocations.push($scope.image_name);
                            }
                        });
                    }
                    angular.forEach(value.venue_zone, function(venue_zones) {
                        angular.forEach(venue_zones.attachments, function(attachment) {
                            image_type = attachment.filename.split(".");
                            if (image_type[1] !== "svg") {
                                hash = md5.createHash('VenueZone' + venue_zones.id + 'png' + 'original');
                                $scope.imageLocations.push('/images/original/VenueZone/' + venue_zones.id + '.' + hash + '.png');
                                svg_count++;
                            }
                        });
                        var available_count = 0;
                        angular.forEach(value.event_zone, function(event_zones) {
                            if (venue_zones.id === event_zones.venue_zone_id) {
                                available_count = available_count + event_zones.available_count;
                            }
                        });
                        zone_details.push({
                            'venue_zone_id': venue_zones.id,
                            'zone_name': venue_zones.name,
                            'available_count': available_count,
                            'is_booking_available': venue_zones.is_booking_available
                        });
                    });
                });
                preloader.preloadImages($scope.imageLocations)
                    .then(function handleResolve() {
                        var svgInterval = setInterval(function() {
                            if ($("#zones")
                                .html() !== undefined) {
                                var svg = $("#zones")
                                    .getSVG();
                                if (svg[0].contentType === 'image/svg+xml') {
                                    clearInterval(svgInterval);
                                    if (svg_count >= 1) {
                                        $(svg)
                                            .find('g > *')
                                            .css({
                                                "fill": "transparent",
                                                "stroke": "transparent",
                                                "cursor": "pointer"
                                            });
                                    } else {
                                        $(svg)
                                            .find('g > *')
                                            .css({
                                                "cursor": "pointer"
                                            });
                                        $('.venue-images')
                                            .css({
                                                "height": "658px"
                                            });
                                    }
                                    $('.venue-images')
                                        .css({
                                            "visibility": "visible"
                                        });
                                    $('.loader-image')
                                        .css({
                                            "display": "none"
                                        });
                                    if (svg_count >= 1) {
                                        $(svg)
                                            .find('.js-zone')
                                            .mouseover(function() {
                                                var zone_id = $(this)
                                                    .attr('id')
                                                    .replace('zone-', '');
                                                hash = md5.createHash('VenueZone' + zone_id + 'png' + 'original');
                                                $scope.image_name = '/images/original/VenueZone/' + zone_id + '.' + hash + '.png';
                                                $scope.$apply();
                                            });
                                    } else {
                                        $(svg)
                                            .find('.js-zone')
                                            .mouseover(function() {
                                                $(this)
                                                    .children()
                                                    .attr('data-previous-color', $(this)
                                                        .children()
                                                        .attr('fill'));
                                                $(this)
                                                    .children()
                                                    .css({
                                                        'fill': '#bd1314'
                                                    });
                                                /*for text color change*/
                                                $(this)
                                                    .children()
                                                    .next()
                                                    .css({
                                                        'fill': '#FFFFFF'
                                                    });
                                            })
                                            .mouseout(function() {
                                                $(this)
                                                    .children()
                                                    .css('fill', $(this)
                                                        .children()
                                                        .attr('data-previous-color'));
                                                /*for text color revert*/
                                                $(this)
                                                    .children()
                                                    .next()
                                                    .css('fill', '#a0a0a0');
                                            });
                                    }
                                    angular.forEach(zone_details, function(event_zones) {
                                        if (event_zones.is_booking_available !== 'false') {
                                            $(svg)
                                                .find('.js-zone')
                                                .click(function() {
                                                    var zone_id = $(this)
                                                        .attr('id')
                                                        .replace('zone-', '');
                                                    $state.go('choose_seats', {
                                                        event_id: $state.params.event_id,
                                                        zone_id: zone_id,
                                                        schedule_id: $scope.startdate.id
                                                    });
                                                });
                                        }
                                    });
                                    $(svg)
                                        .find('.js-zone')
                                        .bind("mouseover mousemove", function(oEvent) {
                                            var zone_id = $(this)
                                                .attr('id')
                                                .replace('zone-', '');
                                            var popup_text = "";
                                            angular.forEach(zone_details, function(event_zones) {
                                                if (parseInt(zone_id) === event_zones.venue_zone_id) {
                                                    popup_text = '<h4 class="list-group-item-heading">' + event_zones.zone_name + '</h4><h5 class="list-group-item-heading"> Available: ' + event_zones.available_count + '</h5>';
                                                }
                                            });
                                            $(this)
                                                .qtip({
                                                    content: {
                                                        text: popup_text
                                                    },
                                                    position: {
                                                        target: [oEvent.screenX, oEvent.clientY + 280]
                                                    },
                                                    show: {
                                                        delay: 0,
                                                        event: false,
                                                        ready: true,
                                                        effect: false
                                                    },
                                                });
                                        });
                                }
                            }
                        }, 100);
                    });
            });
        /**
         * @ngdoc method
         * @name ChooseTicketController.model.getPriceTypes
         * @methodOf module.ChooseTicketController
         * @description
         * This method will fetch all availbale price types
         */
        model.getPriceTypes = function() {
            priceTypes.get()
                .$promise.then(function(response) {
                    model.price_types_details = response.data;
                });
        };
        /**
         * @ngdoc method
         * @name ChooseTicketController.model.checkAvailability
         * @methodOf module.ChooseTicketController
         * @description
         * This a method will selected items to cart if its available
         *
         */
        model.checkAvailability = function() {
            if (Object.keys(model.event_price_select)
                .length > 0) {
                var session_id = $window.localStorage.getItem("session_id");
                if (angular.isDefined(session_id) && (session_id === null || session_id === '')) {
                    session_id = $rootScope.generateSession();
                    $window.localStorage.setItem("session_id", session_id);
                }
                model.tickets_details.event_id = model.bookings.Event[0].id;
                model.tickets_details.event_zone_id = model.bookings[0].event_venue_zone[0].id;
                model.tickets_details.event_schedule_id = $scope.startdate.id; //$scope.selectedEventSchedule.id;
                model.tickets_details.session_id = session_id;
                bestAvailableSeats.check_availabilty(model.tickets_details, function(response) {
                    if (angular.isDefined(response.error) && parseInt(response.error.code) === 0) {
                        // $window.localStorage.setItem("session_id", response.data[0].session_id);
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
            } else {
                $('.js-choose-seat')
                    .fadeIn('slow')
                    .delay(2000)
                    .fadeOut('fast');
            }
            return false;
        };
        /**
         * @ngdoc method
         * @name best_available.addPriceDetails
         * @methodOf module.ChooseTicketController
         * @description
         * This a method to add choosed seats through dropdown
         * Its a ng-change function in select box in each price type
         *
         */
        model.addPriceDetails = function(price_type, price, tickets) {
            // remove value from array if user unselect the value
            if (tickets === "") {
                angular.forEach(model.tickets_details.price_type, function(value, key) {
                    if (value.id === price_type.id) {
                        model.tickets_details.price_type.splice(key, 1);
                    }
                });
            } else {
                model.tickets_details.price_type.push({
                    'id': price_type.id,
                    'price': price,
                    'tickets': tickets
                });
            }
        };
        /**
         * @ngdoc method
         * @name ChooseTicketController.model.init
         * @methodOf module.ChooseTicketController
         * @description
         * This method will call model.getPriceTypes() function on initial loading
         */
        model.init = function() {
            model.getPriceTypes();
        };
        model.init();
        // to destroy all toolitp on this controll destroy event- it will close all toolitps on page change
        $scope.$on("$destroy", function() {
            $(".qtip")
                .remove();
        });
    }]);