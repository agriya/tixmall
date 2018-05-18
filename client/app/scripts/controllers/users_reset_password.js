'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersChangePasswordCtrl
 * @description
 * # UsersChangePasswordCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('UsersResetPasswordCtrl', ['$rootScope', '$scope', '$location', 'flash', 'userResetPassword', '$filter', '$window', '$state', function($rootScope, $scope, $location, flash, userResetPassword, $filter, $window, $state) {
        // used controller as syntax, assigned current scope to variable model
        var model = this;
        model.resetpassword = {};
        $rootScope.header = $rootScope.settings.SITE_NAME + ' | ' + $filter("translate")("Reset Password");
        $scope.save_btn = false;
        model.resetpassword.hash = $state.params.hash;
        /**
         * @ngdoc method
         * @name UsersResetPasswordCtrl.model.save
         * @methodOf module.UsersResetPasswordCtrl
         * @description
         * This method resets password with newpassword given by user
         */
        model.save = function() {
            if ($scope.userReset.$valid) {
                var flashMessage;
                $scope.save_btn = true;
                userResetPassword.resetPassword(model.resetpassword, function(response) {
                    $scope.response = response;
                    if ($scope.response.error.code === 0) {
                        flashMessage = $filter("translate")("Password has been changed.");
                        flash.set(flashMessage, 'success', false);
                        $location.path('/users/login');
                    } else {
                        flashMessage = $filter("translate")("Unable to reset password. Please try again.");
                        flash.set(flashMessage, 'error', false);
                        $scope.save_btn = false;
                    }
                });
            }
        };
    }]);