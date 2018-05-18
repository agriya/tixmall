'use strict';
/**
 * @ngdoc service
 * @name tixmall.usersLogin
 * @description
 * # usersLogin
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('usersLogin', ['$resource', function($resource) {
        return $resource('/api/v1/users/login', {}, {
            login: {
                method: 'POST'
            }
        });
    }]);