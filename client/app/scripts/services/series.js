'use strict';
/**
 * @ngdoc service
 * @name tixmall.states
 * @description
 * # states
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('series', ['$resource', function($resource) {
        return $resource('/api/v1/series', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);