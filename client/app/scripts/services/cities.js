'use strict';
/**
 * @ngdoc service
 * @name tixmall.cities
 * @description
 * # cities
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('cities', ['$resource', function($resource) {
        return $resource('/api/v1/cities', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);