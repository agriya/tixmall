'use strict';
/**
 * @ngdoc service
 * @name tixmall.page
 * @description
 * # page
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('page', ['$resource', function($resource) {
        return $resource('/api/v1/pages/:id', {}, {
            get: {
                method: 'GET',
                params: {
                    id: '@id'
                }
            }
        });
    }]);