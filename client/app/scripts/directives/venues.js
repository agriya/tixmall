'use strict';
/**
 * @ngdoc directive
 * @name tixmall.directive:venuesLists
 * @scope
 * @restrict E
 *
 * @description
 * It List venues listing in home page
 *
 * @param {type}  field   
 * type field might be venue, eventAndVenue or hotEvents
 *
 */
angular.module('tixmall')
    .directive('venuesLists', function(venues, md5, events) {
        return {
            templateUrl: 'views/venues_lists.html',
            restrict: 'E',
            scope: {
                type: '@'
            },
            link: function postLink(scope, element, attrs) {
                //jshint unused:false
                var params = {
                    // limit: 2,
                    is_active: true
                };
                //Getting venues list
                venues.get(params, function(response) {
                    if (angular.isDefined(response.data)) {
                        scope.venues = response.data;
                        angular.forEach(scope.venues, function(value) {
                            if (angular.isDefined(value.attachments) && value.attachments !== null) {
                                var hash = md5.createHash('Venue' + value.id + 'png' + 'big_thumb');
                                value.image_name = '/images/big_thumb/Venue/' + value.id + '.' + hash + '.png';
                            }
                        });
                    }
                });
                var limit = 4;
                if (scope.type === 'hotEvents') {
                    limit = 6;
                }
                var eventAndVenueParams = {
                    limit: 4,
                    is_active: true
                };
                //Getting past evnets on venues list
                events.get(eventAndVenueParams, function(response) {
                    if (angular.isDefined(response.data)) {
                        scope.eventsAndVenues = [
                            {
                                'FirstBlockEvents': [],
                                'SecondBlockEvents': [],
                            }
                        ];
                        angular.forEach(response.data, function(value) {
                            if (angular.isDefined(value.attachments) && value.attachments !== null) {
                                var hash = md5.createHash('Event' + value.id + 'png' + 'extra_normal_thumb');
                                value.image_name = '/images/extra_normal_thumb/Event/' + value.id + '.' + hash + '.png';
                            }
                            if (scope.eventsAndVenues[0].FirstBlockEvents.length < 2) {
                                scope.eventsAndVenues[0].FirstBlockEvents.push(value);
                            } else {
                                scope.eventsAndVenues[0].SecondBlockEvents.push(value);
                            }
                        });
                    }
                });
                // scrollbar theme configuration
                scope.scrollbarConfig = {
                    scrollInertia: 500,
                    theme: 'minimal'
                };
            }
        };
    });