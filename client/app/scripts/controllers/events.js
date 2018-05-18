'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:EventsController
 * @description
 * # EventsController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('EventsController', ['events', '$state', 'md5', 'categories', 'series', 'venues', '$filter', function(events, $state, md5, categories, series, venues, $filter) {
        // used controller as syntax, assigned current scope to variable eventscontroller
        var eventscontroller = this;
        eventscontroller.eventLists = [];
        eventscontroller.categoriesLists = [];
        eventscontroller.venuesLists = [];
        eventscontroller.searchDate = decodeURIComponent($state.params.date);
        var eventParams = {};
        if (angular.isDefined(eventscontroller.searchDate) && eventscontroller.searchDate !== 'undefined') {
            eventParams.event_date = eventscontroller.searchDate;
            eventscontroller.date = new Date(eventscontroller.searchDate);
        }
        if (angular.isDefined(eventscontroller.searchDate) && eventscontroller.searchDate !== 'undefined') {
            eventParams.event_end_date = eventscontroller.searchDate;
        }
        if (angular.isDefined($state.params.q) && $state.params.q !== 'undefined' && $state.params.q !== null) {
            eventParams.q = $state.params.q;
        }
        if (angular.isDefined($state.params.venue_id) && $state.params.venue_id !== 'undefined' && $state.params.venue_id !== null) {
            eventParams.venue_id = parseInt($state.params.venue_id);
            eventscontroller.venue_id = parseInt($state.params.venue_id);
        }
        if (angular.isDefined($state.params.series_id) && $state.params.series_id !== 'undefined' && $state.params.series_id !== null) {
            eventscontroller.series_id = parseInt($state.params.series_id);
            eventParams.series_id = parseInt($state.params.series_id);
        }
        /**
         * @ngdoc method
         * @name eventscontroller.getEvents
         * @methodOf module.EventsController
         * @description
         * It gets events list
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.getEvents = function() {
            events.get(eventParams)
                .$promise.then(function(response) {
                    eventscontroller.eventLists = response.data;
                    angular.forEach(eventscontroller.eventLists, function(value) {
                        if (angular.isDefined(value.attachments) && value.attachments !== null) {
                            var hash = md5.createHash('Event' + value.id + 'png' + 'normal_thumb');
                            value.image_name = '/images/normal_thumb/Event/' + value.id + '.' + hash + '.png';
                        }
                    });
                });
        };
        /**
         * @ngdoc method
         * @name eventscontroller.getCategories
         * @methodOf module.EventsController
         * @description
         * It gets categories list
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.getCategories = function() {
            categories.get()
                .$promise.then(function(response) {
                    eventscontroller.categoriesLists = response.data;
                });
        };
        /**
         * @ngdoc method
         * @name eventscontroller.getVenues
         * @methodOf module.EventsController
         * @description
         * It gets venues list
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.getVenues = function() {
            venues.get()
                .$promise.then(function(response) {
                    eventscontroller.venuesLists = response.data;
                });
        };
        /**
         * @ngdoc method
         * @name eventscontroller.getSeries
         * @methodOf module.EventsController
         * @description
         * It gets available series list
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.getSeries = function() {
            series.get()
                .$promise.then(function(response) {
                    eventscontroller.seriesLists = response.data;
                });
        };
        /**
         * @ngdoc method
         * @name eventscontroller.seriesChange
         * @methodOf module.EventsController
         * @description
         * ng change function for series dropdown
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.seriesChange = function(series_id) {
            eventParams.series_id = series_id;
            eventscontroller.getEvents();
        };
        /**
         * @ngdoc method
         * @name eventscontroller.venuesChange
         * @methodOf module.EventsController
         * @description
         * ng change function for venues dropdown
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.venueChange = function(venue_id) {
            eventParams.venue_id = venue_id;
            eventscontroller.getEvents();
        };
        /**
         * @ngdoc method
         * @name eventscontroller.categoriesChange
         * @methodOf module.EventsController
         * @description
         * ng change function for series dropdown
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.categoryChange = function(cat_id) {
            eventParams.category_id = cat_id;
            eventscontroller.getEvents();
        };
        /**
         * @ngdoc method
         * @name eventscontroller.dateFilter
         * @methodOf module.EventsController
         * @description
         * Change function foe date deropdown
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.dateFilter = function(date) {
            if (date !== null && date !== undefined) {
                eventParams.event_date = $filter('date')(date, 'yyyy-MM-dd');
                eventParams.event_end_date = $filter('date')(date, 'yyyy-MM-dd');
                eventscontroller.getEvents();
            } else {
                eventParams.event_date = null;
                eventParams.event_end_date = null;
                eventscontroller.getEvents();
            }
        };
        /**
         * @ngdoc method
         * @name eventscontroller.init
         * @methodOf module.EventsController
         * @description
         * It gets events results, categories and venues results initially
         * It loads initially on controller starts
         *
         * @param {} 
         * @returns {}
         */
        eventscontroller.open1 = function() {
            eventscontroller.opened = true;
        };
        eventscontroller.init = function() {
            eventscontroller.getEvents();
            eventscontroller.getSeries();
            eventscontroller.getVenues();
            eventscontroller.getCategories();
        };
        eventscontroller.init();
    }]);