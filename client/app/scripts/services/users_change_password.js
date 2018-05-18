'use strict';
/**
 * @ngdoc service
 * @name tixmall.usersChangePassword
 * @description
 * # usersChangePassword
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('usersChangePassword', ['$resource', function($resource) {
        return $resource('/api/v1/users/:id/change_password', {}, {
            changePassword: {
                method: 'PUT',
                params: {
                    id: '@id'
                }
            }
        });
    }]);