'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('FooterCtrl', ['$scope', 'newsletters', '$filter', 'flash', function($scope, Newsletters, $filter, flash) {
        var model = this;
        var flashMessage;
        model.newsletters = new Newsletters();
        model.date = new Date();
        model.year = model.date.getFullYear();
        /**
         * @ngdoc method
         * @name FooterCtrl.model.newsletterJoin
         * @methodOf module.FooterCtrl
         * @description
         * Newsletter post function
         *
         * @param {} 
         * @returns {}
         */
        model.newsletterJoin = function newsletterJoin(newsletters_form) {
            if (newsletters_form.email.$valid && model.newsletters.email !== '' && model.newsletters.email !== undefined) {
                Newsletters.save(model.newsletters, function(response) {
                    if (angular.isDefined(response.error.code) && parseInt(response.error.code) === 0) {
                        flashMessage = $filter("translate")("You've Successfully Signed up for Tixmall Newsletter!");
                        flash.set(flashMessage, 'success', false);
                        model.newsletters.email = '';
                    }
                });
            } else {
                flashMessage = $filter("translate")("Not valid email!");
                flash.set(flashMessage, 'error', false);
            }
        };
  }]);