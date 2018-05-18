'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:EventController
 * @description
 * # EventController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('EventController', ['eventView', '$state', 'md5', '$scope', '$timeout', function(eventView, $state, md5, $scope, $timeout) {
        // used controller as syntax, assigned current scope to variable model
        var model = this;
        var hash;
        $scope.event_image_loaded = false;
        model.eventDetails = [];
        /**
         * @ngdoc method
         * @name model.init
         * @methodOf module.EventController
         * @description
         * This method fetches current event details.
         *
         */
        model.init = function() {
            eventView.get({
                    id: $state.params.id,
                    type: "view"
                })
                .$promise.then(function(response) {
                    model.eventDetails = response.data;
                    if (angular.isDefined(response.data.attachments) && response.data.attachments !== null) {
                        hash = md5.createHash('Event' + response.data.id + 'png' + 'large_thumb');
                        model.eventDetails.image_name = '/images/large_thumb/Event/' + response.data.id + '.' + hash + '.png';
                        // For normal thumb
                        var normal_thumb_hash = md5.createHash('Event' + response.data.id + 'png' + 'normal_thumb');
                        model.eventDetails.normal_image_name = '/images/normal_thumb/Event/' + response.data.id + '.' + normal_thumb_hash + '.png';
                    }
                    if (angular.isDefined(response.data.attachment_floor_plan) && response.data.attachment_floor_plan !== null) {
                        hash = md5.createHash('EventFloorPlan' + response.data.id + 'png' + 'original');
                        model.eventDetails.floor_plan_image_name = '/images/original/EventFloorPlan/' + response.data.id + '.' + hash + '.png';
                    }
                    if (angular.isDefined(response.data.attachment_ticket_price) && response.data.attachment_ticket_price !== null) {
                        hash = md5.createHash('TicketPrices' + response.data.id + 'png' + 'original');
                        model.eventDetails.ticket_prices_image_name = '/images/original/TicketPrices/' + response.data.id + '.' + hash + '.png';
                    }
                    if (angular.isDefined(response.data.video) && response.data.video !== null) {
                        model.eventDetails.video_name = '/images/' + response.data.video.dir + '/' + response.data.video.filename;
                    }
                    if (model.eventDetails.min_event_price === null) {
                        model.eventDetails.min_event_price = 0;
                    }
                    $timeout(function() {
                        $scope.event_image_loaded = true;
                    }, 100);
                });
        };
        /**
         * Play and Pause video
         */
        model.playorpause = function() {
            var video = angular.element('video');
            if (video.get(0)
                .paused) {
                video.get(0)
                    .play();
            } else {
                video.get(0)
                    .pause();
            }
        };
        model.init();
    }]);