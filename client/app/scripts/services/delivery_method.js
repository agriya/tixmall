'use strict';
/**
 * @ngdoc service
 * @name tixmall.deliverymethod
 * @description
 * # deliverymethod
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('deliverymethod', ['$resource', function($resource) {
        return $resource('/api/v1/delivery_methods', {}, {
            get: {
                method: 'GET',
            }
        });
  }]);