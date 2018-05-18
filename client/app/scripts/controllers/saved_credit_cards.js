'use strict';
/**
 * @ngdoc SecureDeliveryController
 * @name tixmall.controller:SecureDeliveryController
 * @description
 * # SecureDeliveryController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('SavedCardsController', ['creditcard', 'creditcardDelete', 'flash', '$filter', function(creditcard, creditcardDelete, flash, $filter) {
        // used controller as syntax, assigned current scope to variable savedcards
        var savedcards = this;
        savedcards.details = [];
        var flashMessage;
        /**
         * @ngdoc method
         * @name SavedCardsController.savedcards.init
         * @methodOf module.SavedCardsController
         * @description
         * This method will gets list of available saved credit cards based on current logged in user.
         *
         */
        savedcards.init = function() {
            creditcard.get()
                .$promise.then(function(response) {
                    savedcards.details = response.data;
                });
        };
        savedcards.init();
        /*jshint -W117 */
        /**
         * @ngdoc method
         * @name SavedCardsController.savedcards.deleteCard
         * @methodOf module.SavedCardsController
         * @description
         * This method will delete single card detail from available cards based on id.
         *
         */
        savedcards.deleteCard = function(id) {
            creditcardDelete.delete({
                id: id
            }, function(response) {
                if (angular.isDefined(response.error) && parseInt(response.error.code) === 0) {
                    $('#card_container')
                        .find('#card_details_' + id)
                        .remove();
                    flashMessage = $filter("translate")("Card detail deleted successfully.");
                    flash.set(flashMessage, "success", false);
                } else {
                    flashMessage = $filter("translate")("Could not able to delete. Please try again later");
                    flash.set(flashMessage, "success", false);
                }
            });
        };
    }]);