'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersLoginCtrl
 * @description
 * # UsersLoginCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('UsersLoginCtrl', function($rootScope, $scope, $location, $http, $window, $timeout, progression, notification, $cookies) {
        $scope.save_btn = false;
        /**
         * @ngdoc method
         * @name UsersLoginCtrl.loginUser
         * @methodOf module.UsersLoginCtrl
         * @description
         * This method will check if user details are existing, if existing it will redirect to dashboard page else it will redirect to login page
         */
        $scope.loginUser = function() {
            if ($scope.userLogin.$valid && !$scope.save_btn) {
                $scope.save_btn = true;
                if ($rootScope.settings.USER_USING_TO_LOGIN === 'email') {
                    $scope.user.email = $scope.user.username;
                    delete $scope.user.username;
                }
                $http({
                        method: 'POST',
                        url: '/api/v1/users/login',
                        data: $scope.user
                    })
                    .success(function(response) {
                        $scope.response = response;
                        if ($scope.response.error.code === 0) {
                            $cookies.put('auth', JSON.stringify($scope.response), {
                                path: '/'
                            });
                            $cookies.put('token', $scope.response.access_token, {
                                path: '/'
                            });
                            progression.done();
                            notification.log('Your login successfull.', {
                                addnCls: 'humane-flatty-success'
                            });
                            $location.path('/dashboard');
                            $timeout(function() {
                                $window.location.reload();
                            });
                        } else {
                            progression.done();
                            notification.log('Your login credentials are invalid.', {
                                addnCls: 'humane-flatty-error'
                            });
                            $scope.user = {};
                            $scope.save_btn = false;
                        }
                    });
            }
        };
    });