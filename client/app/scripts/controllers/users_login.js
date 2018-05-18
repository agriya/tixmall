'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersLoginCtrl
 * @description
 * # UsersLoginCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('UsersLoginCtrl', ['$rootScope', '$scope', 'usersLogin', 'providers', '$auth', 'flash', '$window', '$location', '$filter', '$cookies', function($rootScope, $scope, usersLogin, providers, $auth, flash, $window, $location, $filter, $cookies) {
        // used controller as syntax, assigned current scope to variable loginController
        var loginController = this;
        loginController.user = {};
        $rootScope.header = $rootScope.settings.SITE_NAME + ' | ' + $filter("translate")("Login");
        $scope.already_register = 'true';
        if ($cookies.get("auth") !== null && $cookies.get("auth") !== undefined) {
            $scope.$emit('updateParent', {
                isAuth: true
            });
            $rootScope.header = $rootScope.settings.SITE_NAME + ' | Home';
            // $location.path('/');
        }
        if ($rootScope.settings.USER_USING_TO_LOGIN === 'email') {
            $scope.label = $filter("translate")("Email Address:");
            $scope.placeholder = $filter("translate")("Your Email");
        } else {
            $scope.label = $filter("translate")("Username:");
            $scope.placeholder = $filter("translate")("Username");
        }
        $scope.save_btn = false;
        /**
         * @ngdoc method
         * @name UsersLoginCtrl.save
         * @methodOf module.UsersLoginCtrl
         * @description
         * This method checks if user is logged in or not, it will redirect to dashboard page if user authenticated else it will goes to signup page.
         */
        $scope.save = function() {
            if ($rootScope.settings.USER_USING_TO_LOGIN === 'email') {
                loginController.user.email = loginController.user.username;
                delete loginController.user.username;
            }
            if ($scope.userLogin.$valid) {
                $scope.save_btn = true;
                usersLogin.login(loginController.user, function(response) {
                    $scope.response = response;
                    if ($scope.response.error.code === 0) {
                        $cookies.put('auth', JSON.stringify($scope.response), {
                            path: '/'
                        });
                        $cookies.put('token', $scope.response.access_token, {
                            path: '/'
                        });
                        $rootScope.user = $scope.response;
                        var flashMessage = $filter("translate")("Login successful.");
                        flash.set(flashMessage, 'success', false);
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
                        flash.set(response.error.message, 'error', false);
                        $scope.save_btn = false;
                    }
                });
            }
        };
        /**
         * @ngdoc method
         * @name UsersLoginCtrl.authenticate
         * @methodOf module.UsersLoginCtrl
         * @description
         * Social login authentication methodssss
         */
        $scope.authenticate = function(provider) {
            $auth.authenticate(provider);
        };
        // Providers list, but we have not used it
        var params = {};
        params.fields = 'name,icon_class,slug';
        params.is_active = true;
        providers.get(params, function(response) {
            $scope.providers = response.data;
        });
    }]);