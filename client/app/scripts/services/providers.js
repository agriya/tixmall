'use strict';
/**
 * @ngdoc service
 * @name tixmall.providers
 * @description
 * # providers
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('providers', ['$resource', function($resource) {
        return $resource('/api/v1/providers', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);