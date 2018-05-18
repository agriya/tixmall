'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:ContactCtrl
 * @description
 * # ContactCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('ContactCtrl', ['$rootScope', '$scope', 'contact', 'flash', 'vcRecaptchaService', '$filter', function($rootScope, $scope, contact, flash, vcRecaptchaService, $filter) {
        $scope.save_btn = false;
        $scope.model = {
            key: $rootScope.settings.GOOGLE_RECAPTCHA_CODE
        };
        $scope.setResponse = function(response) {
            $scope.captchaVerified = true;
            $scope.response = response;
        };
        $scope.setWidgetId = function(widgetId) {
            $scope.widgetId = widgetId;
        };
        $scope.cbExpiration = function() {
            vcRecaptchaService.reload($scope.widgetId);
            $scope.response = null;
        };
        $scope.captchaVerified = false;
        //reCaptch verified
        /**
         * @ngdoc method
         * @name save
         * @methodOf module.ContactCtrl
         * @description
         * This a method to post contact information
         *
         */
        $scope.save = function() {
            /*jshint -W117 */
            var response = grecaptcha.getResponse();
            if (response.length > 0) {
                $scope.captchaVerified = true;
            } else {
                $scope.captchaVerified = false;
                //reCaptcha not verified
            }
            if ($scope.contactForm.$valid) {
                var flashMessage;
                $scope.save_btn = true;
                contact.create($scope.contact, function(response) {
                    $scope.response = response;
                    if ($scope.response.error.code === 0) {
                        flashMessage = $filter("translate")("We will contact you shortly.");
                        flash.set(flashMessage, 'success', false);
                        $scope.contact = {};
                        $scope.save_btn = false;
                        vcRecaptchaService.reload($scope.widgetId);
                    } else {
                        flash.set(response.error.message, 'error', false);
                        $scope.save_btn = false;
                    }
                });
            } else {
                vcRecaptchaService.reload($scope.widgetId);
            }
        };
    }]);