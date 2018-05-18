'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:VenueViewController
 * @description
 * # VenueViewController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('VenueViewController', ['venueView', '$state', 'md5', function(venueView, $state, md5) {
        // used controller as syntax, assigned current scope to variable venue
        var venue = this;
        venue.venueDetails = [];
        venue.venueDetails.past_events = [];
        venue.venueDetails.upcoming_events = [];
        var venueViewParams = {
            id: $state.params.id,
            is_active: true,
            filter: 'upcoming',
        };
        var venuePastParams = {
            id: $state.params.id,
            is_active: true,
            filter: 'past',
        };
        /**
         * @ngdoc method
         * @name VenueViewController.venue.init
         * @methodOf module.VenueViewController
         * @description
         * This method binds venue and its past and upcoming events
         */
        venue.init = function() {
            var image_type;
            //upcoming events
            venueView.get(venueViewParams)
                .$promise.then(function(response) {
                    venue.venueDetails = response.data;
                    if (angular.isDefined(response.data.attachments) && response.data.attachments !== null) {
                        angular.forEach(response.data.attachments, function(attachment) {
                            image_type = attachment.filename.split(".");
                            if (image_type[1] === "svg") {} else {
                                var hash = md5.createHash('Venue' + response.data.id + 'png' + 'large_thumb');
                                venue.venueDetails.image_name = '/images/large_thumb/Venue/' + response.data.id + '.' + hash + '.png';
                            }
                        });
                    }
                    venue.venueDetails.upcoming_events = (response.data.events) ? response.data.events : [];
                    if (venue.venueDetails.upcoming_events.length > 0) {
                        angular.forEach(venue.venueDetails.upcoming_events, function(value) {
                            if (angular.isDefined(value.attachments) && value.attachments !== null) {
                                var hash = md5.createHash('Event' + value.id + 'png' + 'past_event_thumb');
                                value.image_name = '/images/past_event_thumb/Event/' + value.id + '.' + hash + '.png';
                            }
                        });
                    }
                });
            // Past events
            venueView.get(venuePastParams)
                .$promise.then(function(response) {
                    venue.venueDetails.past_events = (response.data.events) ? response.data.events : [];
                    if (venue.venueDetails.past_events.length > 0) {
                        angular.forEach(venue.venueDetails.past_events, function(value) {
                            if (angular.isDefined(value.attachments) && value.attachments !== null) {
                                var hash = md5.createHash('Event' + value.id + 'png' + 'past_event_thumb');
                                value.image_name = '/images/past_event_thumb/Event/' + value.id + '.' + hash + '.png';
                            }
                        });
                    }
                });
        };
        venue.init();
    }]);