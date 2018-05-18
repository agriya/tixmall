'use strict';
/**
 * @ngdoc service
 * @name tixmall.page
 * @description
 * # page
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('pages', ['$resource', function($resource) {
        return $resource('/api/v1/pages', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);