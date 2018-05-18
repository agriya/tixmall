//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:DemoGraphicCtrl
 * @description
 * # DemoGraphicCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('DemoGraphicCtrl', ['$http', '$window', '$timeout', function($http, $window, $timeout) {
        var demographics_reports = this;
        demographics_reports.loading = true;
        demographics_reports.report_details = [];
        /**
         * @ngdoc method
         * @name CapacityReportCtrl.init
         * @methodOf module.CapacityReportCtrl
         * @description
         * This method will get occupation, age and daily sales reports details when controller initiated
         */
        demographics_reports.init = function() {
            // getting all occupation details
            $http({
                    method: 'GET',
                    url: '/api/v1/occupations',
                })
                .success(function(response) {
                    if (angular.isDefined(response.data)) {
                        demographics_reports.occupations_details = response.data;
                    }
                });
            // getting all education details
            $http({
                    method: 'GET',
                    url: '/api/v1/educations',
                })
                .success(function(response) {
                    if (angular.isDefined(response.data)) {
                        demographics_reports.educations_details = response.data;
                    }
                });
            // all reports details
            $http({
                    method: 'GET',
                    url: '/api/v1/demographic_reports',
                    params: {
                        filter: "chart"
                    }
                })
                .success(function(response) {
                    demographics_reports.report_details = response.data;
                    demographics_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        if (angular.isDefined(response.data.ages)) {
                            demographics_reports.groupAge(response.data.ages);
                        }
                        if (angular.isDefined(response.data.genders)) {
                            demographics_reports.groupGender(response.data.genders);
                        }
                        if (angular.isDefined(response.data.occupations)) {
                            demographics_reports.groupOccupation(response.data.occupations)
                        }
                        if (angular.isDefined(response.data.educations)) {
                            demographics_reports.groupEducation(response.data.educations)
                        }
                        if (angular.isDefined(response.data.daily_sales)) {
                            demographics_reports.groupDailySales(response.data.daily_sales)
                        }
                        if (angular.isDefined(response.data.hourly_sales)) {
                            demographics_reports.groupHourlySales(response.data.hourly_sales)
                        }
                    }
                    $timeout(function() {
                        demographics_reports.refreshChart();
                    }, 100)
                });
        };
        demographics_reports.init();
        /**
         * @ngdoc method
         * @name DemoGraphicCtrl.demographics_reports.groupDailySales
         * @methodOf module.DemoGraphicCtrl
         * @description
         * This Mthod will group daily sales details by static is range between monday to sunday
         */
        demographics_reports.groupDailySales = function(response) {
            demographics_reports.group_daily_sales = [];
            var daysArray = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            for (var i = 0; i <= daysArray.length - 1; i++) {
                var count = 0;
                angular.forEach(response, function(value) {
                    if (value.day === daysArray[i]) {
                        count = value.sum;
                    }
                });
                demographics_reports.group_daily_sales.push({
                    "day": daysArray[i],
                    "count": count
                });
            }
        };
        /**
         * @ngdoc method
         * @name DemoGraphicCtrl.demographics_reports.groupHourlySales
         * @methodOf module.DemoGraphicCtrl
         * @description
         * This Mthod will group hourly sales details by static is range between 1 to 24
         */
        demographics_reports.groupHourlySales = function(response) {
            demographics_reports.group_hourly_sales = [];
            for (var i = 1; i <= 24; i++) {
                var count = 0;
                angular.forEach(response, function(value) {
                    if (parseInt(value.hour) === i) {
                        count = value.sum;
                    }
                });
                demographics_reports.group_hourly_sales.push({
                    "hour": i,
                    "count": count
                });
            }
        };
        /**
         * @ngdoc method
         * @name DemoGraphicCtrl.demographics_reports.groupAge
         * @methodOf module.DemoGraphicCtrl
         * @description
         * This Mthod will group age details by static is range between 16 to 65
         */
        demographics_reports.groupAge = function(response) {
            demographics_reports.agedeatils = [];
            for (var i = 16; i <= 65; i++) {
                var count = 0;
                angular.forEach(response, function(value) {
                    if (parseInt(value.age) === i) {
                        count = value.count;
                    }
                });
                demographics_reports.agedeatils.push({
                    "age": i,
                    "count": count
                });
            }
        };
        /**
         * @ngdoc method
         * @name DemoGraphicCtrl.demographics_reports.groupGender
         * @methodOf module.DemoGraphicCtrl
         * @description
         * This Mthod will group gender details by static is range between 1 to 2
         */
        demographics_reports.groupGender = function(response) {
            demographics_reports.genderdeatils = [];
            for (var i = 1; i <= 2; i++) {
                var count = 0;
                angular.forEach(response, function(value) {
                    if (parseInt(value.gender_id) === i) {
                        count = value.count;
                    }
                });
                demographics_reports.genderdeatils.push({
                    "gender_id": i,
                    "count": count
                });
            }
        };
        /**
         * @ngdoc method
         * @name DemoGraphicCtrl.demographics_reports.groupOccupation
         * @methodOf module.DemoGraphicCtrl
         * @description
         * This Mthod will group gender details by static is range between 1 to 2
         */
        demographics_reports.groupOccupation = function(response) {
            demographics_reports.customized_occupation_details = [];
            angular.forEach(demographics_reports.occupations_details, function(total_occupation_value) {
                var occupation_count = 0;
                angular.forEach(response, function(report_occupation_value) {
                    if (parseInt(report_occupation_value.occupation_id) === total_occupation_value.id) {
                        occupation_count = report_occupation_value.count;
                    }
                });
                demographics_reports.customized_occupation_details.push({
                    "occupation_id": total_occupation_value.id,
                    "count": occupation_count,
                    "occupation": total_occupation_value.name
                });
            })
        };
        /**
         * @ngdoc method
         * @name DemoGraphicCtrl.demographics_reports.groupEducation
         * @methodOf module.DemoGraphicCtrl
         * @description
         * This Mthod will group gender details by static is range between 1 to 2
         */
        demographics_reports.groupEducation = function(response) {
            demographics_reports.customized_education_details = [];
            angular.forEach(demographics_reports.educations_details, function(total_education_value) {
                var education_count = 0;
                angular.forEach(response, function(report_education_value) {
                    if (parseInt(report_education_value.education_id) === total_education_value.id) {
                        education_count = report_education_value.count;
                    }
                });
                demographics_reports.customized_education_details.push({
                    "education_id": total_education_value.id,
                    "count": education_count,
                    "education": total_education_value.name
                });
            })
        };
        /**
         * @ngdoc method
         * @name DemoGraphicCtrl.demographics_reports.refreshFilters
         * @methodOf module.DemoGraphicCtrl
         * @description
         * This is a callback function on Apply filtering button action
         */
        demographics_reports.refreshFilters = function(filterParams) {
            $http({
                    method: 'GET',
                    url: '/api/v1/demographic_reports',
                    params: filterParams
                })
                .success(function(response) {
                    demographics_reports.loading = false;
                    if (angular.isDefined(response.data)) {
                        demographics_reports.report_details = response.data;
                        if (angular.isDefined(response.data.ages)) {
                            demographics_reports.groupAge(response.data.ages);
                        }
                        if (angular.isDefined(response.data.genders)) {
                            demographics_reports.groupGender(response.data.genders);
                        }
                        if (angular.isDefined(response.data.occupations)) {
                            demographics_reports.groupOccupation(response.data.occupations)
                        }
                        if (angular.isDefined(response.data.educations)) {
                            demographics_reports.groupEducation(response.data.educations)
                        }
                        if (angular.isDefined(response.data.daily_sales)) {
                            demographics_reports.groupDailySales(response.data.daily_sales)
                        }
                        if (angular.isDefined(response.data.hourly_sales)) {
                            demographics_reports.groupHourlySales(response.data.hourly_sales)
                        }
                    } else {
                        demographics_reports.report_details = [];
                    }
                    $timeout(function() {
                        demographics_reports.refreshChart();
                    }, 100);
                });
        };
        /**
         * @ngdoc method
         * @name DemoGraphicCtrl.demographics_reports.refreshChart
         * @methodOf module.DemoGraphicCtrl
         * @description
         * This Method will update chart based on filter data
         */
        demographics_reports.refreshChart = function() {
            angular.element('document')
                .ready(function() {
                    angular.element('.gvChart')
                        .remove();
                    googleLoaded.done(function() {
                        angular.element("#ageReport")
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
                        angular.element("#dayReport")
                            .gvChart({
                                chartType: 'ColumnChart',
                                gvSettings: {
                                    width: '100%',
                                    height: 410,
                                    fontName: 'Open Sans, sans-serif',
                                    fontSize: 13,
                                    pointSize: 8,
                                    titlePosition: "none",
                                    legend: {
                                        position: 'bottom'
                                    },
                                    vAxis: {
                                        title: 'Quantities',
                                        format: '#,###',
                                        gridlines: {
                                            color: "#f1f1f1"
                                        }
                                    },
                                    hAxis: {
                                        title: 'Days'
                                    },
                                    chartArea: {
                                        left: 60,
                                        top: 10,
                                        width: "93%",
                                        height: "75%"
                                    }
                                }
                            });
                        angular.element("#genderReport")
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
                        angular.element("#occupationReport")
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
                        angular.element("#educationReport")
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
                        angular.element("#hourReport")
                            .gvChart({
                                chartType: 'AreaChart',
                                gvSettings: {
                                    width: '100%',
                                    height: 410,
                                    fontName: 'Open Sans, sans-serif',
                                    fontSize: 13,
                                    titlePosition: "none",
                                    areaOpacity: 0.2,
                                    pointSize: 8,
                                    lineWidth: 2,
                                    legend: {
                                        position: 'bottom'
                                    },
                                    vAxis: {
                                        title: 'Quantities',
                                        format: '#,###',
                                        gridlines: {
                                            color: "#f1f1f1"
                                        }
                                    },
                                    hAxis: {
                                        title: 'Hours'
                                    },
                                    chartArea: {
                                        left: 60,
                                        top: 10,
                                        width: "93%",
                                        height: "75%"
                                    }
                                }
                            });
                    });
                });
        };
        // this will make charts responsive on screen size changes
        angular.element($window)
            .bind('resize', function() {
                demographics_reports.refreshChart();
            });
    }]);