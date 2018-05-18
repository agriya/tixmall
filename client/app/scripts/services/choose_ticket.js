'use strict';
/**
 * @ngdoc service
 * @name tixmall.events
 * @description
 * # bookings
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('bookings', ['$resource', function($resource) {
        return $resource('/api/v1/events/:event_id/venue/:venue_id', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);