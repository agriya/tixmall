//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:FinancialReportCtrl
 * @description
 * # FinancialReportCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('FinancialReportCtrl', ['$http', '$window', '$timeout', function($http, $window, $timeout) {
        var financial_reports = this;
        financial_reports.loading = true;
        financial_reports.report_details = [];
        /**
         * @ngdoc method
         * @name FinancialReportCtrl.init
         * @methodOf module.FinancialReportCtrl
         * @description
         * This method will get financial report details when controller initiated
         */
        financial_reports.init = function() {
            // getting all delivery methods details
            $http({
                    method: 'GET',
                    url: '/api/v1/delivery_methods',
                })
                .success(function(response) {
                    if (angular.isDefined(response.data)) {
                        financial_reports.delivery_methods = response.data;
                    }
                });
            $http({
                    method: 'GET',
                    url: '/api/v1/financial_reports',
                    params: {
                        filter: "chart"
                    }
                })
                .success(function(response) {
                    financial_reports.loading = false;
                    financial_reports.report_details = response.data;
                    if (angular.isDefined(response.data) && angular.isDefined(response.data.delivery_methods)) {
                        financial_reports.groupDailyDeliveryMethods(response.data.delivery_methods);
                    }
                    if (angular.isDefined(response.data) && angular.isDefined(response.data.sales_channels)) {
                        financial_reports.groupChannels(response.data.sales_channels);
                    }
                    if (angular.isDefined(response.data) && angular.isDefined(response.data.payment_types)) {
                        financial_reports.groupPaymentTypes(response.data.payment_types);
                    }
                    $timeout(function() {
                        financial_reports.refreshChart();
                    }, 100);
                });
        };
        financial_reports.init();
        /**
         * @ngdoc method
         * @name FinancialReportCtrl.financial_reports.groupDailyDeliveryMethods
         * @methodOf module.FinancialReportCtrl
         * @description
         * This Method will group daily sales details by static is range between monday to sunday
         */
        financial_reports.groupDailyDeliveryMethods = function(response) {
            financial_reports.group_delivery_methods = [];
            angular.forEach(financial_reports.delivery_methods, function(delivery_methods_value) {
                var count = 0;
                angular.forEach(response, function(value) {
                    if (parseInt(delivery_methods_value.id) === parseInt(value.delivery_method_id)) {
                        count = value.count;
                    }
                });
                financial_reports.group_delivery_methods.push({
                    "name": delivery_methods_value.name,
                    "count": count
                });
            });
        };
        /**
         * @ngdoc method
         * @name FinancialReportCtrl.financial_reports.groupPaymentTypes
         * @methodOf module.FinancialReportCtrl
         * @description
         * This Method will group payment types details
         */
        financial_reports.groupPaymentTypes = function(response) {
            financial_reports.group_payment_types = [];
            var count;
            count = (response[0].count !== null) ? response[0].count : 0;
            financial_reports.group_payment_types.push({
                "name": "PayFort",
                "count": count
            });
        };
        /**
         * @ngdoc method
         * @name FinancialReportCtrl.financial_reports.groupChannels
         * @methodOf module.FinancialReportCtrl
         * @description
         * This Method will group daily channels,..channel values are static site and web
         */
        financial_reports.groupChannels = function(response) {
            financial_reports.group_sales_channels = [{
                "name": "Mobile Web Site",
                count: 0
            }, {
                "name": "Web Site",
                count: 0
            }];
            var count;
            angular.forEach(response, function(value) {
                if (value.is_booked_via_mobile === true) {
                    count = (value.count !== null) ? value.count : 0;
                    financial_reports.group_sales_channels[0].count = count;
                } else {
                    count = (value.count !== null) ? value.count : 0;
                    financial_reports.group_sales_channels[1].count = count;
                }
            });
        };
        /**
         * @ngdoc method
         * @name FinancialReportCtrl.financial_reports.refreshFilters
         * @methodOf module.FinancialReportCtrl
         * @description
         * This is a callback function on Apply filtering button action
         */
        financial_reports.refreshFilters = function(filterParams) {
            $http({
                    method: 'GET',
                    url: '/api/v1/financial_reports',
                    params: filterParams
                })
                .success(function(response) {
                    financial_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        financial_reports.report_details = response.data;
                        if (angular.isDefined(response.data.delivery_methods)) {
                            financial_reports.groupDailyDeliveryMethods(response.data.delivery_methods);
                        }
                        if (angular.isDefined(response.data.sales_channels)) {
                            financial_reports.groupChannels(response.data.sales_channels);
                        }
                        if (angular.isDefined(response.data.payment_types)) {
                            financial_reports.groupPaymentTypes(response.data.payment_types);
                        }
                    } else {
                        financial_reports.report_details = [];
                    }
                });
        };
        /**
         * @ngdoc method
         * @name FinancialReportCtrl.demographics_reports.refreshChart
         * @methodOf module.FinancialReportCtrl
         * @description
         * This Method will update chart based on filter data
         */
        financial_reports.refreshChart = function() {
            angular.element('document')
                .ready(function() {
                    angular.element('.gvChart')
                        .remove();
                    googleLoaded.done(function() {
                        angular.element("#saleChannelReport")
                            .gvChart({
                                chartType: 'PieChart',
                                gvSettings: {
                                    width: '100%',
                                    height: 400,
                                    fontName: 'Open Sans, sans-serif',
                                    fontSize: 13,
                                    is3D: true,
                                    legend: 'bottom',
                                    chartArea: {
                                        top: 0,
                                        width: "90%",
                                        height: "85%"
                                    }
                                }
                            });
                        angular.element("#orderTypeReport")
                            .gvChart({
                                chartType: 'PieChart',
                                gvSettings: {
                                    width: '100%',
                                    height: 400,
                                    fontName: 'Open Sans, sans-serif',
                                    fontSize: 13,
                                    is3D: true,
                                    legend: 'bottom',
                                    chartArea: {
                                        top: 0,
                                        width: "90%",
                                        height: "85%"
                                    }
                                }
                            });
                        angular.element("#deliveryTypeReport")
                            .gvChart({
                                chartType: 'PieChart',
                                gvSettings: {
                                    width: '100%',
                                    height: 400,
                                    fontName: 'Open Sans, sans-serif',
                                    fontSize: 13,
                                    is3D: true,
                                    legend: 'bottom',
                                    chartArea: {
                                        top: 0,
                                        width: "90%",
                                        height: "85%"
                                    }
                                }
                            });
                        angular.element("#installmentReport")
                            .gvChart({
                                chartType: 'PieChart',
                                gvSettings: {
                                    width: '100%',
                                    height: 400,
                                    fontName: 'Open Sans, sans-serif',
                                    fontSize: 13,
                                    is3D: true,
                                    legend: 'bottom',
                                    chartArea: {
                                        top: 0,
                                        width: "90%",
                                        height: "85%"
                                    }
                                }
                            });
                    });
                });
        };
        // this will make charts responsive on screen size changes
        angular.element($window)
            .bind('resize', function() {
                financial_reports.refreshChart();
            });
    }]);