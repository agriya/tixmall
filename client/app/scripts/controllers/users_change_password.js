'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersChangePasswordCtrl
 * @description
 * # UsersChangePasswordCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('UsersChangePasswordCtrl', ['$rootScope', '$scope', '$location', 'flash', 'usersChangePassword', '$filter', '$cookies', function($rootScope, $scope, $location, flash, usersChangePassword, $filter, $cookies) {
        // used controller as syntax, assigned current scope to variable model
        var model = this;
        $rootScope.header = $rootScope.settings.SITE_NAME + ' | ' + $filter("translate")("Change Password");
        $scope.save_btn = false;
        /**
         * @ngdoc method
         * @name UsersChangePasswordCtrl.save
         * @methodOf module.UsersChangePasswordCtrl
         * @description
         * This method will provide a option to changes new password
         */
        $scope.save = function() {
            if ($scope.userChangePassword.$valid) {
                var flashMessage;
                $scope.save_btn = true;
                model.user.id = $rootScope.user.id;
                usersChangePassword.changePassword(model.user, function(response) {
                    $scope.response = response;
                    if ($scope.response.error.code === 0) {
                        flashMessage = $filter("translate")("Password has been changed.");
                        flash.set(flashMessage, 'success', false);
                        if (parseInt($rootScope.settings.USER_IS_LOGOUT_AFTER_CHANGE_PASSWORD)) {
                            $cookies.remove('auth', {
                                path: '/'
                            });
                            $cookies.remove('token', {
                                path: '/'
                            });
                            $scope.$emit('updateParent', {
                                isAuth: false
                            });
                            $location.path('/users/login');
                        }
                    } else {
                        flashMessage = $filter("translate")("Unable to change the password. Please try again.");
                        flash.set(flashMessage, 'error', false);
                        $scope.save_btn = false;
                    }
                });
            }
        };
    }]);