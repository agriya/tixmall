'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:MyOrdersController
 * @description
 * # MyOrdersController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('MyOrdersController', ['getorders', '$window', '$cookies', function(getorders, $window, $cookies) {
            // used controller as syntax, assigned current scope to variable myorders
            var myorders = this;
            myorders.details = [];
            var auth = JSON.parse($cookies.get('auth'));
            /**
             * @ngdoc method
             * @name MyOrdersController.myorders.init
             * @methodOf module.MyOrdersController
             * @description
             * This method loads orders details for currently logged in user
             *
             */
            myorders.init = function() {
                getorders.get({
                        user_id: auth.id,
                        limit: 'all'
                    })
                    .$promise.then(function(response) {
                        myorders.details = response.data;
                    });
            };
            myorders.init();
     }
]);