'use strict';
/**
 * @ngdoc service
 * @name tixmall.userResetPassword
 * @description
 * # userResetPassword
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('userResetPassword', ['$resource', function($resource) {
        return $resource('/api/v1/users/reset_password/:hash', {}, {
            resetPassword: {
                method: 'PUT',
                params: {
                    hash: '@hash'
                }
            }
        });
    }]);