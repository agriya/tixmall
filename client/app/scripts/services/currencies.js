'use strict';
/**
 * @ngdoc service
 * @name tixmall.currencies
 * @description
 * # currencies
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('currencies', ['$resource', function($resource) {
        return $resource('/api/v1/currencies', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);