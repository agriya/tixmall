'use strict';
/**
 * @ngdoc service
 * @name tixmall.states
 * @description
 * # states
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('states', ['$resource', function($resource) {
        return $resource('/api/v1/states', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);