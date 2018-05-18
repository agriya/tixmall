'use strict';
/**
 * @ngdoc service
 * @name tixmall.sessionService
 * @description
 * # sessionService
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .service('TokenService', function($rootScope, $http, $cookies, $q) {
        //jshint unused:false
        var promise;
        var promiseSettings;
        var deferred = $q.defer();
        if ($cookies.get("token") === null || $cookies.get("token") === undefined) {
            promise = $http({
                    method: 'GET',
                    url: '/api/v1/oauth/token',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .success(function(data) {
                    if (angular.isDefined(data.access_token)) {
                        $cookies.put("token", data.access_token, {
                            path: '/'
                        });
                    }
                });
        } else {
            promise = true;
        }
        if (angular.isUndefined($rootScope.settings)) {
            $rootScope.settings = {};
            promiseSettings = $http({
                    method: 'GET',
                    url: '/api/v1/settings?type=all',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .success(function(response) {
                    if (angular.isDefined(response.data)) {
                        angular.forEach(response.data, function(value, key) {
                            //jshint unused:false
                            $rootScope.settings[value.name] = value.value;
                        });
                    }
                });
        } else {
            promiseSettings = true;
        }
        return {
            promise: promise,
            promiseSettings: promiseSettings
        };
    });