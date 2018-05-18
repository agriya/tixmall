'use strict';
/**
 * @ngdoc service
 * @name tixmall.usersOrder
 * @description
 * # usersOrder
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('usersOrder', ['$resource', function($resource) {
        return $resource('/api/v1/users/:user_id/orders/:order_id', {}, {
            update: {
                method: 'PUT',
                params: {
                    user_id: '@user_id',
                    order_id: '@order_id'
                }
            }
        });
  }]);