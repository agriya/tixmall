'use strict';
/**
 * @ngdoc service
 * @name tixmall.usersForgotPassword
 * @description
 * # usersForgotPassword
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('usersForgotPassword', ['$resource', function($resource) {
        return $resource('/api/v1/users/forgot_password', {}, {
            forgetPassword: {
                method: 'POST'
            }
        });
    }]);