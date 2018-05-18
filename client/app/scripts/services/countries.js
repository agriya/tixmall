'use strict';
/**
 * @ngdoc service
 * @name tixmall.countries
 * @description
 * # countries
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('countries', ['$resource', function($resource) {
        return $resource('/api/v1/countries', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);