'use strict';
/**
 * @ngdoc service
 * @name tixmall.events
 * @description
 * # chooseSeat
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('chooseSeat', ['$resource', function($resource) {
        return $resource('/api/v1/events/:event_id/venue_zones/:zone_id/seats', {}, {
            get: {
                method: 'GET'
            }
        });
  }])
    .factory('bestAvailableSeats', ['$resource', function($resource) {
        return $resource('/api/v1/events/:event_id/best_available_seats', {}, {
            check_availabilty: {
                method: 'POST',
                params: {
                    event_id: '@event_id'
                }
            }
        });
  }]);