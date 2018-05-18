'use strict';
/**
 * @ngdoc service
 * @name tixmall.newsletters
 * @description
 * # newsletters
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('newsletters', ['$resource', function($resource) {
        return $resource('/api/v1/newsletters', {}, {
            get: {
                method: 'GET'
            }
        });
  }]);