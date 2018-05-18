'use strict';
/**
 * @ngdoc directive
 * @name tixmall.directive:svgImage
 * @scope
 * @restrict E
 *
 * @description
 *
 */
angular.module('tixmall')
    .directive('svgImage', function() {
        return {
            restrict: 'E',
            replace: true,
            scope: {
                entry: '=',
                label: '@'
            },
            link: function(scope, element) {
                element.replaceWith('<object id="' + scope.label + '" data="' + scope.entry + '" style="width:100%;background-color:#C00D01;" /></object>');
            }
        };
    })
    .directive('venueZoneSvgImage', function() {
        return {
            restrict: 'E',
            replace: true,
            scope: {
                entry: '=',
                label: '@'
            },
            link: function(scope, element) {
                element.replaceWith('<object id="' + scope.label + '" data="' + scope.entry + '" class="choose-seat-bg" style="width:100%;height: 640px; border:1px solid black;background-color:#C00D01;"/></object>');
            }
        };
    });