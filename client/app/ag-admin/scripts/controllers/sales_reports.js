//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:SalesReportCtrl
 * @description
 * # SalesReportCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('SalesReportCtrl', ['$http', function($http) {
        var sales_reports = this;
        sales_reports.loading = true;
        sales_reports.report_details = [];
        /**
         * @ngdoc method
         * @name CapacityReportCtrl.init
         * @methodOf module.CapacityReportCtrl
         * @description
         * This method will get sales report details when controller initiated
         */
        sales_reports.init = function() {
            $http({
                    method: 'GET',
                    url: '/api/v1/sales_reports',
                    params: {
                        filter: "chart"
                    }
                })
                .success(function(response) {
                    sales_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        sales_reports.report_details = response.data;
                        sales_reports.total_details = response.total_details;
                    }
                });
        };
        sales_reports.init();
        /**
         * @ngdoc method
         * @name CapacityReportCtrl.init
         * @methodOf module.CapacityReportCtrl
         * @description
         * This is a callback function on Apply filtering button action
         */
        sales_reports.refreshFilters = function(filterParams) {
            $http({
                    method: 'GET',
                    url: '/api/v1/sales_reports',
                    params: filterParams
                })
                .success(function(response) {
                    sales_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        sales_reports.report_details = response.data;
                        sales_reports.total_details = response.total_details;
                    } else {
                        sales_reports.report_details = [];
                    }
                });
        };
    }]);