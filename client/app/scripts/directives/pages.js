'use strict';
/**
 * @ngdoc directive
 * @name tixmall.directive:pages
 * @scope
 * @restrict E
 *
 * @description
 * It will bind static page content
 */
angular.module('tixmall')
    .directive('pages', function(pages) {
        return {
            template: '<div class="col-md-3"><h3 ng-bind="settings.SITE_NAME"></h3><ul class="list-unstyled text-muted"><li ng-repeat="page in pages"><a href="#!/pages/{{page.id}}/{{page.slug}}">{{page.title}}</a></li></ul></div>',
            restrict: 'E',
            replace: 'true',
            link: function postLink(scope, element, attrs) {
                //jshint unused:false
                var params = {
                    limit: 20,
                    is_active: true
                };
                pages.get(params, function(response) {
                    if (angular.isDefined(response.data)) {
                        scope.pages = response.data;
                    }
                });
            }
        };
    });