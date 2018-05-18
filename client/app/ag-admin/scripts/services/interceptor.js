'use strict';
/**
 * @ngdoc service
 * @name tixmallAdmin.interceptor
 * @description
 * # interceptor
 * Factory in the tixmallAdmin.
 */
angular.module('tixmallAdmin')
    .factory('interceptor', ['$q', '$location', '$injector', '$window', '$rootScope', '$cookies', function($q, $location, $injector, $window, $rootScope, $cookies) {
        return {
            // On response success
            response: function(response) {
                if (angular.isDefined(response.data)) {
                    if (angular.isDefined(response.data.error_message) && parseInt(response.data.error) === 1 && response.data.error_message === 'Authentication failed') {
                        $cookies.remove('auth', {
                            path: '/'
                        });
                        $cookies.remove('token', {
                            path: '/'
                        });
                        window.location = "#/users/login";
                    }
                }
                // Return the response or promise.
                return response || $q.when(response);
            },
            // On response failture
            responseError: function(response) {
                if (response.status === 401) {
                    if ($cookies.get("auth") !== null && $cookies.get("auth") !== undefined) {
                        var auth = JSON.parse($cookies.get("auth"));
                        var refresh_token = auth.refresh_token;
                        if (refresh_token === null || refresh_token === '' || refresh_token === undefined) {
                            $cookies.remove('auth', {
                                path: '/'
                            });
                            $cookies.remove('token', {
                                path: '/'
                            });
                            var redirectto = $location.absUrl()
                                .split('/#/');
                            redirectto = redirectto[0] + '#/users/login';
                            $rootScope.refresh_token_loading = false;
                            window.location.href = redirectto;
                        } else {
                            if ($rootScope.refresh_token_loading !== true) {
                                //jshint unused:false
                                $rootScope.refresh_token_loading = true;
                                var params = {};
                                var auth = JSON.parse($cookies.get("auth"));
                                params.token = auth.refresh_token;
                                var $http = $injector.get('$http');
                                $http({
                                        method: 'GET',
                                        url: '/api/v1/oauth/refresh_token',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        }
                                    })
                                    .success(function(response) {
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
                                                .split('/#/');
                                            redirectto = redirectto[0] + '#/users/login';
                                            $rootScope.refresh_token_loading = false;
                                            window.location.href = redirectto;
                                        }
                                        $timeout(function() {
                                            $window.location.reload();
                                        }, 1000);
                                    });
                            }
                        }
                    }
                }
                // Return the promise rejection.
                return $q.reject(response);
            },
            request: function(config) {
                var venueId;
                if (/\/venue_zone_previews$/.test(config.url) && config.method === 'POST') {
                    config.url = config.url.replace('venue_zone_previews', 'venues/' + config.data.venue_id + '/venue_zone_previews');
                }
                if (config.url.match(/venue_zone_previews\/[0-9]+/g) && config.method === 'PUT') {
                    venueId = $location.search()
                        .venue_id;
                    config.url = config.url.replace('venue_zone_previews', 'venues/' + venueId + '/venue_zone_previews');
                }
                if (config.url.match(/venue_zone_previews\/[0-9]+/g) && config.method === 'GET') {
                    venueId = $location.search()
                        .venue_id;
                    config.url = config.url.replace('venue_zone_previews', 'venues/' + venueId + '/venue_zone_previews');
                }
                if (config.url.match(/venue_zone_previews\/[0-9]+/g) && config.method === 'DELETE') {
                    venueId = $location.search()
                        .venue_id;
                    config.url = config.url.replace('venue_zone_previews', 'venues/' + venueId + '/venue_zone_previews');
                }
                return config;
            },
        };
    }])