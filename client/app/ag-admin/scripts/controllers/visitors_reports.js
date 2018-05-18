//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:VisitorsReportCtrl
 * @description
 * # VisitorsReportCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('VisitorsReportCtrl', ['$http', '$window', function($http, $window) {
        var visitors_reports = this;
        visitors_reports.loading = true;
        visitors_reports.report_details = [];
        /**
         * @ngdoc method
         * @name VisitorsReportCtrl.init
         * @methodOf module.VisitorsReportCtrl
         * @description
         * This method will get visitor report details when controller initiated
         */
        visitors_reports.init = function() {
            $http({
                    method: 'GET',
                    url: '/api/v1/visitor_reports',
                    params: {
                        filter: "chart"
                    }
                })
                .success(function(response) {
                    visitors_reports.loading = false;
                    visitors_reports.report_details = response.data;
                    visitors_reports.refreshChart();
                });
        };
        visitors_reports.init();
        /**
         * @ngdoc method
         * @name VisitorsReportCtrl.visitors_reports.refreshFilters
         * @methodOf module.VisitorsReportCtrl
         * @description
         * This is a callback function on Apply filtering button action
         */
        visitors_reports.refreshFilters = function(filterParams) {
            $http({
                    method: 'GET',
                    url: '/api/v1/visitor_reports',
                    params: filterParams
                })
                .success(function(response) {
                    visitors_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        visitors_reports.report_details = response.data;
                    } else {
                        visitors_reports.report_details = [];
                    }
                    visitors_reports.refreshChart();
                });
        };
        /**
         * @ngdoc method
         * @name VisitorsReportCtrl.visitors_reports.refreshChart
         * @methodOf module.VisitorsReportCtrl
         * @description
         * This Method will update chart based on filter data
         */
        visitors_reports.refreshChart = function() {
            angular.element('document')
                .ready(function() {
                    $('.gvChart')
                        .remove();
                    googleLoaded.done(function() {
                        // total visitor reports initialization
                        $("#totalVisitorReport")
                            .gvChart({
                                chartType: 'PieChart',
                                gvSettings: {
                                    width: '100%',
                                    height: 270,
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
                visitors_reports.refreshChart();
            });
    }]);