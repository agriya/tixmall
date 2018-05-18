'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:EventViewController
 * @description
 * # EventViewController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('EventScheduleController', ['eventView', '$state', 'md5', 'moment', '$scope', function(eventView, $state, md5, moment, $scope) {
        // used controller as syntax, assigned current scope to variable event
        var event = this;
        event.eventDetails = [];
        event.eventlists = [];
        event.day = moment();
        var eventViewParams = {
            id: $state.params.id
        };
        /**
         * @ngdoc method
         * @name event.refreshEvents
         * @methodOf module.EventScheduleController
         * @description
         * This method is callback function for calendar directive.
         * This mewthod loads calendar event data for each month click
         * Its a parent update callback function from directive
         *
         */
        event.refreshEvents = function(dateFilter) {
            $scope.dateFilter = dateFilter;
            if (angular.isDefined(dateFilter) && dateFilter !== undefined) {
                eventViewParams.event_date = dateFilter;
                eventViewParams.event_end_date = dateFilter;
            }
            eventView.get(eventViewParams)
                .$promise.then(function(response) {
                    if (angular.isDefined(response.data.event_schedule[dateFilter])) {
                        event.eventlists = response.data.event_schedule[dateFilter];
                        event.eventlists.length = response.data.event_schedule[dateFilter].length;
                    } else {
                        event.eventlists.length = 0;
                    }
                });
        };
        /**
         * @ngdoc method
         * @name event.init
         * @methodOf module.EventScheduleController
         * @description
         * This method fetches current event schedule details.
         *
         */
        event.init = function() {
            eventView.get(eventViewParams)
                .$promise.then(function(response) {
                    event.eventDetails = response.data;
                    if (angular.isDefined(response.data.attachments.filename) && response.data.attachments.filename !== 0) {
                        var hash = md5.createHash('Event' + response.data.id + 'png' + 'normal_thumb');
                        event.eventDetails.image_name = '/images/normal_thumb/Event/' + response.data.id + '.' + hash + '.png';
                    }
                    if (event.eventDetails.min_event_price === null) {
                        event.eventDetails.min_event_price = 0;
                    }
                });
        };
        event.init();
        var currentDate = new Date();
        event.refreshEvents(currentDate.getFullYear() + "-" + ('0' + (currentDate.getMonth() + 1))
            .slice(-2) + "-" + ('0' + currentDate.getDate())
            .slice(-2));
    }]);