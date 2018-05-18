//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:CapacityReportCtrl
 * @description
 * # CapacityReportCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('CapacityReportCtrl', ['$http', function($http) {
        var capacity_reports = this;
        capacity_reports.loading = true;
        capacity_reports.report_details = [];
        /**
         * @ngdoc method
         * @name CapacityReportCtrl.capacity_reports.refreshFilters
         * @methodOf module.CapacityReportCtrl
         * @description
         * This is a callback function on Apply filtering button action
         */
        capacity_reports.refreshFilters = function(filterParams) {
            $http({
                    method: 'GET',
                    url: '/api/v1/capacity_reports',
                    params: filterParams
                })
                .success(function(response) {
                    capacity_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        capacity_reports.report_details = response.data;
                    } else {
                        capacity_reports.report_details = [];
                    }
                });
        };
    }]);