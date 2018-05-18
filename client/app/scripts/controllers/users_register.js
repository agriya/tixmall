'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersRegisterCtrl
 * @description
 * # UsersRegisterCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('UsersRegisterCtrl', ['$rootScope', '$scope', 'usersRegister', 'flash', '$location', '$timeout', 'vcRecaptchaService', '$filter', '$window', '$cookies', function($rootScope, $scope, usersRegister, flash, $location, $timeout, vcRecaptchaService, $filter, $window, $cookies) {
        // used controller as syntax, assigned current scope to variable vm
        var vm = this;
        vm.user = {};
        $rootScope.header = $rootScope.settings.SITE_NAME + ' | ' + $filter("translate")("Register");
        $scope.save_btn = false;
        // captcha initialization
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
        /**
         * @ngdoc method
         * @name UsersRegisterCtrl.save
         * @methodOf module.UsersRegisterCtrl
         * @description
         * This method creates new user
         */
        $scope.save = function(userSignup) {
            /*jshint -W117 */
            var response = grecaptcha.getResponse();
            if (response.length > 0) {
                $scope.captchaVerified = true;
            } else {
                $scope.captchaVerified = false;
                //reCaptcha not verified
            }
            if (!$scope.save_btn) {
                var flashMessage;
                vm.user.dob = vm.events.startsAt;
                if (userSignup.$valid) {
                    usersRegister.create(vm.user, function(response) {
                        $scope.save_btn = true;
                        $scope.response = response;
                        if ($scope.response.error.code === 0) {
                            flashMessage = $filter("translate")("You have successfully registered with our site and your activation mail has been sent to your mail inbox. Please confirm your email for next login.");
                            flash.set(flashMessage, 'success', false);
                            if (parseInt($rootScope.settings.USER_IS_AUTO_LOGIN_AFTER_REGISTER)) {
                                $cookies.put('auth', JSON.stringify($scope.response), {
                                    path: '/'
                                });
                                $cookies.put('token', $scope.response.access_token, {
                                    path: '/'
                                });
                                $rootScope.user = $scope.response;
                                $scope.$emit('updateParent', {
                                    isAuth: true
                                });
                                if ($window.localStorage.getItem("redirect_url") !== null) {
                                    $location.path($window.localStorage.getItem("redirect_url"));
                                    $window.localStorage.removeItem('redirect_url');
                                } else {
                                    $location.path('/');
                                }
                            } else {
                                $timeout(function() {
                                    $location.path('/users/login');
                                }, 1000);
                            }
                        } else {
                            if (angular.isDefined(response.error.fields) && angular.isDefined(response.error.fields.email)) {
                                flashMessage = $filter("translate")("Already this email exists.");
                            } else if (angular.isDefined(response.error.fields) && angular.isDefined(response.error.fields.mobile)) {
                                flashMessage = $filter("translate")("Already this mobile number exists.");
                            } else if (angular.isDefined(response.error.fields) && angular.isDefined(response.error.fields.username)) {
                                flashMessage = $filter("translate")("Already this username exists.");
                            } else {
                                flashMessage = $filter("translate")("User could not be added. Please, try again later.");
                            }
                            flash.set(flashMessage, 'error', false);
                            $scope.save_btn = false;
                        }
                    });
                }
            } else {
                vcRecaptchaService.reload($scope.widgetId);
            }
        };
        // calendar
        //These variables MUST be set as a minimum for the calendar to work
        vm.calendarView = 'month';
        vm.viewDate = new Date();
        vm.events = [
        ];
        $scope.options = {
            maxDate: new Date(),
        };
        vm.isCellOpen = true;
        vm.toggle = function($event, field, event) {
            $event.preventDefault();
            $event.stopPropagation();
            event[field] = !event[field];
        };
        // user title static array with static values
        $scope.titles = [{
            "name": "Mr"
        }, {
            "name": "Mrs"
        }, {
            "name": "Miss"
        }];
    }]);