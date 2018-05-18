'use strict';
/**
 * @ngdoc service
 * @name tixmall.contact
 * @description
 * # contact
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('contact', ['$resource', function($resource) {
        return $resource('/api/v1/contacts', {}, {
            create: {
                method: 'POST'
            }
        });
    }]);