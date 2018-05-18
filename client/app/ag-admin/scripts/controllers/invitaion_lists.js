//'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:InvitationListCtrl
 * @description
 * # InvitationListCtrl
 * Controller of the tixmall
 */
angular.module('tixmallAdmin')
    .controller('InvitationListCtrl', ['$http', function($http) {
        var invitation_lists = this;
        invitation_lists.loading = true;
        invitation_lists.getList = function() {
            $http({
                    method: 'GET',
                    url: '/api/v1/lists',
                })
                .success(function(response) {
                    invitation_lists.loading = false;
                    if (angular.isDefined(response.data)) {
                        invitation_lists.invitation_details = response.data;
                    }
                });
        };
    }]);