/*globals $:false */
'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:HomeCtrl
 * @description
 * # HomeCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('HomeCtrl', ['$scope', '$rootScope', '$filter', 'categories', 'md5', 'events', '$timeout', 'preloader', 'series', 'venues', '$state', function($scope, $rootScope, $filter, categories, md5, events, $timeout, preloader, series, venues, $state) {
        // used controller as syntax, assigned current scope to variable model
        var model = this;
        var hash;
        model.categories = [];
        model.eventData = [];
        $rootScope.header = $rootScope.settings.SITE_NAME + ' | ' + $filter("translate")("Home");
        $scope.tabContentLoaded = false;
        $scope.tabActive = 0;
        $scope.HomeFilter = {};
        /**
         * @ngdoc method
         * @name HomeCtrl.getContent
         * @methodOf module.HomeCtrl
         * @description
         * This method loads events on current category tab 
         * Its a click function for each tabs
         *
         * @param {string} 
         */
        $scope.open1 = function() {
            $scope.opened = true;
        };
        $scope.getContent = function(category_id) {
            $scope.owl_corousel_event = [];
            $scope.current_category = category_id;
            $scope.tabContentLoaded = false;
            var eventparams = {
                limit: 20,
                is_active: true,
                category_id: category_id,
            };
            model.homeEventBlock1 = [];
            model.homeEventBlock2 = [];
            model.homeEventBlock3 = [];
            //Indexes of images which have different size from other images
            model.extraLargeImages = [4, 8, 13, 17];
            events.get(eventparams, function(response) {
                if (response.data.length > 0) {
                    var overall_event_count = Math.ceil(response.data.length / 6);
                    $scope.owl_corousel_event = [];
                    for (var i = 1; i <= overall_event_count; i++) {
                        $scope.owl_corousel_event.push({
                            'FirstBlockevents': [],
                            'SecondBlockevents': [],
                        });
                    }
                    if (angular.isDefined(response.data)) {
                        model.eventData = response.data;
                        var temp_comments = [],
                            h = 0,
                            owl_corousel_event = 0,
                            event_count = 0,
                            first_loop = 1;
                        for (var j = 0; j < model.eventData.length; j++) {
                            temp_comments.push(model.eventData[j]);
                            event_count++;
                            h++;
                            if (temp_comments.length === 6 || h === model.eventData.length) {
                                if ((event_count === 6 || h === model.eventData.length) && first_loop > 1) {
                                    owl_corousel_event++;
                                    event_count = 0;
                                } else {
                                    event_count = 0;
                                }
                                angular.forEach(temp_comments, function(comment) {
                                    if ($scope.owl_corousel_event[owl_corousel_event].FirstBlockevents.length < 3) {
                                        $scope.owl_corousel_event[owl_corousel_event].FirstBlockevents.push(comment);
                                    } else if ($scope.owl_corousel_event[owl_corousel_event].SecondBlockevents.length < 3) {
                                        $scope.owl_corousel_event[owl_corousel_event].SecondBlockevents.push(comment);
                                    }
                                }); // jshint ignore:line
                                temp_comments = [];
                                first_loop++;
                            }
                        }
                        if ($scope.owl_corousel_event.length > 0) {
                            angular.forEach($scope.owl_corousel_event, function(corousel_event) {
                                if (corousel_event.FirstBlockevents.length > 0) {
                                    angular.forEach(corousel_event.FirstBlockevents, function(Frist_block_value, Frist_block_key) {
                                        if (Frist_block_key === 0) {
                                            if (angular.isDefined(Frist_block_value.attachments) && Frist_block_value.attachments !== null) {
                                                hash = md5.createHash('Event' + Frist_block_value.id + 'png' + 'large_home_thumb');
                                                Frist_block_value.image_name = '/images/' + 'large_home_thumb' + '/Event/' + Frist_block_value.id + '.' + hash + '.png';
                                            } else {
                                                Frist_block_value.image_name = '../images/no_image_838x492.png';
                                            }
                                        } else {
                                            if (angular.isDefined(Frist_block_value.attachments) && Frist_block_value.attachments !== null) {
                                                hash = md5.createHash('Event' + Frist_block_value.id + 'png' + 'medium_home_thumb');
                                                Frist_block_value.image_name = '/images/' + 'medium_home_thumb' + '/Event/' + Frist_block_value.id + '.' + hash + '.png';
                                            } else {
                                                Frist_block_value.image_name = '../images/no_image_428x492.png';
                                            }
                                        }
                                    });
                                    corousel_event.home_firstblk_events = [];
                                    corousel_event.home_firstblk_events.push(corousel_event.FirstBlockevents);
                                }
                                if (corousel_event.SecondBlockevents.length > 0) {
                                    angular.forEach(corousel_event.SecondBlockevents, function(Second_block_value, Second_block_key) {
                                        if (Second_block_key === 2) {
                                            if (angular.isDefined(Second_block_value.attachments) && Second_block_value.attachments !== null) {
                                                hash = md5.createHash('Event' + Second_block_value.id + 'png' + 'large_home_thumb');
                                                Second_block_value.image_name = '/images/' + 'large_home_thumb' + '/Event/' + Second_block_value.id + '.' + hash + '.png';
                                            } else {
                                                Second_block_value.image_name = '../images/img6.jpg';
                                            }
                                        } else {
                                            if (angular.isDefined(Second_block_value.attachments) && Second_block_value.attachments !== null) {
                                                hash = md5.createHash('Event' + Second_block_value.id + 'png' + 'medium_home_thumb');
                                                Second_block_value.image_name = '/images/' + 'medium_home_thumb' + '/Event/' + Second_block_value.id + '.' + hash + '.png';
                                            } else {
                                                Second_block_value.image_name = '../images/img5.jpg';
                                            }
                                        }
                                    });
                                    corousel_event.home_secondblk_events = [];
                                    corousel_event.home_secondblk_events.push(corousel_event.SecondBlockevents);
                                }
                            });
                        }
                    }
                }
                $scope.tabContentLoaded = true;
            });
        };
        $scope.sliderImageLoaded = false;
        $scope.myInterval = 5000;
        $scope.noWrapSlides = false;
        $scope.active = 0;
        /**
         * @ngdoc method
         * @name HomeCtrl.getSliderImages
         * @methodOf module.HomeCtrl
         * @description
         * This method loads slider images with event and venue details
         * By default it loads 5 events in slider
         *
         */
        function getSliderImages() {
            var sliderParams = {
                limit: 5,
                is_active: true,
            };
            events.get(sliderParams, function(response) {
                if (angular.isDefined(response.data)) {
                    model.sliderData = response.data;
                    angular.forEach(model.sliderData, function(value) {
                        if (angular.isDefined(value.attachments) && value.attachments !== null) {
                            var hash = md5.createHash('Event' + value.id + 'png' + 'large_thumb');
                            value.image_name = '/images/large_thumb/Event/' + value.id + '.' + hash + '.png';
                        }
                    });
                    $timeout(function() {
                        $scope.sliderImageLoaded = true;
                        $scope.tabContentLoaded = true;
                    }, 100);
                }
            });
        }
        $scope.submitHomeFilters = function() {
            if ($scope.date !== null && $scope.date !== undefined) {
                $scope.HomeFilter.date = $filter('date')($scope.date, 'yyyy-MM-dd');
            }
            $state.go('events', $scope.HomeFilter);
        };
        /**
         * @ngdoc method
         * @name HomeCtrl.model.init
         * @methodOf module.HomeCtrl
         * @description
         * This method loads slider content and categories
         */
        model.init = function() {
            getSliderImages();
            var params = {
                limit: 8,
                is_active: true
            };
            categories.get(params)
                .$promise.then(function(response) {
                    model.categories = response.data;
                    if (angular.isDefined(model.categories)) {
                        $scope.getContent(response.data[0].id);
                        $timeout(function() {
                            $('.nav-tabs li')
                                .removeClass('active');
                            $('.js-cat-class')
                                .first()
                                .addClass('active');
                            $('.nav-link .fa-chevron-down')
                                .parents('li')
                                .addClass('extra-menu');
                        }, 50);
                    }
                });
            venues.get()
                .$promise.then(function(response) {
                    $scope.venuesLists = response.data;
                });
            series.get()
                .$promise.then(function(response) {
                    $scope.seriesLists = response.data;
                });
        };
        model.init();
    }]);
angular.module('tixmall')
    .directive("owlCarousel", function() {
        return {
            restrict: 'E',
            transclude: false,
            link: function(scope) {
                scope.initCarousel = function(element) {
                    // provide any default options you want
                    var defaultOptions = {
                        responsiveClass: true,
                        margin: 30,
                        responsive: {
                            0: {
                                items: 1,
                                nav: true,
                                dots: false,
                            },
                            600: {
                                items: 1,
                                nav: true,
                                dots: false,
                            },
                            1000: {
                                items: 1,
                                nav: true,
                                loop: false,
                                dots: false,
                            }
                        }
                    };
                    $(element)
                        .owlCarousel(defaultOptions);
                };
            }
        };
    });
angular.module('tixmall')
    .directive('owlCarouselItem', [function() {
        return {
            restrict: 'A',
            transclude: false,
            link: function(scope, element) {
                if (scope.$last) {
                    scope.initCarousel(element.parent());
                }
            }
        };
}]);