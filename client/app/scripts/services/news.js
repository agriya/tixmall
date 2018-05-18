'use strict';
/**
 * @ngdoc service
 * @name tixmall.news
 * @description
 * # news
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('news', ['$resource', function($resource) {
        return $resource('/api/v1/news', {}, {
            get: {
                method: 'GET'
            }
        });
  }])
    .factory('newsView', ['$resource', function($resource) {
        return $resource('/api/v1/news/:id', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);