'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersLoginCtrl
 * @description
 * # UsersLoginCtrl
 * This controller clear all auth information and redirected to login page
 */
angular.module('tixmallAdmin')
    .controller('UsersLogoutCtrl', function($scope, $location, $http, $window, adminTokenService, $q, $cookies) {
        $http({
                method: 'GET',
                url: '/api/v1/users/logout'
            })
            .success(function(response) {
                $scope.response = response;
                if ($scope.response.error.code === 0) {
                    $cookies.remove('auth', {
                        path: '/'
                    });
                    $cookies.remove('token', {
                        path: '/'
                    });
                    var promise = adminTokenService.promise;
                    var promiseSettings = adminTokenService.promiseSettings;
                    $q.all([
                           promiseSettings,
                           promise
                        ])
                        .then(function(value) {
                            $location.path('/users/login');
                        });
                }
            });
    });