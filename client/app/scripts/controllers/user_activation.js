'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UserActivationCtrl
 * @description
 * # UserActivationCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('UserActivationCtrl', ['$scope', '$location', 'flash', 'userActivation', '$stateParams', '$filter', function($scope, $location, flash, userActivation, $stateParams, $filter) {
        var element = {};
        // used controller as syntax, assigned current scope to variable activation
        var activation = this;
        element.user_id = $stateParams.user_id;
        element.hash = $stateParams.hash;
        /**
         * @ngdoc method
         * @name UserActivationCtrl.init
         * @methodOf module.UserActivationCtrl
         * @description
         * This method will check user activation
         */
        activation.init = function() {
            userActivation.activation(element, function(data) {
                $scope.data = data;
                if ($scope.data.error.code === 0) {
                    var flashMessage = $filter("translate")("Your account has been activated. Please login to continue.");
                    flash.set(flashMessage, 'success', false);
                } else {
                    flash.set($scope.data.error.message, 'error', false);
                }
                $location.path('/users/login');
            });
        };
  }]);