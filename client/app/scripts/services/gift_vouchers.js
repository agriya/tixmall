'use strict';
/**
 * @ngdoc service
 * @name tixmall.giftvoucher
 * @description
 * # cities
 * Factory in the giftvoucher.
 */
angular.module('tixmall')
    .factory('giftVoucher', ['$resource', function($resource) {
        return $resource('/api/v1/gift_vouchers', {}, {
            get: {
                method: 'GET'
            },
            create: {
                method: 'POST'
            }
        });
  }])
    .factory('giftVoucherCheck', ['$resource', function($resource) {
        return $resource('/api/v1/gift_vouchers/:coupon_code', {}, {
            get: {
                method: 'GET',
                params: {
                    coupon_code: '@coupon_code'
                }
            }
        });
  }])
    .factory('giftVoucherDelete', ['$resource', function($resource) {
        return $resource('/api/v1/gift_vouchers/:id', {}, {
            delete: {
                method: 'DELETE',
                params: {
                    id: '@id'
                }
            }
        });
  }])
    .factory('giftVoucherById', ['$resource', function($resource) {
        return $resource('/api/v1/gift_vouchers/:id', {}, {
            get: {
                method: 'GET',
                params: {
                    id: '@id'
                }
            }
        });
  }]);