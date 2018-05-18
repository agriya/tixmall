'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:ChooseTicketController
 * @description
 * # ChooseTicketController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('MultiScheduleController', ['$scope', '$state', 'bookings', function($scope, $state, bookings) {
        /*jshint -W117 */
        var model = this;
        var event_schedule = [];
        bookings.get({
                event_id: $state.params.event_id,
                venue_id: $state.params.venue_id
            })
            .$promise.then(function(response) {
                model.bookings = response.data;
                //Stroing event schedule array to local variable
                event_schedule = model.bookings.EventSchedule;
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.allGroupedDate
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method groups all date with key name 'all'
                 */
                $scope.allGroupedDate = _.groupBy(event_schedule, function() {
                    return "all";
                });
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.grouped
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method groups all data based on date like '2016-09-13'
                 */
                $scope.grouped = _.groupBy(event_schedule, function(item) {
                    return item.start_date.substring(0, 10);
                });
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.groupedByMonthAndYear
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method groups all data based on year and month like '2016-09'
                 */
                $scope.groupedByMonthAndYear = _.groupBy(event_schedule, function(item) {
                    return item.start_date.substring(0, 7);
                });
                $scope.active = 0;
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.changeGroupedByYear
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method will group date based on choosed year and month
                 * Its a ng-change function for year and month dropdown
                 */
                $scope.changeGroupedByYear = function(value) {
                    $scope.scheduled_year = value;
                    $scope.groupedByDate = _.groupBy(event_schedule, function(item) {
                        return (value.substring(0, 7) === item.start_date.substring(0, 7)) ? value : false;
                    });
                    // to remove duplicate values in date and month dropdown
                    $scope.groupedByDate = _.uniq($scope.groupedByDate[value], function(value) {
                        return value.start_date.substring(0, 10);
                    });
                    // initially select date and month
                    $scope.scheduled_date = $scope.groupedByDate[0].start_date;
                    $scope.changeGroupedByDate($scope.scheduled_date);
                    // updaing event schedule id on year change
                    $scope.selectedEventSchedule = _.find(event_schedule, function(item) {
                        return $scope.scheduled_date === item.start_date;
                    });
                };
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.changeGroupedByDate
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method will group time based on choosed year and date&day
                 * Its a ng-change function for date and month dropdown
                 */
                $scope.changeGroupedByDate = function(value) {
                    $scope.scheduled_date = value;
                    $scope.groupedByTime = _.groupBy(event_schedule, function(item) {
                        return (value.substring(0, 10) === item.start_date.substring(0, 10)) ? value : false;
                    });
                    $scope.scheduled_time = $scope.groupedByTime[value][0].start_date;
                    // updaing event schedule id on date and month change
                    $scope.selectedEventSchedule = _.find(event_schedule, function(item) {
                        return $scope.scheduled_time === item.start_date;
                    });
                };
                /**
                 * @ngdoc method
                 * @name ChooseTicketController.changeGroupedByDate
                 * @methodOf module.ChooseTicketController
                 * @description
                 * This method will group time based on choosed year and date and day
                 * Its a ng-change function for date and month dropdown
                 */
                $scope.changeGroupedByTime = function(value) {
                    // updaing event schedule id on time change
                    $scope.selectedEventSchedule = _.find(event_schedule, function(item) {
                        return value === item.start_date;
                    });
                };
                // initially selecting year and month (first item in dropdown)
                var count = 0;
                angular.forEach($scope.groupedByMonthAndYear, function(value) {
                    count++;
                    if (count === 1) {
                        $scope.scheduled_year = value[0].start_date;
                        // initially selecting year and month (first item in dropdown)
                        $scope.changeGroupedByYear($scope.scheduled_year);
                    }
                });
            });
    }]);