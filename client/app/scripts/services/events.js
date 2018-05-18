'use strict';
/**
 * @ngdoc service
 * @name tixmall.events
 * @description
 * # news
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('events', ['$resource', function($resource) {
        return $resource('/api/v1/events', {}, {
            get: {
                method: 'GET',
                params: {
                    venue_id: '@venue_id',
                    series_id: '@series_id',
                    category_id: '@category_id'
                }
            }
        });
  }])
    .factory('eventView', ['$resource', function($resource) {
        return $resource('/api/v1/events/:id', {}, {
            get: {
                method: 'GET',
                params: {
                    type: "@type",
                    id: '@id'
                },
            }
        });
  }]);