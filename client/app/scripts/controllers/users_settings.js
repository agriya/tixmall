'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:UsersSettingsCtrl
 * @description
 * # UsersSettingsCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('UsersSettingsCtrl', ['$rootScope', '$scope', 'userSettings', 'occupations', 'educations', 'flash', '$filter', function($rootScope, $scope, userSettings, occupations, educations, flash, $filter) {
        // used controller as syntax, assigned current scope to variable model
        var model = this;
        model.user = {};
        $rootScope.header = $rootScope.settings.SITE_NAME + ' | ' + $filter("translate")("Users Settings");
        $scope.save_btn = false;
        $scope.titles = [{
            "name": "Mr"
        }, {
            "name": "Mrs"
        }, {
            "name": "Miss"
        }];
        // gogole location auto complete initiation
        var inputFrom = document.getElementById('goo-place');
        /*jshint -W117 */
        var autocompleteFrom = new google.maps.places.Autocomplete(inputFrom); //jslint vars:false       
        google.maps.event.addListener(autocompleteFrom, 'place_changed', function() { //jslint vars:false
            var place = autocompleteFrom.getPlace();
            model.user.latitude = place.geometry.location.lat();
            model.user.longitude = place.geometry.location.lng();
            var k = 0;
            angular.forEach(place.address_components, function(value, key) {
                //jshint unused:false
                if (value.types[0] === 'locality' || value.types[0] === 'administrative_area_level_2') {
                    if (k === 0) {
                        model.user.city_name = value.long_name;
                    }
                    if (value.types[0] === 'locality') {
                        k = 1;
                    }
                }
                if (value.types[0] === 'administrative_area_level_1') {
                    model.user.state_name = value.long_name;
                }
                if (value.types[0] === 'sublocality_level_1') {
                    model.user.address = value.long_name;
                }
                if (value.types[0] === 'country') {
                    model.user.country_iso2 = value.short_name;
                }
                if (value.types[0] === 'postal_code') {
                    model.user.zip_code = parseInt(value.long_name);
                }
            });
        });
        /**
         * @ngdoc method
         * @name UsersSettingsCtrl.save
         * @methodOf module.UsersSettingsCtrl
         * @description
         * This method updates user information
         */
        $scope.save = function(mydetails) {
            if (mydetails.$valid) {
                var flashMessage;
                $scope.save_btn = true;
                model.user.id = $rootScope.user.id;
                userSettings.update(model.user, function(response) {
                    $scope.response = response;
                    if ($scope.response.error.code === 0) {
                        flashMessage = $filter("translate")("Data saved successfully.");
                        flash.set(flashMessage, 'success', false);
                    } else {
                        if (angular.isDefined(response.error.fields) && angular.isDefined(response.error.fields.email)) {
                            flashMessage = $filter("translate")("Already this email exists.");
                        } else if (angular.isDefined(response.error.fields) && angular.isDefined(response.error.fields.mobile)) {
                            flashMessage = $filter("translate")("Already this mobile number exists.");
                        } else {
                            flashMessage = $filter("translate")("User could not be updated. Please, try again later.");
                        }
                        flashMessage = $filter("translate")(response.error.message);
                        flash.set(flashMessage, 'error', false);
                    }
                    $scope.save_btn = false;
                });
            }
        };
        /**
         * @ngdoc method
         * @name UsersSettingsCtrl.index
         * @methodOf module.UsersSettingsCtrl
         * @description
         * This method refills user information initally
         */
        $scope.index = function() {
            var params = {};
            params.id = $rootScope.user.id;
            userSettings.get(params, function(response) {
                model.user.title = response.data.title;
                model.user.first_name = response.data.first_name;
                model.user.last_name = response.data.last_name;
                model.user.email = response.data.email;
                model.user.phone_code = response.data.phone_code;
                model.user.phone = response.data.phone;
                model.user.residence_country_id = response.data.residence_country_id;
                model.user.address = response.data.address;
                model.user.address1 = response.data.address1;
                model.user.mobile = parseInt(response.data.mobile);
                model.user.occupation_id = response.data.occupation_id;
                model.user.education_id = response.data.education_id;
            });
            occupations.get()
                .$promise.then(function(response) {
                    $scope.occupations = response.data;
                });
            educations.get()
                .$promise.then(function(response) {
                    $scope.educations = response.data;
                });
        };
        /**
         * @ngdoc method
         * @name UsersSettingsCtrl.init
         * @methodOf module.UsersSettingsCtrl
         * @description
         * This method calls index function
         */
        $scope.init = function() {
            $scope.index();
        };
        $scope.init();
    }]);