'use strict';
/**
 * @ngdoc service
 * @name tixmall.price_types
 * @description
 * # price_types
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('priceTypes', ['$resource', function($resource) {
        return $resource('/api/v1/price_types', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);