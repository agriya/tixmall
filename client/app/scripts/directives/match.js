'use strict';
/**
 * @ngdoc directive
 * @name tixmall.directive:match
 * @description
 * Its check two scope values are matching or not, we have used thios directive for email and password confirm
 */
angular.module('tixmall')
    .directive('match', function($parse) {
        return {
            require: 'ngModel',
            link: function(scope, elem, attrs, ctrl) {
                scope.$watch(function() {
                    return $parse(attrs.match)(scope) === ctrl.$modelValue;
                }, function(currentValue) {
                    ctrl.$setValidity('mismatch', currentValue);
                });
            }
        };
    });