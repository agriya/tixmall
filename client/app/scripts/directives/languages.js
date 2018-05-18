'use strict';
/**
 * @ngdoc directive
 * @name tixmall.directive:languages
 * @scope
 * @restrict AE
 *
 * @description
 * It binds available languages in a dropdown

 */
angular.module('tixmall')
    .directive('languages', function() {
        return {
            templateUrl: 'views/languageDropdown.html',
            restrict: 'AE',
            link: function postLink(scope, element, attrs) {
                //jshint unused:false
            },
            controller: function(LocaleService, $scope, $rootScope, $window) {
                $scope.currentLocaleDisplayName = LocaleService.getLocaleDisplayName();
                $scope.localesDisplayNames = LocaleService.getLocalesDisplayNames();
                if ($scope.currentLocaleDisplayName === "Arabic") {
                    $rootScope.selectedCurrency = $rootScope.currency_codes[0];
                    $window.localStorage.setItem("currency", JSON.stringify($rootScope.currency_codes[0]));
                } else {
                    $rootScope.selectedCurrency = $rootScope.currency_codes[1];
                    $window.localStorage.setItem("currency", JSON.stringify($rootScope.currency_codes[1]));
                }
                $scope.visible = $scope.localesDisplayNames && $scope.localesDisplayNames.length > 1;
                $scope.changeLanguage = function(locale) {
                    LocaleService.setLocaleByDisplayName(locale);
                    $scope.currentLocaleDisplayName = locale;
                    if (locale === "Arabic") {
                        $rootScope.selectedCurrency = $rootScope.currency_codes[0];
                        $window.localStorage.setItem("currency", JSON.stringify($rootScope.currency_codes[0]));
                    } else {
                        $rootScope.selectedCurrency = $rootScope.currency_codes[1];
                        $window.localStorage.setItem("currency", JSON.stringify($rootScope.currency_codes[1]));
                    }
                };
            }
        };
    });