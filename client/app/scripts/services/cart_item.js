'use strict';
/**
 * @ngdoc service
 * @name tixmall.CartItem
 * @description
 * # CartItem
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('CartItem', ['$rootScope', '$log', function($rootScope, $log) {
        var item = function(id, name, price, quantity, data) {
            this.setId(id);
            this.setName(name);
            this.setPrice(price);
            this.setQuantity(quantity);
            this.setData(data);
        };
        item.prototype.setId = function(id) {
            if (id) {
                this._id = id;
            } else {
                $log.error('An ID must be provided');
            }
        };
        item.prototype.getId = function() {
            return this._id;
        };
        item.prototype.setName = function(name) {
            if (name) {
                this._name = name;
            } else {
                $log.error('A name must be provided');
            }
        };
        item.prototype.getName = function() {
            return this._name;
        };
        item.prototype.setPrice = function(price) {
            var priceFloat = parseFloat(price);
            if (priceFloat) {
                if (priceFloat <= 0) {
                    $log.error('A price must be over 0');
                } else {
                    this._price = (priceFloat);
                }
            } else {
                $log.error('A price must be provided');
            }
        };
        item.prototype.getPrice = function() {
            return this._price;
        };
        item.prototype.setQuantity = function(quantity, relative) {
            var quantityInt = parseInt(quantity);
            if (quantityInt % 1 === 0) {
                if (relative === true) {
                    this._quantity += quantityInt;
                } else {
                    this._quantity = quantityInt;
                }
                if (this._quantity < 1) {
                    this._quantity = 1;
                }
            } else {
                this._quantity = 1;
                $log.info('Quantity must be an integer and was defaulted to 1');
            }
        };
        item.prototype.getQuantity = function() {
            return this._quantity;
        };
        item.prototype.setData = function(data) {
            if (data) {
                this._data = data;
            }
        };
        item.prototype.getData = function() {
            if (this._data) {
                return this._data;
            } else {
                $log.info('This item has no data');
            }
        };
        item.prototype.getTotal = function() {
            return +parseFloat(this.getQuantity() * this.getPrice())
                .toFixed(2);
        };
        item.prototype.toObject = function() {
            return {
                id: this.getId(),
                name: this.getName(),
                price: this.getPrice(),
                quantity: this.getQuantity(),
                data: this.getData(),
                total: this.getTotal()
            };
        };
        return item;
    }]);