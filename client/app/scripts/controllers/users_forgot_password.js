'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersForgotPasswordCtrl
 * @description
 * # UsersForgotPasswordCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('UsersForgotPasswordCtrl', ['$rootScope', '$scope', '$location', 'flash', 'usersForgotPassword', '$filter', 'vcRecaptchaService', function($rootScope, $scope, $location, flash, usersForgotPassword, $filter, vcRecaptchaService) {
        $rootScope.header = $rootScope.settings.SITE_NAME + ' | ' + $filter("translate")("Forgot Password");
        // used controller as syntax, assigned current scope to variable forgotPwdController
        var forgotPwdController = this;
        forgotPwdController.user = [];
        $scope.save_btn = false;
        $scope.forgot_pwd_request = false;
        if (parseInt($rootScope.settings.USER_IS_CAPTCHA_ENABLED_FORGOT_PASSWORD)) {
            $scope.model = {
                key: $rootScope.settings.GOOGLE_RECAPTCHA_CODE
            };
            $scope.setResponse = function(response) {
                $scope.response = response;
            };
            $scope.setWidgetId = function(widgetId) {
                $scope.widgetId = widgetId;
            };
            $scope.cbExpiration = function() {
                vcRecaptchaService.reload($scope.widgetId);
                $scope.response = null;
            };
            $scope.show_recaptcha = true;
        }
        /**
         * @ngdoc method
         * @name UsersForgotPasswordCtrl.save
         * @methodOf module.UsersForgotPasswordCtrl
         * @description
         * This method will send reset password links to the user, who have already have an account with our site
         */
        $scope.save = function() {
            if ($scope.userForgotPassword.$valid && !$scope.save_btn) {
                var flashMessage;
                $scope.save_btn = true;
                usersForgotPassword.forgetPassword(forgotPwdController.user, function(response) {
                    $scope.response = response;
                    if ($scope.response.error.code === 0) {
                        flashMessage = $filter("translate")("We have sent mail to reset your password. Please check it out");
                        flash.set(flashMessage, 'success', false);
                        $scope.forgot_pwd_request = true;
                    } else {
                        flashMessage = $filter("translate")("No email found");
                        flash.set(flashMessage, 'error', false);
                        $scope.save_btn = false;
                    }
                });
            }
            if ($scope.userForgotPassword.$invalid && parseInt($rootScope.settings.USER_IS_CAPTCHA_ENABLED_FORGOT_PASSWORD)) {
                vcRecaptchaService.reload($scope.widgetId);
            }
        };
    }]);