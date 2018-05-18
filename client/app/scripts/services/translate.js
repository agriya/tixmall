'use strict';
/**
 * @ngdoc service
 * @name tixmall.LocaleService
 * @description
 * # Set Languages for language switching option
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .service('LocaleService', function($translate, $rootScope, tmhDynamicLocale, languages) {
        var localesObj = {};
        var _LOCALES_DISPLAY_NAMES = [];
        var _LOCALES;
        var availableLanguages = [];
        var params = {
            is_active: true
        };
        // gettings language lists for translate
        languages.get(params)
            .$promise.then(function(response) {
                angular.forEach(response.data, function(data) {
                    availableLanguages[data.iso2] = data.name;
                });
                localesObj.locales = availableLanguages;
                localesObj = localesObj.locales;
                _LOCALES = Object.keys(localesObj);
                if (!_LOCALES || _LOCALES.length === 0) {
                    console.error('There are no _LOCALES provided');
                }
                _LOCALES.forEach(function(locale) {
                    _LOCALES_DISPLAY_NAMES.push(localesObj[locale]);
                });
            });
        // STORING CURRENT LOCALE
        // var currentLocale = $translate.proposedLanguage(); // because of async loading - its some times returns browser language
        var currentLocale = $translate.use() || $translate.storage()
            .get($translate.storageKey()) || $translate.preferredLanguage(); // because of async loading
        // METHODS
        var checkLocaleIsValid = function(locale) {
            return _LOCALES.indexOf(locale) !== -1;
        };
        var setLocale = function(locale) {
            if (!checkLocaleIsValid(locale)) {
                console.error('Locale name "' + locale + '" is invalid');
                return;
            }
            currentLocale = locale; // updating current locale
            // asking angular-translate to load and apply proper translations
            $translate.use(locale);
        };
        // EVENTS
        // on successful applying translations by angular-translate
        $rootScope.$on('$translateChangeSuccess', function(event, data) {
            document.documentElement.setAttribute('lang', data.language); // sets "lang" attribute to html
            $rootScope.$emit('changeLanguage', {
                currentLocale: data.language,
            });
            // asking angular-dynamic-locale to load and apply proper AngularJS $locale setting
            tmhDynamicLocale.set(data.language.toLowerCase()
                .replace(/_/g, '-'));
        });
        return {
            getLocaleDisplayName: function() {
                return localesObj[currentLocale];
            },
            setLocaleByDisplayName: function(localeDisplayName) {
                setLocale(_LOCALES[_LOCALES_DISPLAY_NAMES.indexOf(localeDisplayName) // get locale index
                    ]);
            },
            getLocalesDisplayNames: function() {
                return _LOCALES_DISPLAY_NAMES;
            }
        };
    });