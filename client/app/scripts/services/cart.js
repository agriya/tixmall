'use strict';
/**
 * @ngdoc service
 * @name tixmall.carts
 * @description
 * # carts
 * Factory in the tixmall.
 */
angular.module('tixmall')
    .factory('carts', ['$resource', function($resource) {
        return $resource('/api/v1/carts/:session_id', {}, {
            get: {
                method: 'GET',
                params: {
                    session_id: '@session_id'
                }
            },
            addtocart: {
                method: 'POST'
            }
        });
  }])
    .factory('updatecart', ['$resource', function($resource) {
        return $resource('/api/v1/carts/:session_id', {}, {
            update: {
                method: 'PUT',
                params: {
                    session_id: '@session_id'
                }
            }
        });
  }])
    .factory('deletecart', ['$resource', function($resource) {
        return $resource('/api/v1/carts/:session_id', {}, {
            remove: {
                method: 'DELETE',
                params: {
                    cartId: '@cartId',
                    session_id: '@session_id'
                }
            }
        });
  }])
    .factory('DonationAmt', ['$resource', function($resource) {
        return $resource('/api/v1/donation_amounts', {}, {
            get: {
                method: 'GET'
            }
        });
  }])
    .service('Cart', ['$rootScope', '$window', 'CartItem', 'store', 'deletecart', '$injector', function($rootScope, $window, CartItem, store, deletecart, $injector) {
        this.init = function() {
            this.$cart = {
                shipping: null,
                taxRate: null,
                tax: null,
                donationAmount: 0,
                deliveryMethod: null,
                sessionId: null,
                items: []
            };
        };
        this.addItem = function(id, name, price, quantity, data) {
            var inCart = this.getItemById(id);
            if (typeof inCart === 'object') {
                //Update quantity of an item if it's already in the cart
                inCart.setQuantity(quantity, false);
                $rootScope.$broadcast('Cart:itemUpdated', inCart);
            } else {
                var newItem = new CartItem(id, name, price, quantity, data);
                this.$cart.items.push(newItem);
                $rootScope.$broadcast('Cart:itemAdded', newItem);
            }
            $rootScope.$broadcast('Cart:change', {});
        };
        this.getItemById = function(itemId) {
            var items = this.getCart()
                .items;
            var build = false;
            angular.forEach(items, function(item) {
                if (item.getId() === itemId) {
                    build = item;
                }
            });
            return build;
        };
        this.setShipping = function(shipping) {
            this.$cart.shipping = shipping;
            return this.getShipping();
        };
        this.getShipping = function() {
            if (this.getCart()
                .items.length === 0) {
                return 0;
            }
            return this.getCart()
                .shipping;
        };
        this.setTaxRate = function(taxRate) {
            this.$cart.taxRate = +parseFloat(taxRate)
                .toFixed(2);
            return this.getTaxRate();
        };
        this.getTaxRate = function() {
            return this.$cart.taxRate;
        };
        this.setDonationAmount = function(donationAmount) {
            this.$cart.donationAmount = donationAmount;
            this.$restore(this.getCart());
            return this.getDonationAmount();
        };
        this.getDonationAmount = function() {
            return +parseFloat(this.$cart.donationAmount)
                .toFixed(2);
        };
        this.setDeliveryMethod = function(deliveryMethod) {
            this.$cart.deliveryMethod = deliveryMethod;
            return this.getDeliveryMethod();
        };
        this.getDeliveryMethod = function() {
            return this.$cart.deliveryMethod;
        };
        this.setSessionId = function(sessionId) {
            this.$cart.sessionId = sessionId;
            return this.getSessionId();
        };
        this.getSessionId = function() {
            if (this.getCart()
                .items.length === 0) {
                return 0;
            }
            return this.getCart()
                .sessionId;
        };
        this.getTax = function() {
            return +parseFloat(((this.getSubTotal() / 100) * this.getCart()
                    .taxRate))
                .toFixed(2);
        };
        this.setCart = function(cart) {
            this.$cart = cart;
            return this.getCart();
        };
        this.getCart = function() {
            return this.$cart;
        };
        this.getItems = function() {
            return this.getCart()
                .items;
        };
        this.getTotalItems = function() {
            var count = 0;
            var items = this.getItems();
            angular.forEach(items, function(item) {
                count += item.getQuantity();
            });
            return count;
        };
        this.getTotalUniqueItems = function() {
            return this.getCart()
                .items.length;
        };
        this.getSubTotal = function() {
            var total = 0;
            angular.forEach(this.getCart()
                .items,
                function(item) {
                    total += item.getTotal();
                });
            return +parseFloat(total)
                .toFixed(2);
        };
        this.totalCost = function() {
            return +parseFloat(this.getSubTotal() + this.getShipping() + this.getTax())
                .toFixed(2);
        };
        this.removeItem = function(index) {
            var item = this.$cart.items.splice(index, 1)[0] || {};
            $rootScope.$broadcast('Cart:itemRemoved', item);
            $rootScope.$broadcast('Cart:change', {});
        };
        this.removeItemById = function(id, type) {
            var item;
            var cart = this.getCart();
            if (type === "clear") {
                deletecart.remove({
                    cartId: id,
                    session_id: $window.localStorage.getItem("session_id")
                }, function(response) {
                    if (angular.isDefined(response.error) && parseInt(response.error.code) === 0) {
                        angular.forEach(cart.items, function(item, index) {
                            if (item.getId() === id) {
                                item = cart.items.splice(index, 1)[0] || {};
                            }
                        });
                        if (cart.items.length === 0) {
                            $window.localStorage.removeItem('session_id');
                            var CountDown = $injector.get('CountDown');
                            CountDown.stopTimer();
                        }
                    }
                });
            } else {
                angular.forEach(cart.items, function(item, index) {
                    if (item.getId() === id) {
                        item = cart.items.splice(index, 1)[0] || {};
                    }
                });
            }
            this.setCart(cart);
            $rootScope.$broadcast('Cart:itemRemoved', item);
            $rootScope.$broadcast('Cart:change', {});
        };
        this.empty = function(type) {
            $rootScope.$broadcast('Cart:change', {});
            if (type === "clear") {
                angular.forEach(this.getItems(), function() {
                    deletecart.remove({
                        session_id: $window.localStorage.getItem("session_id")
                    }, function() {});
                });
                $window.localStorage.removeItem("session_id");
            }
            this.$cart.items = [];
            $window.localStorage.removeItem('cart');
        };
        this.isEmpty = function() {
            return (this.$cart.items.length > 0 ? false : true);
        };
        this.toObject = function() {
            if (this.getItems()
                .length === 0) {
                return false;
            }
            var items = [];
            angular.forEach(this.getItems(), function(item) {
                items.push(item.toObject());
            });
            return {
                shipping: this.getShipping(),
                tax: this.getTax(),
                taxRate: this.getTaxRate(),
                subTotal: this.getSubTotal(),
                totalCost: this.totalCost(),
                items: items
            };
        };
        this.$restore = function(storedCart) {
            var _self = this;
            _self.init();
            _self.$cart.shipping = storedCart.shipping;
            _self.$cart.tax = storedCart.tax;
            _self.$cart.sessionId = storedCart.sessionId;
            _self.$cart.donationAmount = storedCart.donationAmount;
            _self.$cart.deliveryMethod = storedCart.deliveryMethod;
            angular.forEach(storedCart.items, function(item) {
                _self.$cart.items.push(new CartItem(item._id, item._name, item._price, item._quantity, item._data));
            });
            this.$save();
        };
        this.$save = function() {
            return store.set('cart', JSON.stringify(this.getCart()));
        };
    }]);