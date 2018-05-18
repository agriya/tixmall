'use strict';
/**
 * @ngdoc service
 * @name tixmall.creditcard
 * @description
 * # creditcard
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('creditcard', ['$resource', function($resource) {
        return $resource('/api/v1/credit_cards', {}, {
            get: {
                method: 'GET'
            }
        });
  }])
    .factory('creditcardDelete', ['$resource', function($resource) {
        return $resource('/api/v1/credit_cards/:id', {}, {
            delete: {
                method: 'Delete',
                params: {
                    id: '@id'
                }
            }
        });
  }]);