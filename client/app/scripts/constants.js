'use strict';
/**
 * @ngdoc directive
 * @name tixmall.directive:languages
 * @description
 * # languages
 */
angular.module('tixmall')
    .constant('GeneralConfig', {
        'preferredlanguage': 'en',
        'phoneNumberPattern': /^\(?(\d{3})\)?[ .-]?(\d{3})[ .-]?(\d{4})$/,
        'userNamePattern': /^[A-Za-z_-][A-Za-z0-9_-]*$/,
        'Number': /^(0|[1-9][0-9]*)$/,
        'fraction': 2
    });