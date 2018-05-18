'use strict';
/**
 * @ngdoc service
 * @name tixmall.usersRegister
 * @description
 * # usersRegister
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('usersRegister', ['$resource', function($resource) {
        return $resource('/api/v1/users/register', {}, {
            create: {
                method: 'POST'
            }
        });
    }]);