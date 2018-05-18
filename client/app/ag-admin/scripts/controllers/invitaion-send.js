//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:InvitationSendCtrl
 * @description
 * # InvitationSendCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('InvitationSendCtrl', ['$scope', '$http', 'notification', function($scope, $http, notification) {
        var invitation_send = this;
        $scope.send_to_id = [];
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
        /**
         * @ngdoc method
         * @name reportsFilter.getallLists
         * @methodOf module.reportsFilter
         * @description
         * This method will get sales report details when controller initiated
         */
        $scope.getAllLists = function() {
            $http({
                    method: 'GET',
                    url: '/api/v1/lists',
                })
                .success(function(response) {
                    if (angular.isDefined(response.data) && response.data !== '') {
                        $scope.getAllLists = response.data;
                    }
                });
        };
        /**
         * @ngdoc method
         * @name reportsFilter.getallGuests
         * @methodOf module.reportsFilter
         * @description
         * This method will get sales report details when controller initiated
         */
        $scope.getAllGuests = function() {
            $http({
                    method: 'GET',
                    url: '/api/v1/guests',
                })
                .success(function(response) {
                    if (angular.isDefined(response.data) && response.data !== '') {
                        $scope.getAllGuests = response.data;
                    }
                });
        };
        $scope.index = function() {
            $scope.getAllEvents();
            $scope.getAllPriceTypes();
            $scope.getAllLists();
            $scope.getAllGuests();
        };
        /**
         * @ngdoc method
         * @name reportsFilter.sendInvitation
         * @methodOf module.sendInvitation
         * @description
         * This method will get sales report details when controller initiated
         */
        $scope.sendInvitation = function($valid) {
            if ($valid) {
                $scope.invitationForm.$setPristine();
                $scope.invitationForm.$setUntouched();
                if ($scope.invite.is_send_to_list === 1) {
                    $scope.send_to_id.push({
                        "id": $scope.invite.list_id
                    });
                } else {
                    $scope.send_to_id.push({
                        "id": $scope.invite.guest_id
                    });
                }
                $scope.invite = {
                    send_to_id: $scope.send_to_id,
                    event_id: $scope.invite.event.id,
                    event_schedule_id: $scope.invite.event_schedule_id,
                    is_send_to_list: parseInt($scope.invite.is_send_to_list),
                    price_type_id: $scope.invite.price_type_id,
                };
                $http.post('/api/v1/send_invitation', $scope.invite)
                    .success(function(response) {
                        $scope.invite = {};
                        notification.log("Invitation Send Successfully", {
                            addnCls: 'humane-flatty-success'
                        });
                    });
            }
        };
        $scope.index();
    }]);