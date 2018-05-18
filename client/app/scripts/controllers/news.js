'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:NewsCtrl
 * @description
 * # NewsCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('NewsCtrl', ['$scope', '$rootScope', '$state', 'newsView', 'news', 'md5', function($scope, $rootScope, $state, newsView, news, md5) {
        // used controller as syntax, assigned current scope to variable model
        var model = this;
        model.relatedNews = [];
        model.newsview = [];
        model.previousNews = [];
        model.nextNews = [];
        var params = {
            id: $state.params.id,
            is_active: true
        };
        /**
         * @ngdoc method
         * @name NewsCtrl.model.init
         * @methodOf module.NewsCtrl
         * @description
         * This method loads news its related news based on its category
         *
         */
        function getNewsAndRelatedNews() {
            newsView.get(params)
                .$promise.then(function(response) {
                    if (angular.isDefined(response.data)) {
                        model.newsview = response.data;
                    }
                    if (angular.isDefined(response.prev)) {
                        model.previousNews = response.prev;
                    }
                    if (angular.isDefined(response.next)) {
                        model.nextNews = response.next;
                    }
                    var category = '';
                    angular.forEach(response.data.news_category, function(value, i) {
                        if (i > 0) {
                            category += ',';
                        }
                        category += value.news_category_id;
                    });
                    if (angular.isDefined(response.data.attachments) && response.data.attachments !== null && angular.isDefined(response.data.attachments.id)) {
                        var hash = md5.createHash('News' + response.data.id + 'png' + 'large_thumb');
                        $scope.image_name = '/images/large_thumb/News/' + response.data.id + '.' + hash + '.png';
                    }
                    //Related news
                    var relatedParams = {
                        limit: 10,
                        active: true,
                        filter: 'related&&category_ids=' + category,
                    };
                    news.get(relatedParams)
                        .$promise.then(function(response) {
                            model.relatedNews = response.data;
                            angular.forEach(model.relatedNews, function(value) {
                                if (angular.isDefined(value.attachments) && value.attachments !== null && angular.isDefined(value.attachments.id)) {
                                    var hash = md5.createHash('News' + value.id + 'png' + 'small_thumb');
                                    value.image_name = '/images/small_thumb/News/' + value.id + '.' + hash + '.png';
                                }
                            });
                        });
                });
        }
        /**
         * @ngdoc method
         * @name NewsCtrl.model.init
         * @methodOf module.NewsCtrl
         * @description
         * This method loads news its related news based on its category
         *
         */
        model.init = function() {
            getNewsAndRelatedNews();
        };
        model.init();
    }]);