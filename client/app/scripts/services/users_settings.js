'use strict';
/**
 * @ngdoc service
 * @name tixmall.userProfile
 * @description
 * # userProfile
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('userSettings', ['$resource', function($resource) {
        return $resource('/api/v1/users/:id', {}, {
            update: {
                method: 'PUT',
                params: {
                    id: '@id'
                }
            },
            get: {
                method: 'GET',
                params: {
                    id: '@id'
                }
            }
        });
    }])
    .factory('occupations', ['$resource', function($resource) {
        return $resource('/api/v1/occupations', {}, {
            get: {
                method: 'GET'
            }
        });
    }])
    .factory('educations', ['$resource', function($resource) {
        return $resource('/api/v1/educations', {}, {
            get: {
                method: 'GET'
            }
        });
    }]);