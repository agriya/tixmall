var ngapp = angular.module('tixmallAdmin', ['ng-admin', 'http-auth-interceptor', 'checklist-model', 'ui.bootstrap', 'ui.bootstrap.datetimepicker', 'ngCookies']);
var admin_api_url = '/';
var limit_per_page = 20;
var VenueID;
var venue_zone;
var auth;
var $cookies;
angular.injector(['ngCookies'])
    .invoke(['$cookies', function(_$cookies_) {
        $cookies = _$cookies_;
}]);
if ($cookies.get('auth') !== undefined && $cookies.get('auth') !== null) {
    auth = JSON.parse($cookies.get('auth'));
}
ngapp.constant('user_roles', {
    admin: 1,
    user: 2,
    organizer: 3
});
ngapp.config(['$httpProvider',
    function($httpProvider) {
        $httpProvider.interceptors.push('interceptor');
        $httpProvider.interceptors.push('oauthTokenInjector');
    }
]);
ngapp.config(function($stateProvider) {
    var getToken = {
        'TokenServiceData': function(adminTokenService, $q) {
            return $q.all({
                AuthServiceData: adminTokenService.promise,
                SettingServiceData: adminTokenService.promiseSettings
            });
        }
    };
    $stateProvider.state('login', {
            url: '/users/login',
            templateUrl: 'views/users_login.html',
            controller: 'UsersLoginCtrl',
            resolve: getToken
        })
        .state('eventzoneCreate', {
            parent: 'main',
            url: '/eventszone/:slug?event_id&venue_id',
            templateUrl: 'views/event_zone.html',
            controller: 'EventZoneCtrl',
            controllerAs: 'event',
            resolve: getToken
        })
        .state('eventzonEdit', {
            parent: 'main',
            url: '/eventszone/:slug/:id?event_id&venue_id',
            templateUrl: 'views/event_zone.html',
            controller: 'EventZoneCtrl',
            controllerAs: 'event',
            resolve: getToken
        })
        .state('demographics_reports', {
            parent: 'main',
            url: '/demographics-reports',
            templateUrl: 'views/demographic_reports.html',
            controller: 'DemoGraphicCtrl',
            controllerAs: 'demographics_reports',
            resolve: getToken
        })
        .state('sales_reports', {
            parent: 'main',
            url: '/sales-reports',
            templateUrl: 'views/sales_reports.html',
            controller: 'SalesReportCtrl',
            controllerAs: 'sales_reports',
            resolve: getToken
        })
        .state('sales_detailed_reports', {
            parent: 'main',
            url: '/sales-deatiled-reports?start_date&end_date',
            templateUrl: 'views/sales_detailed_reports.html',
            controller: 'SalesDetailedReportCtrl',
            controllerAs: 'sales_detailed_reports',
            resolve: getToken
        })
        .state('capacity_reports', {
            parent: 'main',
            url: '/capacity-reports',
            templateUrl: 'views/capacity_reports.html',
            controller: 'CapacityReportCtrl',
            controllerAs: 'capacity_reports',
            resolve: getToken
        })
        .state('financial_reports', {
            parent: 'main',
            url: '/financial-reports',
            templateUrl: 'views/financial_reports.html',
            controller: 'FinancialReportCtrl',
            controllerAs: 'financial_reports',
            resolve: getToken
        })
        .state('participant_reports', {
            parent: 'main',
            url: '/participants-reports',
            templateUrl: 'views/participants_reports.html',
            controller: 'ParticipantReportCtrl',
            controllerAs: 'participant_reports',
            resolve: getToken
        })
        .state('visitors_reports', {
            parent: 'main',
            url: '/visitors-reports',
            templateUrl: 'views/visitors_reports.html',
            controller: 'VisitorsReportCtrl',
            controllerAs: 'visitors_reports',
            resolve: getToken
        })
        .state('invitation_lists', {
            parent: 'main',
            url: '/invitation-lists',
            templateUrl: 'views/invitaion-lists.html',
            controller: 'InvitationListCtrl',
            controllerAs: 'invitation_lists',
            resolve: getToken
        })
        .state('invitation_visitors', {
            parent: 'main',
            url: '/invitation-visitors',
            templateUrl: 'views/invitaion-visitors.html',
            controller: 'InvitationVisitorsCtrl',
            controllerAs: 'invitation_visitors',
            resolve: getToken
        })
        .state('invitation_send', {
            parent: 'main',
            url: '/invitation-send',
            templateUrl: 'views/invitaion-send.html',
            controller: 'InvitationSendCtrl',
            controllerAs: 'invitation_send',
            resolve: getToken
        })
        .state('logout', {
            url: '/users/logout',
            controller: 'UsersLogoutCtrl',
            resolve: getToken
        });
});
ngapp.directive('uploadImage', function() {
    return {
        restrict: 'E',
        scope: {
            entry: '&',
            class: '@'
        },
        controller: function($http, $scope, Upload, notification) {
            $scope.show_error = false;
            $scope.upload = function() {
                if ($scope.file) {
                    Upload.upload({
                            url: '/api/v1/attachments?class=' + $scope.class,
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            },
                            file: $scope.file
                        })
                        .then(function(response) {
                            $scope.response = response.data;
                            if ($scope.response.error.code === 0) {
                                $scope.show_error = false;
                                $scope.file.filename = $scope.response.attachment;
                                $scope.entry()
                                    .values.image = $scope.response.attachment;
                            }
                            if ($scope.response.error.code === 1) {
                                $scope.show_error = true;
                                notification.log("Image not uploaded.", {
                                    addnCls: 'humane-flatty-error'
                                });
                                return false;
                            }
                        }, function() {}, function(evt) {
                            $scope.file.progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                        });
                }
            };
        },
        template: '<div class="row"><div class="col-md-2"><button type="file" name="attachment" ng-model="file" ngf-select="upload()" class="btn btn-default" accept="image/*"><span translate="BROWSE">Browse</span></button></div><span class="text-danger" ng-if="show_error">&nbsp;Upload image with 1200 x 800 Dimension</span><div class="col-md-10" ng-show="file.progress >= 0 && !show_error"><div class="row"><div class="col-md-3 "><div class="progress" ng-show="file.progress >= 0 && file.progress < 100">                                              <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="{{file.progress}}" style="width:{{file.progress}}%"> <span></span> </div></div></div><div class="col-md-9">{{file.filename}}</div></div></div></div>'
    }
})
ngapp.directive('googlePlaces', ['$location', function($location) {
    return {
        restrict: 'E',
        scope: {
            entity: "&",
            entityName: "@",
            entry: "&",
            size: "@",
            label: "@"
        },
        link: function(scope) {
            var inputFrom = document.getElementById('goo-place');
            var autocompleteFrom = new google.maps.places.Autocomplete(inputFrom);
            google.maps.event.addListener(autocompleteFrom, 'place_changed', function() {
                var place = autocompleteFrom.getPlace();
                scope.entry()
                    .values.latitude = place.geometry.location.lat();
                scope.entry()
                    .values.longitude = place.geometry.location.lng();
                var k = 0;
                angular.forEach(place.address_components, function(value, key) {
                    //jshint unused:false
                    if (value.types[0] === 'locality' || value.types[0] === 'administrative_area_level_2') {
                        if (k === 0) {
                            scope.entry()
                                .values['city_name'] = value.long_name;
                        }
                        if (value.types[0] === 'locality') {
                            k = 1;
                        }
                    }
                    if (value.types[0] === 'administrative_area_level_1') {
                        scope.entry()
                            .values['state_name'] = value.long_name;
                    }
                    if (value.types[0] === 'country') {
                        scope.entry()
                            .values['country_iso2'] = value.short_name;
                    }
                    if (value.types[0] === 'postal_code') {
                        scope.entry()
                            .values.zip_code = parseInt(value.long_name);
                    }
                });
                scope.$apply();
            });
        },
        template: '<input class="form-control" id="goo-place"/>'
    };
}]);
ngapp.directive('customDropdown', ['$location', '$http', function($location, $http) {
    return {
        restrict: 'E',
        scope: {
            entity: "&",
            entityName: "@",
            entry: "&",
            size: "@",
            label: "@",
            type: "@",
            default: "@"
        },
        link: function(scope, elem) {},
        controller: function($http, $scope, $rootScope) {
            var model = this;
            var end_point_url;
            var that = this;
            if ($scope.type === "section") {
                end_point_url = admin_api_url + 'api/v1/venue_zone_sections?venue_zone_id=' + $scope.entry()
                    .values.venue_zone_id + '&limit=all';
                $http.get(end_point_url)
                    .then(function(response) {
                        $scope.section_dropdown_details = response.data.data;
                        if ($scope.default === "true") {
                            if ($scope.type === "section") {
                                $scope.sectionValue = $scope.entry()
                                    .values.venue_section_id;
                                $scope.changeDropdownValues("section", $scope.sectionValue);
                                $rootScope.rowValue = $scope.entry()
                                    .values.venue_section_row_id;
                                $scope.changeDropdownValues("row", $rootScope.rowValue);
                                $rootScope.seatValue = $scope.entry()
                                    .values.venue_section_row_seat_id;
                                $scope.changeDropdownValues("seat", $rootScope.seatValue);
                            }
                        }
                    });
            }
            /**
             * @ngdoc method
             * @name customDropdownr.changeDropdownValues
             * @methodOf customDropdownr
             * @description
             * This method is ng-change function for cascading dropdowns
             * Created dynamic dropdowns for venue sections, rows and seats
             * Rows value should be changed based on section value and seats value should be changed based on row value changes
             * Here we gonna apply filters based on dropdown changes
             */
            $scope.changeDropdownValues = function(type, value) {
                if (type === "seat") {
                    $scope.entry()
                        .values.venue_section_row_seat_id = value;
                }
                if (type === "row") {
                    end_point_url = admin_api_url + 'api/v1/venue_zone_section_seats?venue_zone_section_row_id=' + value + '&limit=all';
                    $scope.entry()
                        .values.venue_section_row_id = value;
                }
                if (type === "section") {
                    end_point_url = admin_api_url + 'api/v1/venue_zone_section_rows?venue_zone_section_id=' + value + '&limit=all';
                    $scope.entry()
                        .values.venue_section_id = value;
                }
                if (type !== "seat") {
                    $http.get(end_point_url)
                        .then(function(response) {
                            if (type === "section") {
                                $rootScope.row_dropdown_details = response.data.data;
                            }
                            if (type === "row") {
                                $rootScope.seat_dropdown_details = response.data.data;
                            }
                        });
                }
            }
        },
        template: "<select class=\"form-control width-auto\" ng-model=\"sectionValue\" ng-show=\"type === 'section'\" ng-change=\"changeDropdownValues(type, sectionValue)\" ng-options=\"section.id as section.name for section in section_dropdown_details\" >" + "<option value=\"\">Please select section</option></select> <select class=\"form-control width-auto\" ng-model=\"$root.rowValue\" ng-show=\"type === 'row'\" ng-change=\"changeDropdownValues(type, $root.rowValue)\" ng-options=\"row.id as row.name for row in $root.row_dropdown_details\" >" + "<option value=\"\">Please select row</option></select> <select class=\"form-control width-auto\" ng-model=\"$root.seatValue\" ng-show=\"type === 'seat'\" ng-change=\"changeDropdownValues(type, $root.seatValue)\" ng-options=\"seat.id as seat.seat_number for seat in $root.seat_dropdown_details\">" + "<option value=\"\">Please select seat</option></select>"
    };
}]);
ngapp.directive('inputType', function() {
    return {
        restrict: 'E',
        scope: {
            entity: "&",
            entry: "&"
        },
        link: function(scope, elem, attrs) {
            elem.bind('change', function() {
                scope.$apply(function() {
                    scope.entry()
                        .values.value = scope.value;
                    if (scope.entry()
                        .values.type === 'checkbox') {
                        scope.entry()
                            .values.value = scope.value ? 1 : 0;
                    }
                });
            });
        },
        controller: function($scope) {
            $scope.text = true;
            $scope.value = $scope.entry()
                .values.value;
            if ($scope.entry()
                .values.type === 'checkbox') {
                $scope.text = false;
                $scope.value = Number($scope.value);
            }
        },
        template: '<textarea ng-model="$parent.value" id="value" name="value" class="form-control" ng-if="text"></textarea><input type="checkbox" ng-model="$parent.value" id="value" name="value" ng-if="!text" ng-true-value="1" ng-false-value="0" ng-checked="$parent.value == 1"/>'
    };
});
//Custom  Dashboard
//Referenced Link: http://ng-admin-book.marmelab.com/doc/Dashboard.html
//Created custom directive for header, reference http://ng-admin-book.marmelab.com/doc/Custom-pages.html keyword - directive.
//Template files created under 'tpl' directory.
ngapp.directive('dashboardSummary', ['$location', '$state', '$http', function($location, $state, $http) {
    return {
        restrict: 'E',
        scope: {
            entity: "&",
            entityName: "@",
            entry: "&",
            size: "@",
            label: "@",
            revenueDetails: "&"
        },
        templateUrl: '../ag-admin/views/dashboardSummary.tpl.html',
        link: function(scope) {
            scope.rangeVal = [{
                "key": "lastDays",
                "value": "Last 7 Days"
            }, {
                "key": "lastWeeks",
                "value": "Last 4 Weeks"
            }, {
                "key": "lastMonths",
                "value": "Last 3 Months"
            }, {
                "key": "lastYears",
                "value": "Last 3 Years"
            }];
            if (scope.rangeText === undefined) {
                scope.rangeText = "Last 7 Days";
            }
            scope.selectedRangeItem = function(rangeVal, rangeText) {};
            scope.adminstats = [];
            scope.adminactivities = [];
            scope.adminoverview = [];
            var timeZone;
            $http.get(admin_api_url + 'api/v1/stats')
                .success(function(response) {
                    scope.adminoverview = response;
                });
        }
    };
}]);
//Events Schedule edit
ngapp.directive('eventBasket', function() {
    return {
        restrict: 'E',
        scope: {
            entry: "&"
        },
        templateUrl: 'views/events.tpl.html',
        controller: function($scope) {
            $scope.index = function() {
                $scope.event_schedules = [];
                // for create view - no eventschedule 
                if ($scope.entry()
                    .values.event_schedule === null || $scope.entry()
                    .values.event_schedule === undefined) {
                    $scope.event_schedules = [];
                } else {
                    if ($scope.entry()
                        .values.event_schedule.length > 0) {
                        $scope.event_schedules = $scope.entry()
                            .values.event_schedule;
                    }
                }
                $scope.entry()
                    .values.eventschedule = $scope.event_schedules;
                //angular.copy($scope.entry()
                // .values.event_schedule, $scope.event_schedules);
                angular.forEach($scope.event_schedules, function(value) {
                    if (value.start_date) {
                        value.start_date = new Date(value.start_date);
                    }
                    if (value.end_date) {
                        value.end_date = new Date(value.end_date);
                    }
                })
                $scope.date = new Date();
            }
            // date and time picker
            $scope.openCalendar = function(e, picker, which) {
                $scope.event_schedules[which][picker] = true;
            };
            $scope.addEventSchedule = function() {
                $scope.event_schedules.push({
                    start_date: new Date(),
                    end_date: new Date()
                });
            };
            $scope.deleteEventSchedule = function(schedule_id, index) {
                $scope.event_schedules.splice(index, 1);
            };
            $scope.index();
        }
    };
});
ngapp.directive('reportsFilter', function() {
    return {
        restrict: 'E',
        scope: {
            type: "@",
            callback: '&'
        },
        templateUrl: 'views/resports-filter.html',
        controller: function($scope, $http) {
            $scope.allEvents = [];
            $scope.eventSchedules = [];
            $scope.filterForm = {};
            $scope.calendar = {};
            /**
             * @ngdoc method
             * @name reportsFilter.getAllEvents
             * @methodOf module.reportsFilter
             * @description
             * This method will get sales report details when controller initiated
             */
            $scope.getAllEvents = function() {
                $http({
                        method: 'GET',
                        url: '/api/v1/events?limit=100',
                    })
                    .success(function(response) {
                        if (angular.isDefined(response.data) && response.data !== '') {
                            $scope.allEvents = response.data;
                        }
                    });
            };
            $scope.getAllEvents();
            $scope.dateDidabled = true;
            /**
             * @ngdoc method
             * @name reportsFilter.eventChange
             * @methodOf module.reportsFilter
             * @description
             * This method load event schedule date for particular event and it will  be enabled after event selected
             */
            $scope.eventChange = function(value) {
                $scope.dateDidabled = false;
                if (value !== '' && value !== null) {
                    $scope.eventSchedules = value.event_schedule;
                }
            };
            /**
             * @ngdoc method
             * @name reportsFilter.getAllPriceTypes
             * @methodOf module.reportsFilter
             * @description
             * This method will get sales report details when controller initiated
             */
            $scope.getAllPriceTypes = function() {
                $http({
                        method: 'GET',
                        url: '/api/v1/price_types',
                    })
                    .success(function(response) {
                        if (angular.isDefined(response.data) && response.data !== '') {
                            $scope.priceTypes = response.data;
                        }
                    });
            };
            $scope.getAllPriceTypes();
            $scope.datepicker = {
                date: new Date('2015-03-01T00:00:00Z'),
                datepickerOptions: {
                    showWeeks: false,
                    startingDay: 1,
                    dateDisabled: function(data) {
                        return (data.mode === 'day' && (new Date()
                            .toDateString() == data.date.toDateString()));
                    }
                }
            }
            $scope.openCalendar = function(e, picker) {
                $scope.calendar[picker] = true;
            };
            $scope.appliedFilter = false;
            $scope.status = {};
            $scope.status.isOpen = false;
            $scope.applyFilter = function() {
                $scope.appliedFilter = true;
                $scope.status.isOpen = false;
                var params = {};
                if (angular.isDefined($scope.filterForm.selected_event) && $scope.filterForm.selected_event !== null && $scope.filterForm.selected_event.id !== null) {
                    params.event_id = $scope.filterForm.selected_event.id;
                } else {
                    delete params.event_id;
                }
                if (angular.isDefined($scope.filterForm.booked_start_date) && $scope.filterForm.booked_start_date !== null) {
                    params.start_date = $scope.filterForm.booked_start_date;
                }
                if (angular.isDefined($scope.filterForm.selected_schedule) && $scope.filterForm.selected_schedule !== null && $scope.filterForm.selected_schedule.id !== null) {
                    params.event_schedule_id = $scope.filterForm.selected_schedule.id;
                } else {
                    delete params.event_schedule_id;
                }
                if (angular.isDefined($scope.filterForm.booked_end_date) && $scope.filterForm.booked_end_date !== '') {
                    params.end_date = $scope.filterForm.booked_end_date;
                }
                if (angular.isDefined($scope.filterForm.selected_sales_channel) && $scope.filterForm.selected_sales_channel !== '') {
                    params.sales_channel = $scope.filterForm.selected_sales_channel;
                }
                if (angular.isDefined($scope.filterForm.selected_pricetype) && $scope.filterForm.selected_pricetype !== null && $scope.filterForm.selected_pricetype.id !== null) {
                    params.price_type_id = $scope.filterForm.selected_pricetype.id;
                }
                params.filter = "chart";
                $scope.callback({
                    filter: params
                });
            }
        }
    };
});
//time ago filter using jquery timeago plugin
ngapp.filter("timeago", function() {
    //passed extra argument to get time zome
    return function(date, timeZone) {
        jQuery.timeago.settings.strings = {
            prefixAgo: null,
            prefixFromNow: null,
            suffixAgo: "ago",
            suffixFromNow: "from now",
            seconds: "less than a minute",
            minute: "a minute",
            minutes: "%d minutes",
            hour: "an hour",
            hours: "%d hours",
            day: "a day",
            days: "%d days",
            month: "a month",
            months: "%d months",
            year: "a year",
            years: "%d years",
            wordSeparator: " ",
            numbers: []
        };
        return jQuery.timeago(date + timeZone);
    };
});
ngapp.config(['RestangularProvider', function(RestangularProvider) {
    RestangularProvider.addFullRequestInterceptor(function(element, operation, what, url, headers, params) {
        if (operation === 'getList') {
            // custom pagination params
            if (params._page) {
                params.page = params._page;
                params.limit = params._perPage;
                delete params._sortDir;
                delete params._sortField;
                delete params._page;
                delete params._perPage;
            }
            if (params._filters) {
                for (var filter in params._filters) {
                    if (filter == 'news_category_id') {
                        params['news_category_id[]'] = params._filters[filter];
                    } else {
                        params[filter] = params._filters[filter];
                    }
                }
                delete params._filters;
            }
        }
        if ($cookies.get("token")) {
            var sep = url.indexOf('?') === -1 ? '?' : '&';
            url = url + sep + 'token=' + $cookies.get("token");
        }
        return {
            params: params,
            url: url
        };
    });
    RestangularProvider.addResponseInterceptor(function(data, operation, what, url, response) {
        if (operation === "getList") {
            var headers = response.headers();
            if (typeof response.data._metadata !== 'undefined' && response.data._metadata.total !== null) {
                response.totalCount = response.data._metadata.total;
            }
        }
        return data;
    });
    //To cutomize single view results, we added setResponseExtractor.
    //Our API Edit view results single array with following data format data[{}], Its not working with ng-admin format
    //so we returned data like data[0];
    RestangularProvider.setResponseExtractor(function(data, operation, what, url) {
        var extractedData;
        // .. to look for getList operations 
        if (what == 'news') {
            if (angular.isDefined(data.data.news_category)) {
                delete data.data.news_category;
            }
            if (angular.isDefined(data.data.attachments)) {
                delete data.data.attachments;
            }
            extractedData = data.data;
        } else {
            extractedData = data.data;
        }
        return extractedData;
    });
}]);
ngapp.config(['NgAdminConfigurationProvider', 'user_roles', function(NgAdminConfigurationProvider, userRoles) {
    var nga = NgAdminConfigurationProvider;
    //trunctate function to shorten text length.
    function truncate(value) {
        if (!value) {
            return '';
        }
        return value.length > 50 ? value.substr(0, 50) + '...' : value;
    }
    var admin = nga.application('Tixmall Admin')
        .baseApiUrl(admin_api_url + 'api/v1/'); // main API endpoint;
    if (typeof auth !== 'undefined') {
        var current_user_role = '';
        if (auth.role_id === userRoles.organizer) {
            current_user_role = "Event Organizer";
        }
        var role = nga.entity('roles');
        admin.addEntity(role);
        var payment_gateway = nga.entity('payment_gateways');
        admin.addEntity(payment_gateway);
        var setting_category = nga.entity('setting_categories');
        admin.addEntity(setting_category);
        var settings = nga.entity('settings');
        admin.addEntity(settings);
        var country = nga.entity('countries');
        admin.addEntity(country);
        var state = nga.entity('states');
        admin.addEntity(state);
        var city = nga.entity('cities');
        admin.addEntity(city);
        var page = nga.entity('pages');
        admin.addEntity(page);
        var email_template = nga.entity('email_templates');
        admin.addEntity(email_template);
        var provider = nga.entity('providers');
        admin.addEntity(provider);
        var language = nga.entity('languages');
        admin.addEntity(language);
        var contact = nga.entity('contacts');
        admin.addEntity(contact);
        var user = nga.entity('users');
        admin.addEntity(user);
        var myaccount = nga.entity('users');
        admin.addEntity(myaccount);
        var category = nga.entity('categories');
        admin.addEntity(category);
        var news = nga.entity('news');
        admin.addEntity(news);
        var news_category = nga.entity('news_categories');
        admin.addEntity(news_category);
        var newsletter = nga.entity('newsletters');
        admin.addEntity(newsletter);
        var series = nga.entity('series');
        admin.addEntity(series);
        var venue = nga.entity('venues');
        admin.addEntity(venue);
        var venue_zone = nga.entity('venue_zones')
        admin.addEntity(venue_zone);
        var venue_zone_preview = nga.entity('venue_zone_previews');
        admin.addEntity(venue_zone_preview);
        var event = nga.entity('events');
        admin.addEntity(event);
        var event_schedule = nga.entity('event_schedules');
        admin.addEntity(event_schedule);
        var event_zone = nga.entity('event_zones');
        admin.addEntity(event_zone);
        var gift_voucher = nga.entity('gift_vouchers');
        admin.addEntity(gift_voucher);
        var order = nga.entity('orders');
        admin.addEntity(order);
        var coupon = nga.entity('coupons');
        admin.addEntity(coupon);
        var venue_zone_section = nga.entity('venue_zone_sections');
        admin.addEntity(venue_zone_section);
        var venue_zone_section_row = nga.entity('venue_zone_section_rows');
        admin.addEntity(venue_zone_section_row);
        var venue_zone_section_seat = nga.entity('venue_zone_section_seats');
        admin.addEntity(venue_zone_section_seat);
        var list = nga.entity('lists');
        admin.addEntity(list);
        var visitor = nga.entity('guests');
        admin.addEntity(visitor);
        /*var send_invitation = nga.entity('send_invitations');
        admin.addEntity(send_invitation);*/
        //Menu configuration
        if (auth.role_id === userRoles.admin) {
            admin.menu(nga.menu()
                .addChild(nga.menu(user)
                    .title('Users')
                    .icon('<span class="glyphicon glyphicon-user"></span>'))
                .addChild(nga.menu()
                    .title('Payments')
                    .icon('<span class="glyphicon glyphicon-usd"></span>')
                    .addChild(nga.menu(payment_gateway)
                        .title('Payment Gateways')
                        .icon('<span class="fa fa-table"></span>')))
                .addChild(nga.menu()
                    .title('Settings')
                    .icon('<span class="glyphicon glyphicon-cog"></span>')
                    .addChild(nga.menu(setting_category)
                        .title('Site Settings')
                        .icon('<span class="fa fa-cog"></span>')))
                .addChild(nga.menu()
                    .title('Master')
                    .icon('<span class="glyphicon glyphicon-dashboard"></span>')
                    .addChild(nga.menu(city)
                        .title('Cities')
                        .icon('<span class="glyphicon glyphicon-record"></span>'))
                    .addChild(nga.menu(state)
                        .title('States')
                        .icon('<span class="glyphicon glyphicon-record"></span>'))
                    .addChild(nga.menu(country)
                        .title('Countries')
                        .icon('<span class="glyphicon glyphicon-record"></span>'))
                    .addChild(nga.menu(page)
                        .title('Pages')
                        .icon('<span class="glyphicon glyphicon-record"></span>'))
                    .addChild(nga.menu(language)
                        .title('Languages')
                        .icon('<span class="glyphicon glyphicon-record"></span>'))
                    .addChild(nga.menu(contact)
                        .title('Contacts')
                        .icon('<span class="glyphicon glyphicon-record"></span>'))
                    .addChild(nga.menu(provider)
                        .title('Providers')
                        .icon('<span class="glyphicon glyphicon-record"></span>'))
                    .addChild(nga.menu(email_template)
                        .title('Email Templates')
                        .icon('<span class="glyphicon glyphicon-record"></span>'))
                    .addChild(nga.menu(category)
                        .title('Categories')
                        .icon('<span class="glyphicon glyphicon glyphicon-leaf"></span>'))
                    .addChild(nga.menu(series)
                        .title('Event Types')
                        .icon('<span class="fa fa-leaf"></span>')))
                .addChild(nga.menu()
                    .title('News')
                    .icon('<span class="fa fa-newspaper-o"></span>')
                    .addChild(nga.menu(news)
                        .title('News')
                        .icon('<span class="glyphicon glyphicon-list-alt"></span>'))
                    .addChild(nga.menu(news_category)
                        .title('News Category')
                        .icon('<span class="glyphicon glyphicon-list-alt"></span>'))
                    .addChild(nga.menu(newsletter)
                        .title('Newsletters')
                        .icon('<span class="glyphicon glyphicon-list-alt"></span>'))));
        }
        admin.menu()
            .addChild(nga.menu()
                .title('Venues')
                .icon('<span class="fa fa-map-marker"></span>')
                .addChild(nga.menu(venue)
                    .title('Venues')
                    .icon('<span class="glyphicon glyphicon-list-alt"></span>'))
                .addChild(nga.menu(venue_zone)
                    .title('Venue Zones')
                    .icon('<span class="glyphicon glyphicon-list-alt"></span>'))
                .addChild(nga.menu(venue_zone_preview)
                    .title('Venue Zone Previews')
                    .icon('<span class="fa fa-eye"></span>')))
            .addChild(nga.menu()
                .title('Events')
                .icon('<span class="fa fa-calendar"></span>')
                .addChild(nga.menu(event)
                    .title('Events')
                    .icon('<span class="glyphicon glyphicon-list-alt"></span>'))
                .addChild(nga.menu(event_schedule)
                    .title('Events Schedules')
                    .icon('<span class="glyphicon glyphicon-list-alt"></span>'))
                .addChild(nga.menu(event_zone)
                    .title('Event Zone')
                    .icon('<span class="glyphicon glyphicon-list-alt"></span>')));
        if (auth.role_id === userRoles.admin) {
            admin.menu()
                .addChild(nga.menu()
                    .title('Gift Vouchers')
                    .icon('<span class="fa fa-gift"></span>')
                    .addChild(nga.menu(gift_voucher)
                        .title('Gift Vouchers')
                        .icon('<span class="glyphicon glyphicon-list-alt"></span>')));
        }
        admin.menu()
            .addChild(nga.menu()
                .title('Reports')
                .icon('<span class="fa fa-pie-chart"></span>')
                .addChild(nga.menu()
                    .title('Sales Reports')
                    .icon('<span class="fa fa-shopping-cart"></span>')
                    .link('/sales-reports'))
                .addChild(nga.menu()
                    .title('Capacity Reports')
                    .icon('<span class="fa fa-building-o"></span>')
                    .link('/capacity-reports'))
                .addChild(nga.menu()
                    .title('Demographic Reports')
                    .icon('<span class="fa fa-area-chart"></span>')
                    .link('/demographics-reports'))
                .addChild(nga.menu()
                    .title('Financial Reports')
                    .icon('<span class="fa fa-money"></span>')
                    .link('/financial-reports'))
                .addChild(nga.menu()
                    .title('Participant Reports')
                    .icon('<span class="fa fa-users"></span>')
                    .link('/participants-reports'))
                .addChild(nga.menu()
                    .title('Visitors Reports')
                    .icon('<span class="fa fa-user"></span>')
                    .link('/visitors-reports')));
        if (auth.role_id === userRoles.admin) {
            admin.menu()
                .addChild(nga.menu()
                    .title('Invitations')
                    .icon('<span class="fa fa-envelope-o"></span>')
                    .addChild(nga.menu(list)
                        .title('Lists')
                        .icon('<span class="fa fa-list"></span>'))
                    //   .link('/invitation-lists'))
                    .addChild(nga.menu(visitor)
                        .title('Visitors')
                        .icon('<span class="fa fa-user"></span>'))
                    // .link('/invitation-visitors'))
                    .addChild(nga.menu()
                        .title('Send')
                        .icon('<span class="fa fa-paper-plane"></span>')
                        .link('/invitation-send')));
        }
        admin.menu()
            .addChild(nga.menu()
                .title('Orders')
                .icon('<span class="fa fa-shopping-cart"></span>')
                .addChild(nga.menu(order)
                    .title('Orders')
                    .icon('<span class="glyphicon glyphicon-list-alt"></span>')))
            .addChild(nga.menu()
                .title('My account')
                .icon('<span class="glyphicon glyphicon-scale"></span>')
                .link('/users/edit/' + auth.id))
            .addChild(nga.menu()
                .title('Logout')
                .icon('<span class="glyphicon glyphicon-log-out"></span>')
                .link('/users/logout'));
        nga.menu()
            .autoClose(true);
        payment_gateway.listView()
            .title("Payment Gateways")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .batchActions([])
            .fields([
                nga.field('id')
                                .label('Gateway ID'),
                nga.field('name')
                                .label('Gateway Name'),
                nga.field('is_test_mode')
                                .label('Mode'),
                nga.field('gateway_fees')
                                .label('Gateway Fees'),
                nga.field('is_enable_for_wallet')
                                .label('Wallet Enabled')
                ]);
        var setting_category_list_tpl = '<ma-edit-button entry="entry" entity="entity" size="sm" label="Configure" ></ma-edit-button>';
        var setting_category_action_tpl = '<ma-filter-button filters="filters()" enabled-filters="enabledFilters" enable-filter="enableFilter()"></ma-filter-button>';
        setting_category.listView()
            .title('Site Settings')
            .fields([
                nga.field('id')
                .label('ID'),
                nga.field('name', 'wysiwyg')
                .label('Name'),
                nga.field('description')
                .label('Description'),
            ])
            .batchActions([])
            .perPage(limit_per_page)
            .actions(setting_category_action_tpl)
            .listActions(setting_category_list_tpl)
        settings_category_edit_template = '<ma-list-button entry="entry" entity="entity" size="sm"></ma-list-button>';
        setting_category.editionView()
            .title('Edit Settings')
            .fields([
                nga.field('name')
                .editable(false)
                .label('Name'),
                nga.field('description')
                .editable(false)
                .label('Description'),
                nga.field('Related Settings', 'referenced_list') // display list of related settings
                .targetEntity(nga.entity('settings'))
                .targetReferenceField('setting_category_id')
                .targetFields([
                    nga.field('label')
                    .label('Name'),
                    nga.field('value')
                    .label('Value')
                ])
                .listActions(['edit'])
            ])
            .actions(settings_category_edit_template);
        var setting_edit_template = '<ma-back-button></ma-back-button>';
        settings.editionView()
            .title('Edit - {{entry.values.label}}')
            .fields([
                nga.field('label')
                .editable(false)
                .label('Name'),
                nga.field('description')
                .editable(false)
                .label('Description'),
                nga.field('value', 'text')
                .label('Value')
                .template('<input-type entry="entry" entity="entity"></input-type>')
                .validation({
                    validator: function(value, entry) {
                        //apply validate conditions here
                    }
                })
            ])
            .actions(setting_edit_template);
        /* country */
        country.listView()
            .title("Countries")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([nga.field('name')
                        .label('Name'),
                nga.field('fips_code')
                        .label('Fips code'),
                nga.field('iso_alpha2')
                        .label('Iso2'),
                nga.field('iso_alpha3')
                        .label('Iso3'),
                ])
            .listActions(['show', 'edit', 'delete']);
        country.creationView()
            .fields([nga.field('name')
                        .validation({
                    required: true
                })
                        .label('Name'),
                nga.field('fips_code')
                        .validation({
                    required: true
                })
                        .label('Fips code'),
                nga.field('iso_alpha2')
                        .validation({
                    required: true
                })
                        .label('Iso2'),
                nga.field('iso_alpha3')
                        .validation({
                    required: true
                })
                        .label('Iso3'),
                nga.field('iso_numeric', 'number')
                        .validation({
                    required: true
                })
                        .label('Ison'),
                nga.field('capital')
                        .label('Capital'),
                nga.field('population', 'number')
                        .label('Population'),
                nga.field('continent')
                        .label('Continent'),
                nga.field('tld')
                        .label('Tld'),
                nga.field('currency')
                        .label('Currency'),
                nga.field('currencyname')
                        .label('Currency Name'),
                nga.field('phone', 'number')
                        .label('Phone'),
                nga.field('postalcodeformat')
                        .label('Postal Code Format'),
                nga.field('postalcoderegex')
                        .label('Postal Code Regex'),
                nga.field('languages')
                        .label('Languages'),
                nga.field('geonameid', 'number')
                        .label('Geo Name Id'),
                nga.field('neighbours')
                        .label('Neighbours'),
                nga.field('equivalentfipscode')
                        .label('Equivalent Fips Code')
                ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
                }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
                }]);
        country.showView()
            .fields([nga.field('iso_alpha2')
                            .label('Iso Alpha2'),
            nga.field('iso_alpha3')
                            .label('Iso Alpha3'),
            nga.field('iso_numeric')
                            .label('Iso Numeric'),
            nga.field('fips_code')
                            .label('Fips Code'),
            nga.field('name')
                            .label('Name'),
            nga.field('capital')
                            .label('Capital'),
            nga.field('population')
                            .label('Population'),
            nga.field('continent')
                            .label('Continent'),
            nga.field('tld')
                            .label('Tld'),
            nga.field('currency')
                            .label('Currency'),
            nga.field('currencyname')
                            .label('Currencyname'),
            nga.field('phone')
                            .label('Phone'),
            nga.field('postalcodeformat')
                            .label('Postalcodeformat'),
            nga.field('postalcoderegex')
                            .label('Postalcoderegex'),
            nga.field('languages')
                            .label('Languages'),
            nga.field('geonameid')
                            .label('Geonameid'),
            nga.field('neighbours')
                            .label('Neighbours'),
            nga.field('equivalentfipscode')
                            .label('Equivalentfipscode'),
            nga.field('created_at')
                            .label('Created At'),
            nga.field('updated_at')
                            .label('Updated At'),
            ]);
        country.editionView()
            .fields(country.creationView()
                .fields());
        /* state */
        state.listView()
            .title("States")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([nga.field('created_at')
                        .label('Added On'),
                nga.field('country_id', 'reference')
                        .targetEntity(country)
                        .targetField(nga.field('name'))
                        .label('Country')
                        .isDetailLink(false)
                        .singleApiCall(function(countryIds) {
                    return {
                        'country_id[]': countryIds
                    };
                }),
                nga.field('name')
                        .label('Name'),
                nga.field('state_code')
                        .label('State Code'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ])
            .listActions(['show', 'edit', 'delete']);
        state.creationView()
            .fields([nga.field('country_id', 'reference')
                        .targetEntity(country)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('Country')
                        .remoteComplete(true),
                nga.field('name')
                        .label('Name'),
                nga.field('state_code')
                        .label('State Code'),
                nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
                }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
                }]);
        state.showView()
            .fields([nga.field('created_at')
                        .label('Added On'),
                nga.field('country_id', 'reference')
                        .targetEntity(country)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('Country Name'),
                nga.field('name')
                        .label('Name'),
                nga.field('state_code')
                        .label('State Code'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ]);
        state.editionView()
            .fields(state.creationView()
                .fields());
        /* city */
        city.listView()
            .title("Cities")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([
                nga.field('country_id', 'reference')
                        .targetEntity(country)
                        .targetField(nga.field('name'))
                        .label('Country name')
                        .isDetailLink(false)
                        .singleApiCall(function(countryIds) {
                    return {
                        'country_id[]': countryIds
                    };
                }),
                nga.field('state_id', 'reference')
                        .targetEntity(state)
                        .targetField(nga.field('name'))
                        .label('State')
                        .isDetailLink(false)
                        .singleApiCall(function(stateIds) {
                    return {
                        'state_id[]': stateIds
                    };
                }),
                nga.field('name')
                        .label('Name'),
                nga.field('city_code')
                        .label('City Code'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ])
            .filters([nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                        ])
            .listActions(['show', 'edit', 'delete']);
        city.creationView()
            .fields([nga.field('country_id', 'reference')
                        .targetEntity(country)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('Country')
                        .remoteComplete(true),
                nga.field('state_id', 'reference')
                        .targetEntity(state)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('State')
                        .remoteComplete(true),
                nga.field('name')
                        .validation({
                    required: true
                })
                        .label('Name'),
                nga.field('city_code')
                        .validation({
                    required: true
                })
                        .label('City Code'),
                nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }]),
                ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
                }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
                }]);
        city.showView()
            .fields([
                 nga.field('country_id', 'reference')
                        .targetEntity(country)
                        .targetField(nga.field('name'))
                        .label('Country name')
                        .isDetailLink(false),
                nga.field('state_id', 'reference')
                        .targetEntity(state)
                        .targetField(nga.field('name'))
                        .label('State')
                        .isDetailLink(false),
                nga.field('name')
                        .label('Name'),
                nga.field('city_code')
                        .label('City Code'),
                nga.field('is_active', 'boolean')
                        .label('Is Active')
                ]);
        city.editionView()
            .fields(city.creationView()
                .fields());
        /* page */
        page.listView()
            .title("Pages")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([
                nga.field('title')
                        .label('Title'),
                nga.field('content', 'wysiwyg')
                        .stripTags(true)
                        .label('Content'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ])
            .listActions(['show', 'edit', 'delete'])
            .filters([nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                        ]);
        page.creationView()
            .fields([nga.field('title')
                        .validation({
                    required: true
                })
                 .label('Title'),
                nga.field('content', 'wysiwyg')
                        .validation({
                    required: true
                })
                .map(truncate)
                .label('Content'),
                nga.field('meta_keywords')
                        .label('Meta Keywords'),
                nga.field('meta_description', 'text')
                        .label('Meta Description'),
                nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
                }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
                }]);
        page.showView()
            .fields([nga.field('created_at')
                        .label('Added On'),
                nga.field('title')
                        .label('Title'),
                nga.field('content')
                        .label('Content'),
                nga.field('meta_keywords')
                        .label('Meta Keywords'),
                nga.field('meta_description')
                        .label('Meta Description'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ]);
        page.editionView()
            .fields(page.creationView()
                .fields());
        /* email templates */
        email_template.listView()
            .title("Email Templates")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([nga.field('display_label')
                        .label('Name'),
                nga.field('from')
                        .label('From Name'),
                nga.field('subject')
                        .label('Subject'),
                nga.field('email_content')
                        .label('Content'),
                ])
            .listActions(['edit'])
            .batchActions([])
            .filters([nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                        ]);
        email_template.editionView()
            .fields([nga.field('display_label')
                        .editable(false)
                        .label('Name'),
                nga.field('from')
                        .validation({
                    required: true
                })
                        .label('From Name'),
                nga.field('subject')
                        .validation({
                    required: true
                })
                        .label('Subject'),
                nga.field('email_content', 'text')
                        .validation({
                    required: true
                })
                        .label('Content'),
                nga.field('email_variables')
                        .editable(false)
                        .label('Constant for Subject and Content'),
                ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
                }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
                }]);
        /* providers */
        provider.listView()
            .title("Providers")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([nga.field('name')
                        .label('Name'),
                nga.field('api_key')
                        .label('Client ID'),
                nga.field('secret_key')
                        .label('Secret Key'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ])
            .batchActions([])
            .listActions(['edit'])
            .filters([nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                        ]);
        provider.editionView()
            .fields([nga.field('name')
                        .validation({
                    required: true
                })
                        .label('Name'),
                nga.field('api_key')
                        .validation({
                    required: true
                })
                        .label('Client ID'),
                nga.field('secret_key')
                        .validation({
                    required: true
                })
                        .label('Secret Key'),
                nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                ]);
        /* language */
        language.listView()
            .title("Languages")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([
                nga.field('name')
                        .label('Name'),
                nga.field('iso2')
                        .label('Iso2'),
                nga.field('iso3')
                        .label('Iso3'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ])
            .listActions(['show', 'edit', 'delete'])
            .filters([nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                        ]);
        language.creationView()
            .fields([nga.field('name')
                        .validation({
                    required: true
                })
                        .label('Name'),
                nga.field('iso2')
                        .validation({
                    required: true
                })
                        .label('Iso2'),
                nga.field('iso3')
                        .validation({
                    required: true
                })
                        .label('Iso3'),
                nga.field('is_active', 'choice')
                .label('Is Active')
                .choices([{
                    label: 'Yes',
                    value: true
                }, {
                    label: 'No',
                    value: false
                }]),
                ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
                }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
                }]);
        language.showView()
            .fields([nga.field('created_at')
                        .label('Added On'),
                nga.field('name')
                        .label('Name'),
                nga.field('iso2')
                        .label('Iso2'),
                nga.field('iso3')
                        .label('Iso3'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ]);
        language.editionView()
            .fields(language.creationView()
                .fields());
        /* contact */
        contact.listView()
            .title("Contacts")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([nga.field('created_at')
                        .label('Added On'),
                nga.field('ip_id')
                        .label('Ip Id'),
                nga.field('first_name')
                        .label('First Name'),
                nga.field('last_name')
                        .label('Last Name'),
                nga.field('email')
                        .label('Email'),
                nga.field('phone')
                        .label('Phone'),
                nga.field('subject')
                        .label('Subject'),
                nga.field('message')
                        .label('Message'),
                ])
            .listActions(['show', 'delete']);
        contact.showView()
            .fields([nga.field('created_at')
                        .label('Added On'),
                nga.field('first_name')
                        .label('First Name'),
                nga.field('last_name')
                        .label('Last Name'),
                nga.field('email')
                        .label('Email'),
                nga.field('phone')
                        .label('Phone'),
                nga.field('subject')
                        .label('Subject'),
                nga.field('message')
                        .label('Message'),
                ]);
        /** users **/
        user.listView()
            .title("User")
            .infinitePagination(false)
            .perPage(limit_per_page)
            .fields([nga.field('created_at')
                        .label('Added On'),
                nga.field('role_id', 'reference')
                        .targetEntity(role)
                        .targetField(nga.field('name'))
                        .label('Role')
                        .isDetailLink(false)
                        .singleApiCall(function(roleIds) {
                    return {
                        'role_id[]': roleIds
                    };
                }),
                nga.field('username')
                        .label('Username'),
                nga.field('email')
                        .label('Email'),
                nga.field('first_name')
                        .label('Name'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
                ])
            .listActions(['show', 'edit', 'delete'])
            .filters([nga.field('role_id', 'reference')
                        .targetEntity(role)
                        .targetField(nga.field('name'))
                        .label('Role')
                        .remoteComplete(true),
                        nga.field('is_active', 'choice')
                        .label('Is Active')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                        ]);
        user.creationView()
            .fields([nga.field('role_id', 'reference')
                        .targetEntity(role)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('Role')
                        .validation({
                    required: true
                })
                        .remoteComplete(true),
                nga.field('username')
                        .validation({
                    required: true
                })
                        .label('Username'),
                nga.field('email', 'email')
                        .validation({
                    required: true
                })
                        .label('Email'),
                nga.field('password', 'password')
                        .validation({
                    required: true
                })
                        .label('Password'),
                nga.field('first_name')
                    .label('First Name'),
                nga.field('last_name')
                    .label('Last Name'),
                nga.field('gender_id', 'choice')
                        .choices([{
                    label: 'Male',
                    value: '0'
                                }, {
                    label: 'Female',
                    value: '1'
                                }])
                        .label('Gender'),
                nga.field('dob', 'date')
                    .label('Dob'),
                nga.field('about_me', 'wysiwyg')
                    .label('About Me'),
                nga.field('address')
                        .template('<google-places entry="entry" entity="entity"></google-places>')
                        .validation({
                    required: true
                })
                        .label('Address'),
                nga.field('address1')
                    .label('Address1'),
                nga.field('city_name')
                    .label('City'),
                nga.field('state_name')
                    .label('State'),
                nga.field('country_iso2')
                        .label('Country ISO'),
                nga.field('zip_code')
                    .label('Zip Code'),
               /* nga.field('latitude')
                    .label('Latitude'),
                nga.field('longitude')
                    .label('Longitude'),*/
                nga.field('phone')
                    .label('Phone'),
                nga.field('mobile')
                    .label('Mobile'),
                nga.field('is_active', 'choice')
                    .label('Is Active')
                    .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }]),
                nga.field('is_agree_terms_conditions', 'choice')
                        .label('Is Agree Terms Conditions')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }]),
                nga.field('is_subscribed', 'choice')
                        .label('Is Subscribed')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
                }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log('Some values are invalid, see details in the form', {
                    addnCls: 'humane-flatty-error'
                });
                return false;
                }]);
        user.showView()
            .fields([nga.field('created_at')
                        .label('Added On'),
                nga.field('role_id', 'reference')
                        .targetEntity(role)
                        .targetField(nga.field('name'))
                        .label('Role')
                        .remoteComplete(true),
                nga.field('username')
                        .label('Username'),
                nga.field('email')
                        .label('Email'),
                nga.field('first_name')
                    .label('First Name'),
                nga.field('last_name')
                    .label('Last Name'),
                nga.field('gender_id', 'choice')
                        .choices([{
                    label: 'Male',
                    value: '0'
                                }, {
                    label: 'Female',
                    value: '1'
                                }])
                        .label('Gender'),
                nga.field('dob')
                    .label('Dob'),
                nga.field('about_me', 'wysiwyg')
                    .label('About Me'),
                nga.field('address')
                    .label('Address'),
                nga.field('address1')
                    .label('Address1'),
                nga.field('city_id', 'reference')
                        .targetEntity(city)
                        .targetField(nga.field('name'))
                        .label('City')
                        .perPage('all')
                        .remoteComplete(true)
                        .isDetailLink(false),
                nga.field('state_id', 'reference')
                        .targetEntity(state)
                        .targetField(nga.field('name'))
                        .label('State')
                        .perPage('all')
                        .remoteComplete(true)
                        .isDetailLink(false),
                nga.field('country_id', 'reference')
                        .targetEntity(country)
                        .targetField(nga.field('name'))
                        .label('Country')
                        .remoteComplete(true)
                        .isDetailLink(false),
                nga.field('zip_code')
                    .label('Zip Code'),
                nga.field('phone')
                    .label('Phone'),
                nga.field('mobile')
                    .label('Mobile'),
                nga.field('latitude')
                    .label('Latitude'),
                nga.field('longitude')
                    .label('Longitude'),
                nga.field('is_subscribed', 'boolean')
                        .label('Is Subscribed'),
                nga.field('is_active', 'boolean')
                    .label('Is Active')
                ]);
        user.editionView()
            .fields([nga.field('role_id', 'reference')
                        .targetEntity(role)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('Role')
                        .validation({
                    required: true
                }),
                nga.field('username')
                        .validation({
                    required: true
                })
                        .label('Username'),
                nga.field('email', 'email')
                        .validation({
                    required: true
                })
                        .label('Email'),
                  nga.field('first_name')
                    .label('First Name'),
                nga.field('last_name')
                    .label('Last Name'),
               nga.field('gender_id', 'choice')
                        .choices([{
                    label: 'Male',
                    value: '0'
                                }, {
                    label: 'Female',
                    value: '1'
                                }])
                        .label('Gender'),
                nga.field('dob', 'date')
                    .label('Dob'),
                nga.field('about_me', 'wysiwyg')
                    .label('About Me'),
               nga.field('location')
                        .template('<google-places entry="entry" entity="entity"></google-places>')
                        .validation({
                    required: true
                })
                        .label('Location'),
                nga.field('address')
                    .label('Address'),
                nga.field('address1')
                    .label('Address1'),
                nga.field('city.name')
                    .label('City'),
                nga.field('state.name')
                    .label('State'),
                nga.field('country.iso_alpha2')
                        .label('Country ISO'),
                nga.field('zip_code')
                    .label('Zip Code'),
                nga.field('latitude')
                    .label('Latitude'),
                nga.field('longitude')
                    .label('Longitude'),
                nga.field('phone')
                    .label('Phone'),
                nga.field('mobile')
                    .label('Mobile'),
                nga.field('is_active', 'choice')
                    .label('Is Active')
                    .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }]),
                 nga.field('is_agree_terms_conditions', 'choice')
                        .label('Is Agree Terms Conditions')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }]),
                nga.field('is_subscribed', 'choice')
                        .label('Is Subscribed')
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }])
                ])
            .actions(['batch'])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                if (auth.role_id !== userRoles.organizer) {
                    $state.go($state.get('list'), {
                        entity: entity.name()
                    });
                } else {
                    $state.reload();
                }
                return false;
                }]);
        /* category */
        category.listView()
            .title("Categories")
            .fields([
            nga.field('id')
                .label('Id'),
            nga.field('name')
                .label('Name'),
            nga.field('is_active', 'boolean')
                .label('Is Active'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([
            nga.field('q')
            .label('Search', 'template')
            .pinned(true)
            .template(''), nga.field('filter', 'choice')
            .label('Status')
            .attributes({
                    placeholder: 'Status?'
                })
            .choices([{
                    label: 'Active',
                    value: true
            }, {
                    label: 'Inactive',
                    value: false
            }])]);
        category.creationView()
            .fields([
                nga.field('name')
                    .validation({
                    required: true
                })
                    .label('Name'),
                 nga.field('is_active', 'choice')
                    .label('Active?')
                    .attributes({
                    placeholder: 'Active?'
                })
                    .validation({
                    required: true
                })
                    .choices([{
                    label: 'Yes',
                    value: true
                    }, {
                    label: 'No',
                    value: false
                    }]),
             ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
                }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
                }]);
        category.showView()
            .fields([
                nga.field('id')
                .label('Id'),
                nga.field('created_at')
                       .label('Added On'),
                nga.field('name')
                        .label('Name'),
                nga.field('is_active', 'boolean')
                        .label('Is Active'),
        ]);
        category.editionView()
            .fields(category.creationView()
                .fields());
        /* news */
        news.listView()
            .title("News")
            .fields([nga.field('id')
                .label('Id'),
            nga.field('title')
                .label('Title'),
            nga.field('description', 'wysiwyg')
                .map(truncate)
                .label('Description'),
            nga.field('is_published')
                .label('Is Published'),
            nga.field('published_on')
                .label('Published On'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([nga.field('q')
            .label('Search', 'template')
            .pinned(true)
            .template(''), nga.field('filter', 'choice')
            .label('Status')
            .attributes({
                    placeholder: 'Status?'
                })
            .choices([{
                    label: 'Active',
                    value: true
            }, {
                    label: 'Inactive',
                    value: false
            }])]);
        news.creationView()
            .fields([nga.field('title')
                .validation({
                    required: true
                })
                .label('Title'),
            nga.field('news_category_id', 'reference_many')
                .label('Categories')
                .perPage('all')
                .targetEntity(news_category)
                .targetField(nga.field('name'))
                .remoteComplete(true)
                .singleApiCall(function(tagIds) {
                    return {
                        'news_category_id': tagIds
                    };
                })
                .validation({
                    required: true
                }),
            nga.field('image', 'file')
                    .label("Image")
                    .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment'
                }),
            nga.field('description', 'wysiwyg')
                .validation({
                    required: true
                })
                .label('Description'),
            nga.field('is_published', 'boolean')
                .validation({
                    required: true
                })
                .label('Is Published'),
            nga.field('published_on', 'datetime')
                .validation({
                    required: true
                })
                .label('Published On'),
        ]);
        news.showView()
            .fields([nga.field('id')
                .label('Id'),
            nga.field('created_at')
                .label('Added On'),
            nga.field('title')
                .label('Title'),
            nga.field('description', 'wysiwyg')
                .label('Description', 'wysmig'),
            nga.field('is_published')
                .label('Is Published'),
            nga.field('published_on')
                .label('Published On'),
        ]);
        news.editionView()
            .fields(news.creationView()
                .fields());
        /* news category */
        news_category.listView()
            .title("News Category")
            .fields([nga.field('id')
                .label('Id'),
                nga.field('name')
                                .label('Name'),
                nga.field('is_active', 'boolean')
                                .label('Is Active'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([nga.field('q')
        .label('Search', 'template')
        .pinned(true)
        .template(''), nga.field('filter', 'choice')
        .label('Status')
        .attributes({
                    placeholder: 'Status?'
                })
        .choices([{
                    label: 'Active',
                    value: true
        }, {
                    label: 'Inactive',
                    value: false
        }])]);
        news_category.creationView()
            .fields([
                nga.field('name')
                .label('Name'),
                nga.field('is_active', 'choice')
                        .label('Active?')
                        .attributes({
                    placeholder: 'Active?'
                })
                        .validation({
                    required: true
                })
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }]),
        ]);
        news_category.showView()
            .fields([
                nga.field('id')
                                    .label('Id'),
                nga.field('created_at')
                               .label('Added On'),
                nga.field('name')
                                .label('Name'),
                nga.field('is_active', 'boolean')
                                .label('Is Active'),
        ]);
        news_category.editionView()
            .fields(news_category.creationView()
                .fields());
        /* newsletters */
        newsletter.listView()
            .title("Newsletters")
            .fields([nga.field('id')
                        .label('Id'),
                nga.field('created_at')
                        .label('Added On'),
                nga.field('email')
                        .label('Email'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'delete'])
            .filters([nga.field('q')
        .label('Search', 'template')
        .pinned(true)
        .template(''), nga.field('filter', 'choice')
        .label('Status')
        .attributes({
                    placeholder: 'Status?'
                })
        .choices([{
                    label: 'Active',
                    value: true
        }, {
                    label: 'Inactive',
                    value: false
        }])]);
        newsletter.creationView()
            .fields([nga.field('email', 'email')
                .label('Email'),
        ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
            }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
            }]);
        newsletter.showView()
            .fields([nga.field('id')
                .label('Id'),
                nga.field('created_at')
                        .label('Added On'),
                nga.field('email')
                        .label('Email'),
        ]);
        newsletter.editionView()
            .fields(newsletter.creationView()
                .fields());
        /* series */
        series.deletionView()
            .title('Event Types');
        series.listView()
            .title("Event Types")
            .fields([
                nga.field('id')
                .label('Id'),
                nga.field('created_at')
                                .label('Added On'),
                nga.field('name')
                                .label('Name'),
                nga.field('is_active', 'boolean')
                                .label('Is Active'),
            ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([nga.field('q')
                .label('Search', 'template')
                .pinned(true)
                .template(''), nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
                .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])]);
        series.creationView()
            .title('Event Types')
            .fields([
                nga.field('name')
                .validation({
                    required: true
                })
                .label('Name'), ,
                nga.field('is_active', 'choice')
                                .label('Active?')
                                .attributes({
                    placeholder: 'Active?'
                })
                                .validation({
                    required: true
                })
                                .choices([{
                    label: 'Yes',
                    value: true
                                }, {
                    label: 'No',
                    value: false
                                }]),
                ]);
        series.showView()
            .title('Event Types')
            .fields([
                nga.field('id')
                .label('Id'),
                nga.field('created_at')
                               .label('Added On'),
                nga.field('name')
                                .label('Name'),
                nga.field('is_active', 'boolean')
                                .label('Is Active'),
            ]);
        series.editionView()
            .title('Event Types')
            .fields(series.creationView()
                .fields());
        /* venues */
        venue.listView()
            .title("Venues")
            .fields([
            nga.field('id')
                .label('Id'),
            nga.field('name')
                        .label('Name'),
            nga.field('address1')
                        .label('Address1'),
            nga.field('address2')
                        .label('Address2'),
            nga.field('city_id', 'reference')
                        .targetEntity(city)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('Name'),
            nga.field('state_id', 'reference')
                        .targetEntity(state)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('Name'),
            nga.field('country_id', 'reference')
                        .targetEntity(country)
                        .perPage('all')
                        .targetField(nga.field('name'))
                        .label('Name'),
            nga.field('is_active', 'boolean')
                        .label('Is Active'),
            nga.field('is_seat_map', 'boolean')
                        .label('Seap map enabled')
                         .validation({
                    required: true
                })
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([nga.field('q')
        .label('Search', 'template')
        .pinned(true)
        .template(''), nga.field('filter', 'choice')
        .label('Status')
        .attributes({
                    placeholder: 'Status?'
                })
        .choices([{
                    label: 'Active',
                    value: true
        }, {
                    label: 'Inactive',
                    value: false
        }])]);
        venue.creationView()
            .fields([
            nga.field('name')
                .validation({
                    required: true
                })
                .label('Name'),
            nga.field('address1')
                        .validation({
                    required: true
                })
                        .label('Address1'),
            nga.field('address2')
                        .validation({
                    required: true
                })
                        .label('Address2'),
            nga.field('city_id', 'reference')
                        .label('City')
                        .targetEntity(city)
                        .targetField(nga.field('name'))
                        .remoteComplete(true),
            nga.field('state_id', 'reference')
                        .label('State')
                        .targetEntity(state)
                        .targetField(nga.field('name'))
                        .remoteComplete(true),
            nga.field('country_id', 'reference')
                        .label('Country')
                        .perPage('all')
                        .targetEntity(country)
                        .targetField(nga.field('name'))
                        .remoteComplete(true),
            nga.field('is_active', 'choice')
                        .label('Active?')
                        .attributes({
                    placeholder: 'Active?'
                })
                        .validation({
                    required: true
                })
                        .choices([{
                    label: 'Yes',
                    value: true
                        }, {
                    label: 'No',
                    value: false
                        }]),
             nga.field('svg_image', 'file')
                    .label("SVG Image")
                    .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment'
                }),
            nga.field('image', 'file')
                    .label("Image")
                    .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment'
                }),
           nga.field('is_seat_map', 'boolean')
                        .label('Seap map enabled')
                         .validation({
                    required: true
                })
        ]);
        venue.showView()
            .fields([
            nga.field('id')
            .label('Id'),
            nga.field('created_at')
                        .label('Added On'),
            nga.field('updated_at')
                        .label('Modfied  On'),
            nga.field('name')
                        .label('Name'),
            nga.field('address1')
                        .label('Address1'),
            nga.field('address2')
                        .label('Address2'),
            nga.field('city_id')
                        .label('City Id'),
            nga.field('state_id')
                        .label('State Id'),
            nga.field('country_id')
                        .label('Country Id'),
            nga.field('is_active', 'boolean')
                        .label('Is Active'),
            nga.field('is_seat_map', 'boolean')
                        .label('Seap map enabled')
                         .validation({
                    required: true
                })
            ]);
        venue.editionView()
            .prepare(['Restangular', 'datastore', 'Entry', 'entry', function(Restangular, datastore, Entry, entry) {
                delete entry.values.attachments;
            }])
            .fields(venue.creationView()
                .fields());
        /* venue zone */
        venue_zone.listView()
            .title("Venue Zones")
            .fields([nga.field('id')
                .label('Id'),
            nga.field('name')
                .label('Name'),
            nga.field('seat_count')
                .label('Seat Count'),
            nga.field('venue_id', 'reference')
                .targetEntity(venue)
                .targetField(nga.field('name'))
                .label('Name'),
            nga.field('is_having_seat_selection')
                .label('Is Having Seat Selection'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete', '<ma-create-button entity-name="venue_zone_previews" size="sm" label="Add preview" default-values="{ venue_zone_id: entry.values.id, venue_id:entry.values.venue_id }"></ma-create-button>'])
            .filters([nga.field('q')
                .label('Search', 'template')
        .pinned(true)
                .template(''),
        nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
        .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])]);
        venue_zone.creationView()
            .fields([
            nga.field('name')
                .validation({
                    required: true
                })
                .label('Name'),
            nga.field('venue_id', 'reference')
                .label('Venue')
                .perPage('all')
                .targetEntity(venue)
                .targetField(nga.field('name'))
                .remoteComplete(true),
            nga.field('is_having_seat_selection', 'boolean')
                .validation({
                    required: true
                })
                .label('Is Having Seat Selection'),
            nga.field('svg_image', 'file')
                .label("SVG Image")
                .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment'
                }),
            nga.field('image', 'file')
                .label("Image")
                .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment'
                }),

        ]);
        venue_zone.showView()
            .fields([nga.field('id')
                .label('Id'),
            nga.field('created_at')
                .label('Added On'),
            nga.field('updated_at')
               .label('Modified On'),
            nga.field('name')
                .label('Name'),
            nga.field('venue_id')
                .label('Venue Id'),
            nga.field('is_having_seat_selection')
                .label('Is Having Seat Selection'),
        ]);
        venue_zone.editionView()
            .prepare(['Restangular', 'datastore', 'Entry', 'entry', function(Restangular, datastore, Entry, entry) {
                delete entry.values.venue_zone_section_row;
                delete entry.values.venue_zone_section_seats;
                delete entry.values.venue_zone_sections;
                delete entry.values.attachments;
                delete entry.values.seat_count;
 }])
            .fields(venue_zone.creationView()
                .fields());
        /* venue zone previews*/
        var venue_zone_preview_template = '<ma-filter-button filters="filters()" enabled-filters="enabledFilters" enable-filter="enableFilter()"></ma-filter-button><ma-export-to-csv-button entry="entry" entity="entity" size="sm" datastore="::datastore"></ma-export-to-csv-button>';
        venue_zone_preview.listView()
            .title("Venue Zone Previews")
            .fields([nga.field('id')
                .label('Id'),
            nga.field('venue_zone_section_seats.venue.name')
                .label('Venue'),
            nga.field('venue_zone_section_seats.venue_zone.name')
                .label('Venue Zone'),
            nga.field('venue_zone_section_seats.venue_zone_section.name')
                .label('Section'),
            nga.field('venue_zone_section_seats.venue_zone_section_row.name')
                .label('Row'),
            nga.field('venue_zone_section_seats.seat_number')
                .label('Seat number'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['<a class=\"btn btn-default btn-xs\" href=\"#/venue_zone_previews/show/{{entry.values.id}}?venue_id={{entry.values.venue_id}}\" >\n<span class=\"glyphicon glyphicon-eye\" aria-hidden=\"true\"></span>&nbsp;Show<span class=\"hidden-xs\"></span>\n</a>', "<span class=\"edit-button\"><a class=\"btn btn-default btn-xs\" href=\"#/venue_zone_previews/edit/{{entry.values.id}}?venue_id={{entry.values.venue_id}}&venue_zone_id={{entry.values.venue_zone_id}}\" >\n<span class=\"glyphicon glyphicon-pencil\" aria-hidden=\"true\"></span>&nbsp;Edit<span class=\"hidden-xs\"></span>\n</a></span>", '<a class=\"btn btn-danger btn-xs\" href=\"#/venue_zone_previews/delete/{{entry.values.id}}?venue_id={{entry.values.venue_id}}\" >\n<span class=\"glyphicon glyphicon-delete\" aria-hidden=\"true\"></span>&nbsp;Delete<span class=\"hidden-xs\"></span>\n</a>'])
            .actions(venue_zone_preview_template)
            .filters([nga.field('q')
                .label('Search', 'template')
        .pinned(true)
        .template(''),
        nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
        .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])]);
        venue_zone_preview.creationView()
            .fields([
            nga.field('venue_id')
                .label('Venue')
                .validation({
                    required: true
                })
                .editable(false),
            nga.field('venue_zone_id')
                .label('Venue Zone Id')
                .editable(false),
            nga.field('venue_section_id')
                .template('<custom-dropdown entry="entry" entity="entity" name="" type="section" label="Section name"></custom-dropdown>')
                .label('venue section'),
            nga.field('venue_section_row_id')
                .template('<custom-dropdown entry="entry" entity="entity" name="" type="row" label="Row name"></custom-dropdown>')
                .label('Venue row'),
            nga.field('venue_section_row_seat_id')
                .template('<custom-dropdown entry="entry" entity="entity" name="" type="seat" label="Seat number"></custom-dropdown>')
                .label('Seat number'),
            nga.field('image', 'file')
                .label("Image")
                .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment'
                })
        ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
            }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
            }]);
        venue_zone_preview.showView()
            .fields([nga.field('id')
                .label('Id'),
            nga.field('created_at')
                .label('Added On'),
            nga.field('updated_at')
                .label('Modified On'),
            nga.field('venue_id', 'reference')
                .targetEntity(venue)
                .targetField(nga.field('name'))
                .label('Name'),
            nga.field('venue_zone_id', 'reference')
                .targetEntity(venue_zone)
                .targetField(nga.field('name'))
                .label('Venue Zone'),
            nga.field('venue_section_id', 'reference')
                .targetEntity(venue_zone_section)
                .targetField(nga.field('name'))
                .label('Section'),
            nga.field('venue_section_row_id', 'reference')
                .targetEntity(venue_zone_section_row)
                .targetField(nga.field('name'))
                .label('Row'),
            nga.field('venue_section_row_seat_id', 'reference')
                .targetEntity(venue_zone_section_seat)
                .targetField(nga.field('name'))
                .label('Seat number')
        ]);
        venue_zone_preview.editionView()
            .prepare(['Restangular', 'datastore', 'Entry', 'entry', function(Restangular, datastore, Entry, entry) {}])
            .fields([
            nga.field('venue_id')
                .label('Venue')
                .validation({
                    required: true
                })
                .editable(false),
            nga.field('venue_zone_id')
                .label('Venue Zone Id')
                .editable(false),
            nga.field('venue_section_id')
                .template('<custom-dropdown entry="entry" entity="entity" name="" type="section" label="Section name" default="true" ></custom-dropdown>')
                .label('venue section'),
            nga.field('venue_section_row_id')
                .template('<custom-dropdown entry="entry" entity="entity" name="" type="row" label="Row name" default="true"></custom-dropdown>')
                .label('Venue row'),
            nga.field('venue_section_row_seat_id')
                .template('<custom-dropdown entry="entry" entity="entity" name="" type="seat" label="Seat number" default="true"></custom-dropdown>')
                .label('Seat number'),
            nga.field('image', 'file')
                .label("Image")
                .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment'
                })
        ])
            .onSubmitSuccess(['progression', 'notification', '$state', 'entry', 'entity', function(progression, notification, $state, entry, entity) {
                $state.go($state.get('list'), {
                    entity: entity.name()
                });
                return false;
            }])
            .onSubmitError(['error', 'form', 'progression', 'notification', function(error, form, progression, notification) {
                angular.forEach(error.data.errors, function(value, key) {
                    if (this[key]) {
                        this[key].$valid = false;
                    }
                }, form);
                progression.done();
                notification.log(error.data.message, {
                    addnCls: 'humane-flatty-error'
                });
                return false;
            }]);
        /* events */
        event.listView()
            .title("Events")
            .fields([nga.field('id')
                .label('Id'),
            nga.field('venue_id', 'reference')
                .targetEntity(venue)
                .targetField(nga.field('name'))
                .label('Venue name'),
            nga.field('category_id', 'reference')
                .targetEntity(category)
                .targetField(nga.field('name'))
                .label('Category name'),
            nga.field('series_id', 'reference')
                .targetEntity(series)
                .targetField(nga.field('name'))
                .label('Event type name'),
            nga.field('name')
                .label('Name'),
            nga.field('description', 'wysiwyg')
                .label('Description')
                .map(truncate),
            nga.field('trailer_video_url')
                .label('Trailer Video Url'),
            nga.field('is_active', 'boolean')
                .label('Is Active'),
            nga.field('is_free_event')
                .label('Is Free Event'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete', '<a class="btn btn-default btn-xs" href="#/eventszone/add?event_id={{entry.values.id}}&venue_id={{entry.values.venue_id}}" >\n<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>&nbsp;Add Event Zone<span class="hidden-xs"></span>\n</a>'])
            .filters([
            nga.field('q')
                .label('Search', 'template')
             .pinned(true)
             .template(''),
              nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
               .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])
        ]);
        event.creationView()
            .
        fields([
            nga.field('venue_id', 'reference')
            .targetEntity(venue)
            .perPage('all')
            .targetField(nga.field('name'))
            .label('Venue')
            .remoteComplete(true),
            nga.field('category_id', 'reference')
            .label('Categories')
            .perPage('all')
            .targetEntity(category)
            .targetField(nga.field('name'))
            .label('Categories')
            .remoteComplete(true),
            nga.field('series_id', 'reference')
            .label('Event Types')
            .perPage('all')
            .targetEntity(series)
            .targetField(nga.field('name'))
            .label('Event Types')
            .remoteComplete(true),
            nga.field('name')
            .validation({
                required: true
            })
            .label('Name'),
            nga.field('terms_and_conditions', 'wysiwyg')
            .map(truncate)
            .validation({
                required: true
            })
            .label('Terms and Conditions'),
            nga.field('description', 'wysiwyg')
            .validation({
                required: true
            })
            .label('Description'),
            nga.field('trailer_video_url')
            .validation({
                required: true
            })
            .label('Trailer Video Url'),
            nga.field('is_active', 'choice')
            .label('Active?')
            .attributes({
                placeholder: 'Active?'
            })
            .validation({
                required: true
            })
            .choices([{
                label: 'Yes',
                value: true
            }, {
                label: 'No',
                value: false
            }]),
            nga.field('is_free_event', 'boolean')
            .label('Is Free Event')
             .validation({
                required: true
            }),
            nga.field('video', 'file')
                    .label("Video")
                    .uploadInformation({
                'url': admin_api_url + 'api/v1/attachments',
                'apifilename': 'attachment',
                'accept': ''
            }),
            nga.field('image', 'template')
               .label("Image")
               .template('<upload-image entry="entry" class="Event"></upload-image>')
              .validation({
                required: true
            }),
            /*nga.field('image', 'file')
                    .label("Image")
                    .uploadInformation({
                'url': admin_api_url + 'api/v1/attachments?class=Event',
                'apifilename': 'attachment',
                'accept': ''
            }).validation({
                required: true
            }),*/
            nga.field('floor_plan', 'file')
                .label("Floor Plan Image")
                .uploadInformation({
                'url': admin_api_url + 'api/v1/attachments',
                'apifilename': 'attachment',
                'accept': '',
            })
            .validation({
                required: true
            }),
            nga.field('ticket_prices', 'file')
                    .label("Ticket Prices Image")
                    .uploadInformation({
                'url': admin_api_url + 'api/v1/attachments',
                'apifilename': 'attachment',
                'accept': ''
            })
                .validation({
                required: true
            }),
            nga.field('eventschedule', 'template')
                .template('<event-basket entry="entry" entity="entity"></event-basket>')


        ]);
        event.showView()
            .fields([nga.field('id')
                .label('Id'),
            nga.field('created_at')
                .label('Added On'),
            nga.field('updated_at')
                .label('Modified On'),
            nga.field('venue_id', 'reference')
                .targetEntity(venue)
                .targetField(nga.field('name'))
                .label('Venue'),
            nga.field('category_id', 'reference')
                .targetEntity(category)
                .targetField(nga.field('name'))
                .label('Category'),
            nga.field('series_id', 'reference')
                .targetEntity(series)
                .targetField(nga.field('name'))
                .label('Event Type'),
            nga.field('name')
                .label('Name'),
            nga.field('description', 'wysiwyg')
                .label('Description'),
            nga.field('terms_and_conditions', 'wysiwyg')
            .map(truncate)
            .validation({
                    required: true
                }),
            nga.field('trailer_video_url', 'wysiwyg')
                .label('Trailer Video Url'),
            nga.field('is_active', 'boolean')
                .label('Is Active'),
            nga.field('is_free_event', 'boolean')
                .label('Is Free Event'),
        ]);
        event.editionView()
            .prepare(['Restangular', 'datastore', 'Entry', 'entry', function(Restangular, datastore, Entry, entry) {
                /*delete entry.values.attachments;
                delete entry.values.event_schedule;
                delete entry.values.category;
                delete entry.values.series;
                delete entry.values.venue;
                delete entry.values.attachment_floor_plan;
                delete entry.values.attachment_ticket_price;*/
            }])
            .fields([
            nga.field('venue_id', 'reference')
                .targetEntity(venue)
                .perPage('all')
                .targetField(nga.field('name'))
                .label('Venue')
                .remoteComplete(true),
            nga.field('category_id', 'reference')
                .label('Categories')
                .perPage('all')
                .targetEntity(category)
                .targetField(nga.field('name'))
                .label('Categories')
                .remoteComplete(true),
            nga.field('series_id', 'reference')
                .label('Event Types')
                .perPage('all')
                .targetEntity(series)
                .targetField(nga.field('name'))
                .label('Event Types')
                .remoteComplete(true),
            nga.field('name')
                .validation({
                    required: true
                })
                .label('Name'),
            nga.field('description', 'wysiwyg')
                .validation({
                    required: true
                })
                .label('Description'),
            nga.field('trailer_video_url')
                .validation({
                    required: true
                })
                .label('Trailer Video Url'),
            nga.field('is_active', 'choice')
                .label('Active?')
                .attributes({
                    placeholder: 'Active?'
                })
                .validation({
                    required: true
                })
                .choices([{
                    label: 'Yes',
                    value: true
                }, {
                    label: 'No',
                    value: false
                }]),
            nga.field('image', 'template')
               .label("Image")
               .template('<upload-image entry="entry" class="Event"></upload-image>')
              .validation({
                    required: true
                }),
           /* nga.field('image', 'file')
                    .label("Image")
                    .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments?class=Event',
                    'apifilename': 'attachment'
                }),*/
           nga.field('floor_plan', 'file')
                    .label("Floor Plan Image")
                    .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment',
                    'accept': ''
                }),
            nga.field('ticket_prices', 'file')
                    .label("Ticket Prices Image")
                    .uploadInformation({
                    'url': admin_api_url + 'api/v1/attachments',
                    'apifilename': 'attachment',
                    'accept': ''
                }),
           nga.field('is_free_event', 'boolean')
                .validation({
                    required: true
                })
                .label('Is Free Event'),
           nga.field('eventschedule', 'template')
                .template('<event-basket entry="entry" entity="entity"></event-basket>'),
        ]);
        /* events schedule */
        event_schedule.listView()
            .title("Event Schedules")
            .fields([nga.field('id')
                .label('Id'),
            nga.field('event_id', 'reference')
                .targetEntity(event)
                .targetField(nga.field('name'))
                .label('Name'),
            nga.field('start_date')
                .label('Start Date'),
            nga.field('end_date')
                .label('End Date'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([nga.field('q')
                .label('Search', 'template')
                .pinned(true)
                .template(''), nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
                .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])]);
        event_schedule.creationView()
            .fields([
            nga.field('event_id', 'reference')
                .targetEntity(event)
                .perPage('all')
                .targetField(nga.field('name'))
                .label('Name')
                .remoteComplete(true),
            nga.field('start_date', 'datetime')
                .label('Start Date'),
            nga.field('end_date', 'datetime')
                .label('End Date'),
        ]);
        event_schedule.showView()
            .fields([
            nga.field('id')
                .label('Id'),
            nga.field('created_at')
                .label('Added On'),
            nga.field('updated_at')
                .label('Modified On'),
            nga.field('event_id')
                .label('Event Id'),
            nga.field('start_date')
                .label('Start Date'),
            nga.field('end_date')
                .label('End Date'),
        ]);
        event_schedule.editionView()
            .fields(event_schedule.creationView()
                .fields());
        /* event zones */
        var event_zone_action_template = '<ma-filter-button filters="filters()" enabled-filters="enabledFilters" enable-filter="enableFilter()"></ma-filter-button><ma-export-to-csv-button entry="entry" entity="entity" size="sm" datastore="::datastore"></ma-export-to-csv-button>';
        event_zone.listView()
            .title("Event Zones")
            .fields([nga.field('id')
                .label('Id'),
            nga.field('event_id', 'reference')
                .targetEntity(event)
                .targetField(nga.field('name'))
                .label('Event Name'),
            nga.field('venue_id', 'reference')
                .targetEntity(venue)
                .targetField(nga.field('name'))
                .label('Venue Name'),
            nga.field('venue_zone_id')
                .label('Venue Zone Id'),
            nga.field('is_available', 'boolean')
                .label('Is Available'),
            nga.field('available_count')
                .label('Available Count'),
        ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', '<span class="edit-button"><a class="btn btn-default btn-xs" href="#/eventszone/update/{{entry.values.id}}?venue_id={{entry.values.venue_id}}" >\n<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>&nbsp;Edit<span class="hidden-xs"></span>\n</a></span>', 'delete'])
            .actions(event_zone_action_template)
            .filters([
            nga.field('q')
                .label('Search', 'template')
                .pinned(true)
                .template(''),
            nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
                .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])
        ]);
        event_zone.showView()
            .fields([nga.field('id')
                .label('Id'),
            nga.field('created_at')
                .label('Added On'),
            nga.field('updated_at')
                .label('Modified On'),
            nga.field('event_id')
                .label('Event Id'),
            nga.field('venue_id')
                .label('Venue Id'),
            nga.field('venue_zone_id')
                .label('Venue Zone Id'),
            nga.field('is_available')
                .label('Is Available'),
            nga.field('available_count')
                .label('Available Count'),
        ]);
        /** gift vouchers **/
        gift_voucher.listView()
            .title("Gift Vouchers")
            .fields([nga.field('id')
                .label('Id'),
            nga.field('user_id', 'reference')
                .targetEntity(user)
                .targetField(nga.field('username'))
                .label('Username'),
            nga.field('amount')
                .label('Amount'),
            nga.field('is_general')
                .label('Is General'),
            nga.field('from_name')
                .label('From Name'),
            nga.field('to_name')
                .label('To Name'),
            nga.field('to_email')
                .label('To Email'),
            nga.field('message', 'wysiwyg')
                .map(truncate)
                .label('Message'),
            nga.field('code')
                .label('Code'),
            nga.field('is_used', 'boolean')
                .label('Is Used'),
            nga.field('avaliable_amount')
                .label('Avaliable Amount'),
            ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([
            nga.field('q')
                .label('Search', 'template')
                .pinned(true)
                .template(''),
            nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
                .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])
            ]);
        gift_voucher.creationView()
            .fields([nga.field('user_id', 'reference')
                .targetEntity(user)
                .targetField(nga.field('username'))
                .label('Username')
                .remoteComplete(true),
            nga.field('amount')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Amount'
                })
                .label('Amount'),
            nga.field('is_general')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Is General'
                })
                .label('Is General'),
            nga.field('from_name')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'From Name'
                })
                .label('From Name'),
            nga.field('to_name')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'To Name'
                })
                .label('To Name'),
            nga.field('to_email')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'To Email'
                })
                .label('To Email'),
            nga.field('message', 'wysiwyg')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Message'
                })
                .label('Message'),
            nga.field('code')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Code'
                })
                .label('Code'),
            nga.field('is_used', 'boolean')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Is Used'
                })
                .label('Is Used'),
            nga.field('avaliable_amount')
                .attributes({
                    placeholder: 'Avaliable Amount'
                })
                .label('Avaliable Amount'),
            ]);
        gift_voucher.showView()
            .fields([nga.field('id')
                .label('Id'),
            nga.field('created_at')
                .label('Added On'),
            nga.field('updated_at')
                .label('Modified On'),
            nga.field('user_id')
                .label('User Id'),
            nga.field('amount')
                .label('Amount'),
            nga.field('is_general')
                .label('Is General'),
            nga.field('from_name')
                .label('From Name'),
            nga.field('to_name')
                .label('To Name'),
            nga.field('to_email')
                .label('To Email'),
            nga.field('message')
                .label('Message'),
            nga.field('code')
                .label('Code'),
            nga.field('is_used')
                .label('Is Used'),
            nga.field('avaliable_amount')
                .label('Avaliable Amount'),
            ]);
        gift_voucher.editionView()
            .fields(gift_voucher.creationView()
                .fields());
        /** orders  **/
        order.listView()
            .title("Orders")
            .fields([nga.field('id')
                .label('Id'),
                nga.field('user_id', 'reference')
                .targetEntity(user)
                .targetField(nga.field('username'))
                .label('Username'),
                nga.field('order_status_id')
                .label('Order Status Id'),
                nga.field('address')
                .label('Address'),
                nga.field('city_id', 'reference')
                .targetEntity(city)
                .targetField(nga.field('name'))
                .label('City'),
                nga.field('state_id', 'reference')
                .targetEntity(state)
                .targetField(nga.field('name'))
                .label('State'),
                nga.field('country_id', 'reference')
                .targetEntity(country)
                .targetField(nga.field('name'))
                .label('Country'),
                nga.field('coupon_id', 'reference')
                .targetEntity(coupon)
                .targetField(nga.field('name'))
                .label('Coupon'),
                nga.field('payment_gateway_id')
                .label('Payment Gateway Id'),
                nga.field('quantity')
                .label('Quantity'),
                nga.field('price')
                .label('Price'),
                nga.field('donation_amount')
                .label('Donation Amount'),
                nga.field('total_amount')
                .label('Total Amount'),
                nga.field('delivery_method_id')
                .label('Delivery Method Id'),
                nga.field('delivery_amount')
                .label('Delivery Amount'),
                nga.field('address1')
                .label('Address1'),
                nga.field('zip_code')
                .label('Zip Code'),
                nga.field('site_fee')
                .label('Site Fee'),
                nga.field('event_id', 'reference')
                .targetEntity(event)
                .targetField(nga.field('name'))
                .label('Name'),
                nga.field('gift_voucher_id')
                .label('Gift Voucher Id'),
                ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([
                nga.field('q')
                .label('Search', 'template')
                .pinned(true)
                .template(''),
                nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
                .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])
                ]);
        order.creationView()
            .fields([nga.field('user_id', 'reference')
                .targetEntity(user)
                .targetField(nga.field('username'))
                .label('Username')
                .remoteComplete(true),
                nga.field('order_status_id')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Order Status Id'
                })
                .label('Order Status Id'),
                nga.field('address')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Address'
                })
                .label('Address'),
                nga.field('city_id', 'reference')
                .targetEntity(city)
                .targetField(nga.field('name'))
                .label('City')
                .remoteComplete(true),
                nga.field('state_id', 'reference')
                .targetEntity(state)
                .targetField(nga.field('name'))
                .label('State')
                .remoteComplete(true),
                nga.field('country_id', 'reference')
                .targetEntity(country)
                .targetField(nga.field('name'))
                .label('Country')
                .remoteComplete(true),
                nga.field('coupon_id', 'reference')
                .targetEntity(coupon)
                .targetField(nga.field('name'))
                .label('Coupon')
                .remoteComplete(true),
                nga.field('payment_gateway_id')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Payment Gateway Id'
                })
                .label('Payment Gateway Id'),
                nga.field('quantity')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Quantity'
                })
                .label('Quantity'),
                nga.field('price')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Price'
                })
                .label('Price'),
                nga.field('donation_amount')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Donation Amount'
                })
                .label('Donation Amount'),
                nga.field('total_amount')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Total Amount'
                })
                .label('Total Amount'),
                nga.field('delivery_method_id')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Delivery Method Id'
                })
                .label('Delivery Method Id'),
                nga.field('delivery_amount')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Delivery Amount'
                })
                .label('Delivery Amount'),
                nga.field('address1')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Address1'
                })
                .label('Address1'),
                nga.field('zip_code')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Zip Code'
                })
                .label('Zip Code'),
                nga.field('site_fee')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Site Fee'
                })
                .label('Site Fee'),
                nga.field('event_id', 'reference')
                .targetEntity(event)
                .targetField(nga.field('name'))
                .label('Name')
                .remoteComplete(true),
                nga.field('gift_voucher_id', 'number')
                .attributes({
                    placeholder: 'Gift Voucher Id'
                })
                .label('Gift Voucher Id'),
                ]);
        order.showView()
            .fields([nga.field('id')
                .label('Id'),
                nga.field('created_at')
                .label('Added On'),
                nga.field('updated_at')
                .label('Modified On'),
                nga.field('user_id')
                .label('User Id'),
                nga.field('order_status_id')
                .label('Order Status Id'),
                nga.field('address')
                .label('Address'),
                nga.field('city_id')
                .label('City Id'),
                nga.field('state_id')
                .label('State Id'),
                nga.field('country_id')
                .label('Country Id'),
                nga.field('coupon_id')
                .label('Coupon Id'),
                nga.field('payment_gateway_id')
                .label('Payment Gateway Id'),
                nga.field('quantity')
                .label('Quantity'),
                nga.field('price')
                .label('Price'),
                nga.field('donation_amount')
                .label('Donation Amount'),
                nga.field('total_amount')
                .label('Total Amount'),
                nga.field('delivery_method_id')
                .label('Delivery Method Id'),
                nga.field('delivery_amount')
                .label('Delivery Amount'),
                nga.field('address1')
                .label('Address1'),
                nga.field('zip_code')
                .label('Zip Code'),
                nga.field('site_fee')
                .label('Site Fee'),
                nga.field('event_id')
                .label('Event Id'),
                nga.field('gift_voucher_id')
                .label('Gift Voucher Id'),
                ]);
        order.editionView()
            .fields(order.creationView()
                .fields());
        /** coupons **/
        coupon.listView()
            .title("Coupons")
            .fields([nga.field('id')
                .label('Id'),
                nga.field('name')
                .label('Name'),
                nga.field('is_flat_discount', 'boolean')
                .label('Is Flat Discount'),
                nga.field('discount')
                .label('Discount'),
                nga.field('code')
                .label('Code'),
                ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([
                nga.field('q')
                .label('Search', 'template')
                .pinned(true)
                .template(''),
                nga.field('filter', 'choice')
                .label('Status')
                .attributes({
                    placeholder: 'Status?'
                })
                .choices([{
                    label: 'Active',
                    value: true
                }, {
                    label: 'Inactive',
                    value: false
                }])
                ]);
        coupon.creationView()
            .fields([nga.field('name')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Name'
                })
                .label('Name'),
                nga.field('is_flat_discount', 'boolean')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Is Flat Discount'
                })
                .label('Is Flat Discount'),
                nga.field('discount')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Discount'
                })
                .label('Discount'),
                nga.field('code')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Code'
                })
                .label('Code'),
                ]);
        coupon.showView()
            .fields([nga.field('id')
                .label('Id'),
                nga.field('created_at')
                .label('Added On'),
                nga.field('updated_at')
                .label('Modified On'),
                nga.field('name')
                .label('Name'),
                nga.field('is_flat_discount')
                .label('Is Flat Discount'),
                nga.field('discount')
                .label('Discount'),
                nga.field('code')
                .label('Code'),
                ]);
        coupon.editionView()
            .fields(coupon.creationView()
                .fields());
        /* Invitation list view */
        list.listView()
            .title("Invitation Lists")
            .fields([nga.field('id')
                .label('Id'),
                nga.field('name')
                .label('Name'),
                 nga.field('total_guest')
                .label('Person')
                ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete', '<a href=\'#/guests/list?search={"list_id":{{entry.values.id}}}\' title="Users in the lists" class="btn btn-default brn-xs">Users in the list</a>'])
            .filters([
                nga.field('q')
                .label('Search', 'template')
                .pinned(true)
                .template(''),
                nga.field('filter', 'reference')
                .label('Lists')
                .targetEntity(list)
                .targetField(nga.field('name'))
                .attributes({
                    placeholder: 'Lists?'
                })
                ]);
        list.creationView()
            .fields([nga.field('name')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Name'
                })
                .label('Name'),
                ]);
        list.showView()
            .fields([nga.field('id')
                .label('Id'),
                nga.field('created_at')
                .label('Added On'),
                nga.field('updated_at')
                .label('Modified On'),
                nga.field('name')
                .label('Name'),
                nga.field('slug')
                .label('Slug')
                ]);
        list.editionView()
            .fields(list.creationView()
                .fields());
        /* visitors list view */
        visitor.listView()
            .title("Guests")
            .fields([nga.field('id')
                .label('Id'),
                nga.field('first_name')
                .label('First Name'),
                nga.field('last_name')
                .label('Last Name'),
                nga.field('phone')
                .label('Phone')
                ])
            .infinitePagination(false)
            .perPage(limit_per_page)
            .listActions(['show', 'edit', 'delete'])
            .filters([
                nga.field('q')
                .label('Search', 'template')
                .pinned(true)
                .template(''),
                nga.field('list_id', 'reference')
                .label('Lists')
                .targetEntity(list)
                .targetField(nga.field('name'))
                .attributes({
                    placeholder: 'Lists?'
                })
                ]);
        visitor.creationView()
            .fields([
                nga.field('list_id', 'reference_many')
                .targetEntity(list)
                .targetField(nga.field('name'))
                .singleApiCall(function(ids) {
                    return {
                        'list_id': {
                            'list_id': ids
                        }
                    };
                })
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Lists'
                })
                .label('List'),
                nga.field('first_name')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'First Name'
                })
                .label('First Name'),
                nga.field('last_name')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Last Name'
                })
                .label('Last Name'),
                nga.field('phone')
                .validation({
                    required: true
                })
                .attributes({
                    placeholder: 'Phone'
                })
                .label('Phone')
                ]);
        visitor.showView()
            .fields([nga.field('id')
                .label('Id'),
                nga.field('created_at')
                .label('Added On'),
                nga.field('updated_at')
                .label('Modified On'),
                nga.field('first_name')
                .label('First Name'),
                nga.field('last_name')
                .label('Last Name'),
                nga.field('phone')
                .label('Phone')
                ]);
        visitor.editionView()
            .fields(visitor.creationView()
                .fields());
        //Invitation send
        /* send_invitation.creationView()
             .title('DIGITAL INVITATION DELIVERY (SMS)')
             .fields([
                 nga.field('list_id', 'reference_many')
                 .targetEntity(list).targetField(nga.field('name'))
                 .singleApiCall(function(ids) {
                     console.log("ids", ids);
                     return {
                         'list_id': {'list_id':ids}
                     };
                 })
                 .validation({
                     required: true
                 })
                 .attributes({
                     placeholder: 'Lists'
                 })
                 .label('List'),
               
                 nga.field('first_name')
                 .validation({
                     required: true
                 })
                 .attributes({
                     placeholder: 'First Name'
                 })
                 .label('First Name'),
                 nga.field('last_name')
                 .validation({
                     required: true
                 })
                 .attributes({
                     placeholder: 'Last Name'
                 })
                 .label('Last Name'),
                 nga.field('phone')
                 .validation({
                     required: true
                 })
                 .attributes({
                     placeholder: 'Phone'
                 })
                 .label('Phone')
                 ]);*/
    }
    // customize header
    var customHeaderTemplate = '<div class="navbar-header">' + '<button type="button" class="navbar-toggle" ng-click="isCollapsed = !isCollapsed">' + '<span class="icon-bar"></span>' + '<span class="icon-bar"></span>' + '<span class="icon-bar"></span>' + '</button>' + '<a class="navbar-brand well-sm" href="#/dashboard" title="tixmall" ng-click="appController.displayHome()"><img src="../../images/admin-logo.png" height="33" class="pull-left"/><span class="hdr-text h3">' + current_user_role + '</a>' + '</div>';
    admin.header(customHeaderTemplate);
    // customize dashboard
    var dashboardTpl = '<div class="row list-header"><div class="col-lg-12"><div class="page-header">' + '<h4><span>Dashboard</span></h4></div></div></div>' + '<dashboard-summary></dashboard-summary>';
    admin.dashboard(nga.dashboard()
        .template(dashboardTpl));
    nga.configure(admin);
}]);
ngapp.run(['$rootScope', '$location', '$state', 'user_roles', '$cookies', function($rootScope, $location, $state, userRoles, $cookies) {
    $rootScope.$state = $state;
    $rootScope.$on('$stateChangeStart', function(event, toState, toParams, fromState, fromParams) {
        var url = toState.name;
        var exception_arr = ['dashboard', 'login', 'logout'];
        if (($cookies.get("auth") === null || $cookies.get("auth") === undefined) && exception_arr.indexOf(url) === 0) {
            $location.path('/users/login');
        } else {
            $rootScope.$on('$viewContentLoaded', function() {
                var loaderElement = angular.element(document.querySelector('body'));
                loaderElement.addClass('loaded');
            });
        }
        if ($cookies.get("auth") !== null && $cookies.get("auth") !== undefined) {
            var auth = JSON.parse($cookies.get("auth"));
            if (auth.role_id === userRoles.user) {
                $location.path('/users/logout');
            }
        }
        $rootScope.row_dropdown_details = [];
        $rootScope.seat_dropdown_details = [];
        $rootScope.seatValue = [];
        $rootScope.rowValue = [];
    });
}]);