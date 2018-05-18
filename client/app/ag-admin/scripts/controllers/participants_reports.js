//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:ParticipantReportCtrl
 * @description
 * # ParticipantReportCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('ParticipantReportCtrl', ['$http', function($http) {
        var participant_reports = this;
        participant_reports.loading = true;
        participant_reports.report_details = [];
        /**
         * @ngdoc method
         * @name ParticipantReportCtrl.participant_reports.refreshFilters
         * @methodOf module.ParticipantReportCtrl
         * @description
         * This is a callback function on Apply filtering button action
         */
        participant_reports.refreshFilters = function(filterParams) {
            $http({
                    method: 'GET',
                    url: '/api/v1/participant_reports',
                    params: filterParams
                })
                .success(function(response) {
                    participant_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        participant_reports.report_details = response.data;
                    } else {
                        participant_reports.report_details = [];
                    }
                });
        };
    }]);