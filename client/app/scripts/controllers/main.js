'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('MainCtrl', ['$rootScope', '$scope', '$window', '$cookies', 'md5', 'refreshToken', '$location', 'countries', 'Cart', 'CountDown', 'currencies', function($rootScope, $scope, $window, $cookies, md5, refreshToken, $location, countries, Cart, CountDown, currencies) {
        $scope.isAuth = false;
        $scope.Cart = Cart;
        // Resume timer on page reload
        var timeDuration = $window.localStorage.getItem("timer");
        if (timeDuration > 0) {
            CountDown.startTimer(timeDuration);
        }
        if ($cookies.get("auth") !== null && $cookies.get("auth") !== undefined) {
            $scope.isAuth = true;
            $rootScope.user = JSON.parse($cookies.get("auth"));
        }
        // event to update authentication status
        $scope.$on('updateParent', function(event, args) {
            if (args.isAuth === true) {
                $scope.isAuth = true;
            } else {
                $scope.isAuth = false;
            }
        });
        if ($window.localStorage.getItem("location") !== null) {
            var location = JSON.parse($window.localStorage.getItem("location"));
            $rootScope.lat = location.lat;
            $rootScope.lang = location.lang;
            $rootScope.address = location.address;
            $rootScope.location_name = location.location_name;
        }
        //jshint unused:false
        // use refresh token service when current token expires
        var unregisterUseRefreshToken = $rootScope.$on('useRefreshToken', function(event, args) {
            //jshint unused:false
            $rootScope.refresh_token_loading = true;
            var params = {};
            var auth = JSON.parse($cookies.get("auth"));
            params.token = auth.refresh_token;
            refreshToken.get(params, function(response) {
                if (angular.isDefined(response.access_token)) {
                    $rootScope.refresh_token_loading = false;
                    $cookies.put('token', response.access_token, {
                        path: '/'
                    });
                } else {
                    $cookies.remove('auth', {
                        path: '/'
                    });
                    $cookies.remove('token', {
                        path: '/'
                    });
                    var redirectto = $location.absUrl()
                        .split('/#!/');
                    var find_admin = redirectto[0].split('/');
                    if (find_admin[find_admin.length - 1] === 'ag-admin') {
                        redirectto = redirectto[0] + '/ag-admin/#/users/login';
                    } else {
                        redirectto = redirectto[0] + '/#!/users/login';
                    }
                    $rootScope.refresh_token_loading = false;
                    window.location.href = redirectto;
                    $window.location.reload(true);
                }
            });
        });
        $scope.countries = [];
        var params = {
            limit: 'all'
        };
        $rootScope.currency_codes = [];
        /**
         * @ngdoc method
         * @name MainCtrl.formatCurrency
         * @methodOf module.MainCtrl
         * @description
         * This method changes price value with currency conversion rate
         *
         * @param {string} 
         */
        $rootScope.formatCurrency = function(price) {
            var currency;
            currency = $window.localStorage.getItem("currency");
            currency = JSON.parse(currency);
            if (currency !== null) {
                return price * currency.price;
            } else {
                return price;
            }
        };
        /**
         * @ngdoc method
         * @name MainCtrl.generateSession
         * @methodOf module.MainCtrl
         * @description
         * To generate session id and store session in local storage
         * @param {string} 
         */
        $rootScope.generateSession = function() {
            /*jshint -W117 */
            /*jslint bitwise: true */
            /*jslint eqeqeq: true */
            var d = new Date()
                .getTime();
            var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = (d + Math.random() * 16) % 16 | 0;
                d = Math.floor(d / 16);
                return (c == 'x' ? r : (r & 0x3 | 0x8))
                    .toString(16);
            });
            return uuid;
        };
        /**
         * @ngdoc method
         * @name MainCtrl.clearSession
         * @methodOf module.MainCtrl
         * @description
         * Clear session in in local storage
         *
         * @param {string} 
         */
        $rootScope.clearSession = function() {
            $window.localStorage.removeItem('session_id');
        };
        /**
         * @ngdoc method
         * @name MainCtrl.init
         * @methodOf module.MainCtrl
         * @description
         * This method loads countries and currencies on app load
         *
         * @param {string} 
         */
        $scope.init = function() {
            countries.get(params)
                .$promise.then(function(response) {
                    $scope.countries = response.data;
                });
            currencies.get()
                .$promise.then(function(response) {
                    if (response.data) {
                        angular.forEach(response.data, function(value) {
                            $rootScope.currency_codes.push({
                                "name": value.name,
                                "currency_code": value.code,
                                "price": value.price,
                                "currency_symbol": value.symbol
                            });
                        });
                        $window.localStorage.setItem("currency", JSON.stringify($rootScope.currency_codes[1]));
                        $rootScope.selectedCurrency = $rootScope.currency_codes[1];
                    }
                });
        };
        $scope.init();
  }]);