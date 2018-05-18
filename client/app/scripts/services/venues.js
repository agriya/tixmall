'use strict';
/**
 * @ngdoc service
 * @name tixmall.venues
 * @description
 * # news
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('venues', ['$resource', function($resource) {
        return $resource('/api/v1/venues', {}, {
            get: {
                method: 'GET'
            }
        });
  }])
    .factory('venueView', ['$resource', function($resource) {
        return $resource('/api/v1/venues/:id', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);