//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:SalesAgentReportCtrl
 * @description
 * # SalesAgentReportCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('SalesDetailedReportCtrl', ['$http', '$state', function($http, $state) {
        var sales_detailed_reports = this;
        sales_detailed_reports.loading = true;
        sales_detailed_reports.report_details = [];
        /**
         * @ngdoc method
         * @name SalesDetailedReportCtrl.init
         * @methodOf module.SalesDetailedReportCtrl
         * @description
         * This method will get sales detailed report details when controller initiated
         */
        sales_detailed_reports.init = function() {
            $http({
                    method: 'GET',
                    url: '/api/v1/sales_report_details',
                    params: {
                        start_date: $state.params.start_date,
                        end_date: $state.params.end_date,
                        filter: "chart"
                    }
                })
                .success(function(response) {
                    sales_detailed_reports.loading = false;
                    sales_detailed_reports.report_details = response.data;
                });
        };
        sales_detailed_reports.init();
        /**
         * @ngdoc method
         * @name SalesDetailedReportCtrl.sales_detailed_reports.refreshFilters
         * @methodOf module.SalesDetailedReportCtrl
         * @description
         * This is a callback function on Apply filtering button action
         */
        sales_detailed_reports.refreshFilters = function(filterParams) {
            $http({
                    method: 'GET',
                    url: '/api/v1/sales_report_details',
                    params: filterParams
                })
                .success(function(response) {
                    sales_detailed_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        sales_detailed_reports.report_details = response.data;
                    } else {
                        sales_detailed_reports.report_details = [];
                    }
                });
        };
    }]);