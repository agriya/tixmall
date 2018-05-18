'use strict';
/**
 * @ngdoc directive
 * @name tixmall.directive:latestNews
 * @scope
 * @restrict E
 *
 * @description
 * To list latest news in home page, by default it has limit 3

 */
angular.module('tixmall')
    .directive('latestNews', function(news, md5) {
        return {
            templateUrl: 'views/latest_news.html',
            restrict: 'E',
            replace: true,
            link: function postLink(scope, element, attrs) {
                //jshint unused:false
                var newsparams = {
                    limit: 3,
                    sort_by: 'id'
                };
                var hash;
                //It list latest news in home page
                news.get(newsparams)
                    .$promise.then(function(response) {
                        scope.news = response.data;
                        angular.forEach(scope.news, function(value) {
                            if (angular.isDefined(value.attachments) && value.attachments !== null && angular.isDefined(value.attachments.id)) {
                                hash = md5.createHash('News' + value.id + 'png' + 'medium_thumb');
                                value.image_name = '/images/medium_thumb/News/' + value.id + '.' + hash + '.png';
                            }
                        });
                    });
            }
        };
    });