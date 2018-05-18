'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:EventZoneCtrl
 * @description
 * # EventZoneCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('EventZoneCtrl', function($scope, $http, $state, notification, $location, $rootScope) {
        var event = this;
        $scope.actionType = $state.params.slug;
        event.venue_zone_details = [];
        event.event_zone_details = {};
        event.event_price_details = {};
        event.event_zone_details.eventsection = [];
        event.event_zone_details.event_id = $state.params.event_id;
        event.event_zone_details.venue_id = $state.params.venue_id;
        $scope.event_zone_section_rows = [];
        $scope.event_zone_sections = [];
        $scope.event_zone_prices = [];
        var event_zone_id = $state.params.id;
        event.selectedsections = {};
        event.selectedsections.section = [];
        event.selectedsections.row = [];
        if ($scope.actionType === 'update') {
            $http({
                    method: 'GET',
                    url: '/api/v1/event_zones/' + event_zone_id,
                })
                .success(function(response) {
                    event.event_zone_details = response.data;
                    $scope.event_zone_section_rows = response.data.event_zone_section_rows;
                    $scope.event_zone_sections = response.data.event_zone_sections;
                    $scope.event_zone_prices = response.data.event_zone_prices;
                    event.event_price_details = response.data.event_zone_prices;
                    event.zoneChange(event.event_zone_details.venue_zone_id);
                });
        }
        /**
         * @ngdoc method
         * @name EventZoneCtrl.save
         * @methodOf module.EventZoneCtrl
         * @description
         * This method is dropdown ng-change function
         */
        event.zoneChange = function(venue_id) {
            event.event_zone_array = [];
            $http({
                    method: 'GET',
                    url: '/api/v1/venue_zones/' + venue_id,
                })
                .success(function(response) {
                    // console.log(response);
                    angular.forEach(response.data.venue_zone_sections, function(sectionvalue) {
                        sectionvalue['row'] = []
                        angular.forEach(response.data.venue_zone_section_row, function(rowvalue) {
                            if (parseInt(sectionvalue.id) === parseInt(rowvalue.venue_zone_section_id)) {
                                sectionvalue['row'].push(rowvalue);
                            }
                        });
                        //console.log(sectionvalue);
                        event.event_zone_array.push({
                            "section": sectionvalue
                        });
                    });
                    // pushing already added price type details to checklist-model selectedsections.section and selectedsections.row array
                    if ($scope.actionType === 'update') {
                        angular.forEach($scope.event_zone_sections, function(zonevalue) {
                            angular.forEach(response.data.venue_zone_sections, function(venuevalue) {
                                if (venuevalue.id == zonevalue.venue_zone_section_id) {
                                    event.selectedsections.section.push(venuevalue);
                                }
                            })
                        });
                        angular.forEach($scope.event_zone_section_rows, function(zonevalue) {
                            angular.forEach(response.data.venue_zone_section_row, function(venuevalue) {
                                if (venuevalue.id == zonevalue.venue_zone_section_row_id) {
                                    event.selectedsections.row.push(venuevalue);
                                }
                            })
                        });
                    }
                });
        };
        /**
         * @ngdoc method
         * @name EventZoneCtrl.event.init
         * @methodOf module.EventZoneCtrl
         * @description
         * This method will get all available venue_zones based on venue_id and all available pricetypes.
         */
        event.init = function() {
            // get all venue zones details based on venue_id
            $http({
                    method: 'GET',
                    url: '/api/v1/venue_zones?limit=all&venue_id=' + event.event_zone_details.venue_id,
                })
                .success(function(response) {
                    event.venue_zone_details = response.data[0].venue_zone;
                    if (angular.isDefined(event.venue_zone_details) && event.venue_zone_details.length > 0) {
                        if ($scope.actionType !== 'update') {
                            event.event_zone_details.venue_zone_id = event.venue_zone_details[0].id;
                            event.zoneChange(event.event_zone_details.venue_zone_id);
                        }
                    }
                });
            //get all available price types
            $http({
                    method: 'GET',
                    url: '/api/v1/price_types?limit=all',
                })
                .success(function(response) {
                    event.price_types = response.data;
                });
        };
        event.init();
        /**
         * @ngdoc method
         * @name EventZoneCtrl.event.eventZoneAdd
         * @methodOf module.EventZoneCtrl
         * @description
         * This method will add event zone for given event in given venue
         */
        event.eventZoneAdd = function() {
            angular.forEach(event.selectedsections.section, function(sectionvalue) {
                var row_details = [];
                angular.forEach(event.selectedsections.row, function(rowvalue) {
                    if (parseInt(sectionvalue.id) === parseInt(rowvalue.venue_zone_section_id)) {
                        row_details.push(rowvalue.id);
                    }
                });
                event.event_zone_details.eventsection.push({
                    'venue_zone_section_id': sectionvalue.id,
                    'eventzonerow': {
                        'venue_zone_section_row_id': row_details
                    }
                })
            });
            if (angular.isDefined(event.event_zone_details) && Object.keys(event.event_zone_details.eventsection)
                .length > 0) {
                angular.forEach(event.event_zone_details, function(value, key) {
                    angular.forEach(event.event_zone_details.eventsection, function(value, key) {
                        value.eventzoneprice = event.event_price_details;
                    });
                });
            } else {
                event.event_zone_details.eventsection[0] = {};
                event.event_zone_details.eventsection[0].eventzoneprice = event.event_price_details
            }
            if (event.event_zone_details.venue_zone_id === undefined) {
                event.event_zone_details.venue_zone_id = 0;
                //event.event_zone_details.venue_zone_section_id = [];
            }
            $http({
                    method: 'POST',
                    url: '/api/v1/event_zones',
                    data: event.event_zone_details
                })
                .success(function(response) {
                    notification.log('Element Added Successfully.', {
                        addnCls: 'humane-flatty-success'
                    });
                    $location.path('/event_zones/list');
                });
        }
        /**
         * @ngdoc method
         * @name EventZoneCtrl.eventZoneUpdate
         * @methodOf module.EventZoneCtrl
         * @description
         * This method will update seats rows  and seats on event zone
         */
        $scope.eventZoneUpdate = function() {
            event.event_zone_details.eventsection = [];
            angular.forEach(event.selectedsections.section, function(sectionvalue) {
                var row_details = [];
                angular.forEach(event.selectedsections.row, function(rowvalue) {
                    if (parseInt(sectionvalue.id) === parseInt(rowvalue.venue_zone_section_id)) {
                        row_details.push(rowvalue.id);
                    }
                });
                event.event_zone_details.eventsection.push({
                    'venue_zone_section_id': sectionvalue.id,
                    'eventzonerow': {
                        'venue_zone_section_row_id': row_details
                    }
                })
            });
            if (angular.isDefined(event.event_zone_details)) {
                angular.forEach(event.event_zone_details.eventsection, function(value, key) {
                    value.eventzoneprice = event.event_price_details;
                });
            }
            // delete event.event_zone_details.available_count;
            delete event.event_zone_details.is_available;
            delete event.event_zone_details.event_zone_sections;
            delete event.event_zone_details.event_zone_section_rows;
            delete event.event_zone_details.event_zone_prices;
            $http({
                    method: 'PUT',
                    url: '/api/v1/event_zones/' + event_zone_id,
                    data: event.event_zone_details
                })
                .success(function(response) {
                    notification.log('Element Updates Successfully.', {
                        addnCls: 'humane-flatty-success'
                    });
                    $location.path('/event_zones/list');
                });
        }
        /**
         * @ngdoc method
         * @name EventZoneCtrl.isCheckedSections
         * @methodOf module.EventZoneCtrl
         * @description
         * This method will call on sections checkbox click
         */
        $scope.isCheckedSections = function(sections) {
            var items = $scope.event_zone_sections;
            // event.selectedsections.section.push(sections);
            for (var i = 0; i < items.length; i++) {
                if (sections.id === items[i].venue_zone_section_id) {
                    return true;
                }
            }
            return false;
        };
        /**
         * @ngdoc method
         * @name EventZoneCtrl.isCheckedSections
         * @methodOf module.EventZoneCtrl
         * @description
         * This method will call on rows checkbox click
         */
        $scope.isCheckedRows = function(rows) {
            var items = $scope.event_zone_section_rows;
            for (var i = 0; i < items.length; i++) {
                if (rows.id == items[i].venue_zone_section_row_id) {
                    return true;
                }
            }
            return false;
        };
        /**
         * @ngdoc method
         * @name EventZoneCtrl.isCheckedSections
         * @methodOf module.EventZoneCtrl
         * @description
         * This method will call on price types checkbox click
         */
        $scope.isCheckedPrices = function(price) {
            var items = $scope.event_zone_prices;
            for (var i = 0; i < items.length; i++) {
                if (price.id == items[i].price_type_id) return true;
            }
            return false;
        };
    });