'use strict';
/**
 * @ngdoc service
 * @name tixmall.languages
 * @description
 * # languages
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('languages', ['$resource', function($resource) {
        return $resource('/api/v1/languages', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);