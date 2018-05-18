'use strict';
/**
 * @ngdoc service
 * @name tixmall.usersProfile
 * @description
 * # usersProfile
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('usersProfile', function() {
        // Service logic
        // ...
        var meaningOfLife = 42;
        // Public API here
        return {
            someMethod: function() {
                return meaningOfLife;
            }
        };
    });