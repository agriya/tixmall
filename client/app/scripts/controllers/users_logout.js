'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersLogutCtrl
 * @description
 * # UsersLogutCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('UsersLogoutCtrl', ['$scope', 'usersLogout', '$location', '$cookies', 'flash', '$filter', function($scope, usersLogout, $location, $cookies, flash, $filter) {
        // used controller as syntax, assigned current scope to variable logout
        var logout = this;
        /**
         * @ngdoc method
         * @name UsersLogoutCtrl.logout.init
         * @methodOf module.UsersLogoutCtrl
         * @description
         * This method logout the user and clears all auth auth related events and details
         * It will redirect to home page once logged out
         */
        logout.init = function() {
            usersLogout.logout('', function(response) {
                $scope.response = response;
                if ($scope.response.error.code === 0) {
                    $cookies.remove('auth', {
                        path: '/'
                    });
                    $cookies.remove('token', {
                        path: '/'
                    });
                    var flashMessage = $filter("translate")("Logout successful.");
                    flash.set(flashMessage, 'success', false);
                    $scope.$emit('updateParent', {
                        isAuth: false
                    });
                    $location.path('/');
                }
            });
        };
        logout.init();
    }]);