'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('HeaderCtrl', ['$scope', '$rootScope', '$window', function($scope, $rootScope, $window) {
        $scope.selectedCurrency = '';
        /**
         * @ngdoc method
         * @name FooterCtrl.dropdownItemSelected
         * @methodOf module.FooterCtrl
         * @description
         * Newsletter post function
         *
         * @param {} 
         * @returns {}
         */
        $scope.dropdownItemSelected = function(value, type) {
            if (type === "currency") {
                $rootScope.selectedCurrency = value;
                $window.localStorage.setItem("currency", JSON.stringify(value));
            }
        };
  }]);