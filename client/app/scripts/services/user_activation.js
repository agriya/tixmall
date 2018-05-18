'use strict';
/**
 * @ngdoc service
 * @name tixmall.userActivation
 * @description
 * # userActivation
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('userActivation', ['$resource', function($resource) {
        return $resource('/api/v1/users/:user_id/activation/:hash', {}, {
            activation: {
                method: 'PUT',
                params: {
                    user_id: '@user_id',
                    hash: '@hash'
                }
            }
        });
  }]);