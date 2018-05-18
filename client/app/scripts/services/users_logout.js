'use strict';
/**
 * @ngdoc service
 * @name tixmall.usersLogout
 * @description
 * # usersLogout
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('usersLogout', ['$resource', function($resource) {
        return $resource('/api/v1/users/logout', {}, {
            logout: {
                method: 'GET'
            }
        });
    }]);