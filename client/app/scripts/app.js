/*globals $:false */
'use strict';
/**
 * @ngdoc overview
 * @name tixmall
 * @description
 * # tixmall
 *
 * Main module of the application.
 */
angular.module('tixmall', [
    'ngResource',
    'ngSanitize',
    'satellizer',
    'ngAnimate',
    'ui.bootstrap',
    'ui.bootstrap.datetimepicker',
    'ui.router',
    'angular-growl',
    'google.places',
    'angular.filter',
    'ngCookies',
    'angular-md5',
    'ui.select2',
    'http-auth-interceptor',
    'vcRecaptcha',
    'angulartics',
    'pascalprecht.translate',
    'angulartics.google.analytics',
    'tmh.dynamicLocale',
    'mwl.calendar',
    'ngPasswordStrength',
    'angular-md5',
    'ngMessages',
    'ngScrollbars',
    'HashBangURLs'
])
    .config(['$stateProvider', '$urlRouterProvider', '$translateProvider', 'GeneralConfig', function($stateProvider, $urlRouterProvider, $translateProvider, GeneralConfig) {
        $urlRouterProvider.otherwise('/');
        $translateProvider.useStaticFilesLoader({
            prefix: 'scripts/l10n/',
            suffix: '.json'
        });
        $translateProvider.preferredLanguage(GeneralConfig.preferredlanguage);
        $translateProvider.useLocalStorage(); // saves selected language to localStorage
        // Enable escaping of HTML
        $translateProvider.useSanitizeValueStrategy(['sanitizeParameters']);
        //	$translateProvider.useCookieStorage();
    }])
    .config(function($stateProvider) {
        var getToken = {
            'TokenServiceData': function(TokenService, $q) {
                return $q.all({
                    AuthServiceData: TokenService.promise,
                    SettingServiceData: TokenService.promiseSettings
                });
            }
        };
        $stateProvider.state('home', {
                url: '/',
                templateUrl: 'views/home.html',
                controller: 'HomeCtrl as model',
                resolve: getToken
            })
            .state('users_settings', {
                url: '/users/settings',
                templateUrl: 'views/users_settings.html',
                controller: 'UsersSettingsCtrl as model',
                resolve: getToken
            })
            .state('users_addresses', {
                url: '/users/addresses',
                templateUrl: 'views/users_addresses.html',
                resolve: getToken
            })
            .state('users_change_password', {
                url: '/users/change_password',
                templateUrl: 'views/users_change_password.html',
                controller: 'UsersChangePasswordCtrl as model',
                resolve: getToken
            })
            .state('users_login', {
                url: '/users/login',
                templateUrl: 'views/users_login.html',
                controller: 'UsersLoginCtrl as loginController',
                resolve: getToken
            })
            .state('users_register', {
                url: '/users/register',
                templateUrl: 'views/users_register.html',
                controller: 'UsersRegisterCtrl as vm',
                resolve: getToken
            })
            .state('users_logout', {
                url: '/users/logout',
                controller: 'UsersLogoutCtrl as logout',
                resolve: getToken
            })
            .state('users_forgot_password', {
                url: '/users/forgot_password',
                controller: 'UsersForgotPasswordCtrl as forgotPwdController',
                templateUrl: 'views/users_forgot_password.html',
                resolve: getToken
            })
            .state('user_reset_password', {
                url: '/users/reset_password/{hash}',
                templateUrl: 'views/users_reset_password.html',
                controller: 'UsersResetPasswordCtrl as model',
                resolve: getToken
            })
            .state('contact', {
                url: '/contact',
                templateUrl: 'views/contact.html',
                controller: 'ContactCtrl',
                resolve: getToken
            })
            .state('users_address_add', {
                url: '/users/addresses/add',
                templateUrl: 'views/users_address_add.html',
                resolve: getToken
            })
            .state('news_view', {
                url: '/news/{id}/{slug}',
                templateUrl: 'views/news.html',
                controller: 'NewsCtrl as model',
                resolve: getToken
            })
            .state('pages_view', {
                url: '/pages/:id/:slug',
                templateUrl: 'views/pages_view.html',
                controller: 'PageViewCtrl as pageview',
                resolve: getToken
            })
            .state('event_schedule', {
                url: '/eventschedule/:id/:slug',
                templateUrl: 'views/event_schedule.html',
                controller: 'EventScheduleController as event',
                resolve: getToken
            })
            .state('users_activation', {
                url: '/users/activation/:user_id/:hash',
                templateUrl: 'views/users_activation.html',
                controller: 'UserActivationCtrl as activation',
                resolve: getToken
            })
            .state('event', {
                url: '/event/:id/:slug',
                templateUrl: 'views/event.html',
                controller: 'EventController as model',
                resolve: getToken
            })
            .state('venue', {
                url: '/venue/:id/:slug',
                templateUrl: 'views/venue_view.html',
                controller: 'VenueViewController as venue',
                resolve: getToken
            })
            .state('coming_soon', {
                url: '/comingsoon/{slug}',
                templateUrl: 'views/coming_soon.html',
                resolve: getToken
            })
            .state('choose_tickets', {
                url: '/events/:event_id/:venue_id/choose-tickets/:schedule_id',
                templateUrl: 'views/choose_ticket.html',
                controller: 'ChooseTicketController as model',
                onEnter: function($location, $anchorScroll) {
                    $location.hash('choose_ticket_container');
                    $anchorScroll();
                },
                resolve: getToken
            })
            .state('events', {
                url: '/events?date&venue_id&q&series_id',
                templateUrl: 'views/events.html',
                controller: 'EventsController as eventscontroller',
                resolve: getToken
            })
            .state('choose_seats', {
                url: '/events/:event_id/:zone_id/choose-seats/:schedule_id',
                templateUrl: 'views/choose_seat.html',
                controller: 'ChooseSeatController as model',
                resolve: getToken
            })
            .state('booking_basket', {
                url: '/booking/basket',
                templateUrl: 'views/booking_basket.html',
                controller: 'BookingBasketController as booking_basket',
                resolve: getToken
            })
            .state('secure_delivery', {
                url: '/secure/delivery',
                templateUrl: 'views/secure_delivery.html',
                controller: 'SecureDeliveryController as delivery',
                resolve: getToken
            })
            .state('secure_payment', {
                url: '/secure/payment',
                templateUrl: 'views/secure_payment.html',
                controller: 'SecurePaymentController as payment',
                resolve: getToken
            })
            .state('payment_result', {
                url: '/secure/payment/result',
                templateUrl: 'views/payment_result.html',
                controller: 'SecurePaymentResultController as paymentresult',
                resolve: getToken
            })
            .state('check_gift_vouchers', {
                url: '/account/checkgiftcertificate',
                templateUrl: 'views/check_gift_vouchers.html',
                controller: 'CheckGiftVouchersController as chkgift',
                resolve: getToken
            })
            .state('purchase_gift_vouchers', {
                url: '/giftcertificates/purchase',
                templateUrl: 'views/purchase_giftcertificate.html',
                controller: 'PurchaseGiftCertificateController as purchasegift',
                resolve: getToken
            })
            .state('saved_credit_cards', {
                url: '/accounts/cards',
                templateUrl: 'views/saved_credit_cards.html',
                controller: 'SavedCardsController as savedcards',
                resolve: getToken
            })
            .state('my_orders', {
                url: '/accounts/pastorders',
                templateUrl: 'views/my_orders.html',
                controller: 'MyOrdersController as myorders',
                resolve: getToken
            })
            .state('my_orders_view', {
                url: '/accounts/pastorders/{id}',
                templateUrl: 'views/my_orders_view.html',
                controller: 'MyOrdersViewController as myordersview',
                resolve: getToken
            })
            .state('best_availability', {
                url: '/booking/{event}/bestavailable/{venue}/{schedule_id}',
                templateUrl: 'views/best_available.html',
                controller: 'BestAvailableController as best_available',
                resolve: getToken
            })
            .state('multi_schedule', {
                url: '/production/{event_id}/{venue_id}',
                templateUrl: 'views/multi_schedule.html',
                controller: 'MultiScheduleController as model',
                resolve: getToken
            });
    })
    .config(['growlProvider', function(growlProvider) {
        growlProvider.onlyUniqueMessages(false);
        growlProvider.globalTimeToLive(5000);
        growlProvider.globalPosition('top-center');
        growlProvider.globalDisableCountDown(true);
    }])
    .run(function($rootScope, $location, $cookies, $state, GeneralConfig, Cart, store) {
        $rootScope.$state = $state;
        //sets constants values to rootScope
        $rootScope.GeneralConfig = GeneralConfig;
        $rootScope.$on('$stateChangeStart', function(event, toState, toParams, fromState, fromParams) {
            //jshint unused:false
            var url = toState.name;
            var exception_arr = ['home', 'users_login', 'users_register', 'users_forgot_password', 'search_restaurant_by_city_cuisine', 'search_restaurant', 'restaurant_view', 'pages_view', 'contact', 'coming_soon', 'user_reset_password', 'news_view', 'event_schedule', 'event', 'choose_tickets', 'choose_seats', 'events', 'venue', 'booking_basket', 'best_availability', 'multi_schedule'];
            if (url !== undefined) {
                if (exception_arr.indexOf(url) === -1 && $cookies.get("auth") === null) {
                    $location.path('/users/login');
                }
            }
            $rootScope.isHome = false;
            if (url === 'home') {
                $rootScope.isHome = true;
            }
        });
        //To stop loader after content loaded
        $rootScope.$on('$viewContentLoaded', function() {
            if (!$('#loader-wrapper')
                .hasClass('loader')) {
                $('body')
                    .addClass('loaded');
            }
        });
        //Saving cart items if anything modified 
        $rootScope.$on('Cart:change', function() {
            Cart.$save();
        });
        //scroll top to 0 on each route change
        $rootScope.$on('$stateChangeSuccess', function() {
            document.body.scrollTop = document.documentElement.scrollTop = 0;
        });
        if (angular.isObject(store.get('cart'))) {
            Cart.$restore(store.get('cart'));
        } else {
            Cart.init();
        }
    })
    .config(['$httpProvider',
        function($httpProvider) {
            $httpProvider.interceptors.push('interceptor');
            $httpProvider.interceptors.push('oauthTokenInjector');
        }
    ])
    .config(function(tmhDynamicLocaleProvider) {
        tmhDynamicLocaleProvider.localeLocationPattern('../bower_components/angular-i18n/angular-locale_{{locale}}.js');
    })
    .factory('interceptor', ['$q', '$location', 'flash', '$cookies', '$timeout', '$rootScope', function($q, $location, flash, $cookies, $timeout, $rootScope) {
        return {
            // On response success
            response: function(response) {
                if (angular.isDefined(response.data)) {
                    if (angular.isDefined(response.data.thrid_party_login)) {
                        if (angular.isDefined(response.data.error)) {
                            if (angular.isDefined(response.data.error.code) && parseInt(response.data.error.code) === 0) {
                                $cookies.put('auth', JSON.stringify(response.data.user), {
                                    path: '/'
                                });
                                $timeout(function() {
                                    location.reload(true);
                                });
                            } else {
                                flash.set(response.data.error.message, 'error', false);
                            }
                        }
                    }
                }
                // Return the response or promise.
                return response || $q.when(response);
            },
            // On response failture
            responseError: function(response) {
                // Return the promise rejection.
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
                                .split('/#!/');
                            var find_admin = redirectto[0].split('/');
                            if (find_admin[find_admin.length - 1] === 'ag-admin') {
                                redirectto = redirectto[0] + '/ag-admin/#/users/login';
                            } else {
                                redirectto = redirectto[0] + '/#!/users/login';
                            }
                            $rootScope.refresh_token_loading = false;
                            window.location.href = redirectto;
                        } else {
                            if ($rootScope.refresh_token_loading !== true) {
                                $rootScope.$broadcast('useRefreshToken');
                            }
                        }
                    }
                }
                return $q.reject(response);
            }
        };
    }])
    // home page event lisitng scrollbar style config
    .config(function(ScrollBarsProvider) {
        // scrollbar defaults
        ScrollBarsProvider.defaults = {
            autoHideScrollbar: true,
            setHeight: 280,
            scrollInertia: 0,
            axis: 'y',
            advanced: {
                updateOnContentResize: true
            },
            scrollButtons: {
                scrollAmount: 'auto', // scroll amount when button pressed
                enable: true // enable scrolling buttons by default
            }
        };
    })
    // sanitize dynamic content
    .filter('unsafe', function($sce) {
        return function(val) {
            return $sce.trustAsHtml(val);
        };
    })
    .filter('pricedisplay', ['$rootScope', function($rootScope) {
        return function(val) {
            var price = "Free";
            if (angular.isDefined(val)) {
                var valFormatted = (parseFloat(val)
                        .toFixed(2))
                    .toString()
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                price = $rootScope.settings.CURRENCY_SYMBOL + " " + valFormatted;
            }
            return price;
        };
    }])
    .filter('pricedisplayrange', ['$rootScope', function($rootScope) {
        return function(val) {
            var price = "Free";
            if (angular.isDefined(val)) {
                var splitPrice = val.split('-');
                if (parseFloat(splitPrice[0]) > 0 || parseFloat(splitPrice[1]) > 0) {
                    var valFormatted1 = (parseFloat(splitPrice[0])
                            .toFixed(2))
                        .toString()
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ","),
                        valFormatted2 = (parseFloat(splitPrice[1])
                            .toFixed(2))
                        .toString()
                        .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    price = "Tickets Starting From " + $rootScope.settings.CURRENCY_SYMBOL + " " + valFormatted1 + ' to ' + $rootScope.settings.CURRENCY_SYMBOL + " " + valFormatted2;
                }
            }
            return price;
        };
    }])
    .filter('htmlToPlaintext', function() {
        return function(text) {
            return text ? String(text)
                .replace(/<[^>]+>/gm, '') : '';
        };
    })
    // To customize dates
    .filter('moment', function(moment) {
        return function(dateString, format) {
            return moment(dateString)
                .format(format);
        };
    });
/**
 * @ngdoc module
 * @name HashBangURLs
 *
 * @description
 * To change location with #!
 *
 */
angular.module('HashBangURLs', [])
    .config(['$locationProvider', function($locationProvider) {
        $locationProvider.hashPrefix('!');
    }]);