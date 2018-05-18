'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:TimerCtrl
 * @description
 * # TimerCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('TimerCtrl', [function() {
        /*jshint -W117 */
        $("#timer-affix")
            .affix({
                offset: {
                    top: angular.element('#header')
                        .height()
                }
            });
  }]);