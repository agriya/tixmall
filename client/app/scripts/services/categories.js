'use strict';
/**
 * @ngdoc service
 * @name tixmall.cities
 * @description
 * # cities
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('categories', ['$resource', function($resource) {
        return $resource('/api/v1/categories', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);