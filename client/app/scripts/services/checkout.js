'use strict';
/**
 * @ngdoc service
 * @name tixmall.checkout
 * @description
 * # checkout
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('checkout', ['$resource', function($resource) {
        return $resource('/api/v1/checkout', {}, {
            createcheckout: {
                method: 'POST'
            }
        });
  }]);