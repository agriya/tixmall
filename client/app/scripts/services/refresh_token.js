'use strict';
/**
 * @ngdoc service
 * @name tixmall.refreshToken
 * @description
 * # refreshToken
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('refreshToken', ['$resource', function($resource) {
        return $resource('/api/v1/oauth/refresh_token', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);