'use strict';
/**
 * @ngdoc filter
 * @name tixmall.filter:dateFormat
 * @function
 * @description
 * # dateFormat
 * Filter in the tixmall.
 */
angular.module('tixmall')
    .filter('medium', function myDateFormat($filter) {
        return function(text) {
            var tempdate = new Date(text.replace(/(.+) (.+)/, "$1T$2Z"));
            return $filter('date')(tempdate, "medium");
        };
    });