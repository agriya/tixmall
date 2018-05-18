'use strict';
/**
 * @ngdoc directive
 * @name tixmall.directive:myModal
 * @scope
 * @restrict EA
 *
 * @description
 * Its a directive we can give dynamic content to the model
 * It makes angular ui modal to reuse everywhere
 *
 * @param {name, size, scope, body, bodyClass}  field   
 * name field - its a modal header content 
 * size field - modal size (sm,md,lg)
 * scope field - isolated scope
 * bodt filed - body content, it will replaces modal content by using transclude true.
 * bodyClass field - its a classname to style the body content
 */
function StpaModal($uibModal) {
    //jshint unused:false
    /*jshint -W117 */
    return {
        transclude: true,
        restrict: 'EA',
        template: '<a ng-click="open()" ng-transclude class="cursor-pointer text-decoration-none"></a>',
        scope: {
            //controller: "@",
            //controllerAs: "@",
            name: "@",
            size: "@",
            scope: "=scope",
            body: "@",
            bodyClass: "@"
        },
        link: function(scope, element, attrs) {
            scope.open = function() {
                var modalInstance = $uibModal.open({
                    templateUrl: attrs.template ? attrs.template : false,
                    template: !attrs.template ? function() {
                        var html = '';
                        html += '<div class="modal-header">';
                        html += '<button aria-label="Close"  ng-click="StpaModalCtrl.cancel($event)"  class="close" type="button"><span aria-hidden="true">×</span></button>';
                        html += '<h3 ><strong>' + attrs.name && attrs.name !== '' ? attrs.name : 'Angular My Modal' + '</strong></h3>';
                        html += '</div>';
                        html += '<div class="modal-body ' + attrs.bodyClass + '">';
                        html += attrs.body;
                        html += '</div>';
                        return html;
                    } : false,
                    controller: 'StpaModalCtrl',
                    controllerAs: 'StpaModalCtrl',
                    size: attrs.size ? attrs.size : 'sm', //lg - sm - md
                    windowClass: attrs.windowClass ? attrs.windowClass : 'angular-my-modal-window',
                    backdrop: attrs.backdrop ? attrs.backdrop : true,
                    resolve: {
                        modalSetting: function() {
                            return {
                                name: scope.name ? scope.name : 'Angular My Modal'
                            };
                        },
                        modalScope: function() {
                            return scope.scope ? scope.scope : {};
                        }
                    }
                });
                modalInstance.result.then(function() {
                    //console.debug('success');
                }, function() {
                    //console.debug('error');
                });
            };
        }
    };
}
//directive controller
function StpaModalCtrl($scope, $rootScope, $uibModalInstance, modalSetting, modalScope) {
    //jshint unused:false
    var that = this;
    that.setting = modalSetting;
    that.scope = modalScope;
    that.accept = function(e) {
        $uibModalInstance.close();
        $rootScope.$emit('StpaModalAccepted', e);
        if (e) {
            e.stopPropagation();
        }
    };
    that.cancel = function(e) {
        $uibModalInstance.dismiss('cancel');
        $rootScope.$emit('StpaModalCanceled', e);
        if (e) {
            e.stopPropagation();
        }
    };
    $rootScope.$on('StpaModalAccept', function() {
        that.accept();
    });
    $rootScope.$on('StpaModalCancel', function() {
        that.cancel();
    });
}
angular.module('tixmall')
    .directive('myModal', StpaModal);
angular.module('tixmall')
    .controller('StpaModalCtrl', StpaModalCtrl);