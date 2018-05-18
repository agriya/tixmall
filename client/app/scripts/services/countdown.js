'use strict';
/**
 * @ngdoc service
 * @name tixmall.CountDown
 * @description
 * # CountDown
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .service('CountDown', ['$interval', '$rootScope', '$window', 'Cart', '$state', function($interval, $rootScope, $window, Cart, $state) {
        var countdown = null;
        var timerduration = null;
        var timerSeconds = null;
        var timerMinutes = null;
        $rootScope.timerStarted = false;
        this.startTimer = function(duration) {
            $rootScope.timerStarted = true;
            var timer = duration,
                minutes, seconds;
            countdown = $interval(function() {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                $rootScope.timerval = minutes + ":" + seconds;
                timerSeconds = seconds;
                timerMinutes = (minutes === "0") ? "1" : minutes;
                timerduration = (parseInt(minutes) * 60) + parseInt(timerSeconds);
                $window.localStorage.setItem("timer", timerduration);
                if (--timer < 0) {
                    Cart.empty("clear");
                    timer = null;
                    $rootScope.timerStarted = false;
                    if (angular.isDefined(countdown)) {
                        $window.localStorage.setItem("timer", 0);
                        $interval.cancel(countdown);
                    }
                    $state.go('booking_basket');
                }
            }, 1000);
        };
        this.stopTimer = function() {
            $rootScope.timerStarted = false;
            //Cancel the Timer.
            if (angular.isDefined(countdown)) {
                $window.localStorage.setItem("timer", 0);
                $interval.cancel(countdown);
            }
        };
    }]);