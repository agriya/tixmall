'use strict';
/**
 * @ngdoc service
 * @name tixmall.orders
 * @description
 * # orders
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('orders', ['$resource', function($resource) {
        return $resource('/api/v1/orders/:session_id', {}, {
            createorder: {
                method: 'POST',
                params: {
                    session_id: '@session_id'
                }
            }
        });
  }])
    .factory('getorders', ['$resource', function($resource) {
        return $resource('/api/v1/orders', {}, {
            get: {
                method: 'GET',
                params: {
                    user_id: '@user_id'
                }
            }
        });
  }])
    .factory('getordersById', ['$resource', function($resource) {
        return $resource('/api/v1/orders/:orderid', {}, {
            get: {
                method: 'GET',
                params: {
                    orderid: '@orderid'
                }
            }
        });
  }])
    .factory('sendTickets', ['$resource', function($resource) {
        return $resource('/api/v1/send_tickets/:orderid', {}, {
            get: {
                method: 'GET',
                params: {
                    orderid: '@orderid'
                }
            }
        });
  }]);