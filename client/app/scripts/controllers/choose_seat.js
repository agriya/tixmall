'use strict';
/**
 * @ngdoc function
 * @name tixmall.controller:ChooseSeatController
 * @description
 * # ChooseSeatController
 * Controller of the tixmall
 */
angular.module('tixmall')
    .controller('ChooseSeatController', ['$scope', '$state', 'md5', 'chooseSeat', '$parse', 'carts', 'Cart', 'CountDown', '$window', 'flash', '$filter', '$rootScope', 'preloader', function($scope, $state, md5, chooseSeat, $parse, carts, Cart, CountDown, $window, flash, $filter, $rootScope, preloader) {
        /*jshint -W117 */
        $scope.Cart = Cart;
        var model = this;
        var hash;
        var svg = "";
        var zone_name = "";
        var image_type = "";
        var append_window = true;
        $scope.is_link_enabled = true;
        $scope.svg_images = [];
        $('.venue-images')
            .css({
                "visibility": "hidden"
            });
        $('.loader-image')
            .css({
                "display": "block"
            });
        $scope.event_id = $state.params.event_id;
        chooseSeat.get({
                event_id: $state.params.event_id,
                zone_id: $state.params.zone_id
            })
            .$promise.then(function(response) {
                model.chooseSeat = response.data;
                var normal_thumb_hash = md5.createHash('Event' + model.chooseSeat.Event[0].id + 'png' + 'seat_event_thumb');
                model.chooseSeat.Event[0].normal_image_name = '/images/seat_event_thumb/Event/' + model.chooseSeat.Event[0].id + '.' + normal_thumb_hash + '.png';
                $scope.venue_id = response.data.venue_id;
                $scope.zone_name = response.data.name;
                zone_name = response.data.name;
                var event_schedule = model.chooseSeat.EventSchedule;
                //finding start date
                $scope.startdate = _.find(event_schedule, function(item) {
                    return parseInt($state.params.schedule_id) === item.id;
                });
                if (angular.isDefined(model.chooseSeat.attachments) && model.chooseSeat.attachments !== null) {
                    angular.forEach(model.chooseSeat.attachments, function(attachment) {
                        image_type = attachment.filename.split(".");
                        if (image_type[1] === "svg") {
                            $scope.svg_image = '/images/VenueZoneSVG/' + model.chooseSeat.id + '/' + attachment.filename;
                            $scope.svg_images.push($scope.svg_image);
                        }
                    });
                }
                var stroke_color = ['#be1213', '#006400', '#0000FF', '#FFA500', '#FFFF00', '#00FFFF', '#00FF00'];
                var zone_id = [];
                var zone_prices = [];
                angular.forEach(model.chooseSeat.event_prices, function(event_zone, key) {
                    var zone_price = [];
                    angular.forEach(event_zone.event_zone_prices, function(event_zone_price) {
                        zone_price.push({
                            'id': event_zone_price.id,
                            'price': event_zone_price.price,
                            'price_type_id': event_zone_price.price_type_id
                        });
                        if (parseInt(event_zone_price.price_type_id) === 1) {
                            zone_prices.push({
                                'zone_id': event_zone.id,
                                'price': event_zone_price.price,
                                'style': stroke_color[key]
                            });
                        }
                    });
                    var available_seat_no = '';
                    var unavailable_seat_no = '';
                    angular.forEach(event_zone.event_zone_section_rows, function(event_zone_section_row) {
                        angular.forEach(model.chooseSeat.venue_zone_section_seats, function(seats) {
                            if (seats.venue_zone_section_id === event_zone_section_row.venue_zone_section_id && seats.venue_zone_section_row_id === event_zone_section_row.venue_zone_section_row_id) {
                                if (seats.is_available === 1) {
                                    if (available_seat_no !== '') {
                                        available_seat_no = available_seat_no + ', ' + '#seat-' + seats.id;
                                    } else {
                                        available_seat_no = '#seat-' + seats.id;
                                    }
                                } else {
                                    if (unavailable_seat_no !== '') {
                                        unavailable_seat_no = unavailable_seat_no + ', ' + '#seat-' + seats.id;
                                    } else {
                                        unavailable_seat_no = '#seat-' + seats.id;
                                    }
                                }
                            }
                        });
                    });
                    zone_id.push({
                        'event_zone_id': event_zone.id,
                        'event_zone_prices': zone_price,
                        'available_seat_no': available_seat_no,
                        'unavailable_seat_no': unavailable_seat_no
                    });
                });

                function makeSVG(tag, attrs) {
                    var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
                    for (var k in attrs) {
                        el.setAttribute(k, attrs[k]);
                    }
                    return el;
                }
                $scope.zone_prices = zone_prices;
                preloader.preloadImages($scope.svg_images)
                    .then(function handleResolve() {
                        var svgInterval = setInterval(function() {
                            if ($("#zoom-svg")
                                .html() !== undefined) {
                                svg = $("#zoom-svg")
                                    .getSVG();
                                if (svg[0].contentType === 'image/svg+xml') {
                                    clearInterval(svgInterval);
                                    // Zoom svg image
                                    var panZoom = svgPanZoom('#zoom-svg', {
                                        zoomEnabled: true,
                                        controlIconsEnabled: false
                                    });
                                    $('.js-image-up')
                                        .on('click', function() {
                                            panZoom.panDown();
                                        });
                                    $('.js-image-down')
                                        .on('click', function() {
                                            panZoom.panUp();
                                        });
                                    $('.js-image-left')
                                        .on('click', function() {
                                            panZoom.panLeft();
                                        });
                                    $('.js-image-right')
                                        .on('click', function() {
                                            panZoom.panRight();
                                        });
                                    $('.js-image-reset')
                                        .on('click', function() {
                                            panZoom.reset();
                                        });
                                    $('.js-image-zoomin')
                                        .on('click', function() {
                                            panZoom.zoomIn();
                                        });
                                    $('.js-image-zoomout')
                                        .on('click', function() {
                                            panZoom.zoomOut();
                                        });
                                    // Available seat color changes on default
                                    angular.forEach(zone_id, function(zones, key) {
                                        $(svg)
                                            .find(zones.available_seat_no)
                                            .find('circle:eq(1), rect')
                                            .css({
                                                "stroke": stroke_color[key],
                                                "fill": "transparent",
                                                "stroke-width": "1",
                                                "cursor": "pointer"
                                            });
                                        $(svg)
                                            .find(zones.unavailable_seat_no)
                                            .find('circle:eq(1), rect')
                                            .css({
                                                "stroke": "#be1213",
                                                "fill": "#be1213",
                                                "stroke-width": "1",
                                                "cursor": "pointer"
                                            });
                                    });
                                    // View from seat
                                    $scope.viewFromseat = function() {
                                        append_window = false;
                                        $(svg)
                                            .find('.syos-seatmap')
                                            .css("opacity", "0.25");
                                        angular.forEach(model.chooseSeat.venue_zone_preview, function(value) {
                                            var transform = $(svg)
                                                .find('#seat-' + value.venue_section_row_seat_id)
                                                .attr('transform');
                                            var g = makeSVG('g', {
                                                transform: transform
                                            });
                                            $(svg)
                                                .find('.syos-seatviews')
                                                .append(g);
                                            var image = makeSVG('image', {
                                                href: '/images/camera.png',
                                                width: 50,
                                                height: 50,
                                                id: value.id
                                            });
                                            g.appendChild(image);
                                        });
                                        $scope.is_link_enabled = false;
                                        $(svg)
                                            .find('image')
                                            .click(function() {
                                                $('body')
                                                    .find('.preview-window')
                                                    .remove();
                                                var preview_id = $(this)
                                                    .attr('id');
                                                hash = md5.createHash('VenueZonePreview' + preview_id + 'png' + 'original');
                                                var preview_image = '/images/original/VenueZonePreview/' + preview_id + '.' + hash + '.png';
                                                var preview_window = "<div class='preview-window'><div class='preview-windows'><div><p class='pull-right'><a href='javascript:;'><img src='/images/close.png' ng-click='priceClose()' height=20 width=20/></a></p></div><div><img src='" + preview_image + "' height=300 width=350 /></div></div></div>";
                                                $('body')
                                                    .append(preview_window);
                                            });
                                        $scope.previewClose = function() {
                                            $('body')
                                                .find('.preview-window')
                                                .remove();
                                        };
                                        $('body')
                                            .click(function() {
                                                $('body')
                                                    .find('.preview-window')
                                                    .remove();
                                            });
                                    };
                                    // Back to seat
                                    $scope.backToseat = function() {
                                        append_window = true;
                                        $(svg)
                                            .find('.syos-seatmap')
                                            .css("opacity", "");
                                        $(svg)
                                            .find('.syos-seatviews')
                                            .html('');
                                        $scope.is_link_enabled = true;
                                    };
                                    $(svg)
                                        .find('.js-seat')
                                        .click(function() {
                                            $('body')
                                                .find('.price-window')
                                                .remove();
                                            var seat_name = "";
                                            var section_id = "";
                                            var section_name = "";
                                            var section_row_id = "";
                                            var section_row_name = "";
                                            var is_append_window = false;
                                            $scope.current_selected_item = {};
                                            var seat_id = $(this)
                                                .attr('id');
                                            var is_show_seat = false;
                                            angular.forEach($scope.addedItems, function(value) {
                                                if (seat_id === 'seat-' + value.data.seat_id) {
                                                    is_show_seat = true;
                                                }
                                            });
                                            if (is_show_seat === false) {
                                                var price_window = "<div class='price-window'><div class='price-windows'><div class='col-md-12'>";
                                                angular.forEach(model.chooseSeat.venue_zone_section_seats, function(seats) {
                                                    var select_seat_id = seat_id.replace('seat-', '');
                                                    if (parseInt(seats.id) === parseInt(select_seat_id)) {
                                                        seat_name = seats.seat_number;
                                                        section_id = seats.venue_zone_section_id;
                                                        section_row_id = seats.venue_zone_section_row_id;
                                                        $scope.current_selected_item.seat_name = seat_name;
                                                        $scope.current_selected_item.seat_id = seats.id;
                                                    }
                                                });
                                                angular.forEach(model.chooseSeat.venue_zone_sections, function(venue_zone_section) {
                                                    if (parseInt(venue_zone_section.id) === parseInt(section_id)) {
                                                        section_name = venue_zone_section.name;
                                                        $scope.current_selected_item.venue_zone_section_name = section_name;
                                                        $scope.current_selected_item.venue_zone_section_id = venue_zone_section.id;
                                                    }
                                                });
                                                angular.forEach(model.chooseSeat.venue_zone_section_row, function(venue_zone_section_row) {
                                                    if (parseInt(venue_zone_section_row.id) === parseInt(section_row_id)) {
                                                        section_row_name = venue_zone_section_row.name;
                                                        $scope.current_selected_item.venue_zone_section_row_name = section_row_name;
                                                        $scope.current_selected_item.venue_zone_section_row_id = venue_zone_section_row.id;
                                                    }
                                                });
                                                $scope.current_selected_item.zone_name = zone_name;
                                                price_window = price_window + "<p class='pull-left'><b>" + zone_name + ", Section: " + section_name + ", Row: " + section_row_name + ", Seat: " + seat_name + "</b></p><p class='pull-right'><img src='/images/close.png' ng-click='priceClose()' height=20 width=20/></p></div>";
                                                angular.forEach(zone_id, function(zones) {
                                                    if (zones.available_seat_no.length > 0) {
                                                        if (zones.available_seat_no.indexOf('#' + seat_id) !== -1) {
                                                            is_append_window = true;
                                                            $(svg)
                                                                .find('#' + seat_id)
                                                                .find('circle:eq(1), rect')
                                                                .css({
                                                                    "fill": '#25a61f',
                                                                    "stroke": '#25a61f'
                                                                });
                                                            angular.forEach(zones.event_zone_prices, function(prices) {
                                                                angular.forEach(model.chooseSeat.price_types, function(price_type) {
                                                                    if (parseInt(price_type.id) === parseInt(prices.price_type_id)) {
                                                                        price_window = price_window + '<div class="col-md-12"><div class="col-md-8 row"><b>' + price_type.name + '' + $filter("currency")($rootScope.formatCurrency(prices.price), $rootScope.selectedCurrency.currency_symbol, $rootScope.GeneralConfig.fraction) + '</b><br/>including fees*';
                                                                        if (parseInt(price_type.id) === 2) {
                                                                            price_window = price_window + '<p>Disabled concert goers (and one companion) receive a 50% discount for this concert. Please select if this applies to you. However if you have specific seat requirements or are a wheelchair user please phone the box office on 020 7070 4410 to book.</p>';
                                                                        }
                                                                        price_window = price_window + "</div><div class=\"col-md-4 text-right\"><button class=\"btn btn-sm text-uppercase btn-danger\" ng-click=\"addItemsToCart(current_selected_item, " + prices.price + "," + prices.price_type_id + ",'" + price_type.name + "'," + zones.event_zone_id + ")\" >Select</button></div><div class=\"clearfix\"></div><hr class=\"navbar-btn\"/></div>";
                                                                    }
                                                                });
                                                            });
                                                        }
                                                    }
                                                });
                                                price_window = price_window + '<div class="clearfix"><h6>* Full breakdown of fees available in the basket.</h6></div></div></div>';
                                                if (is_append_window === true && append_window === true) {
                                                    angular.element(document)
                                                        .injector()
                                                        .invoke(function($compile) {
                                                            $('body')
                                                                .append($compile(price_window)($scope));
                                                        });
                                                }
                                                $('body')
                                                    .click(function() {
                                                        var is_seat_selected = false;
                                                        angular.forEach($scope.addedItems, function(value) {
                                                            if (seat_id === 'seat-' + value.data.seat_id) {
                                                                is_seat_selected = true;
                                                            }
                                                        });
                                                        if (is_seat_selected === false) {
                                                            $(svg)
                                                                .find('#' + seat_id)
                                                                .find('circle:eq(1), rect')
                                                                .css({
                                                                    "fill": "transparent",
                                                                    "stroke": '#be1213'
                                                                });
                                                        }
                                                        $('body')
                                                            .find('.price-window')
                                                            .remove();
                                                    });
                                            }
                                        });
                                    $scope.priceClose = function() {
                                        $('body')
                                            .find('.price-window')
                                            .remove();
                                    };
                                    $scope.zoneView = function(zone_view_id) {
                                        angular.forEach(zone_id, function(zones) {
                                            $('#' + zones.event_zone_id)
                                                .css({
                                                    "font-weight": "normal"
                                                });
                                            if (zone_view_id !== zones.event_zone_id) {
                                                $(svg)
                                                    .find(zones.unavailable_seat_no)
                                                    .find('circle:eq(1), rect')
                                                    .css("opacity", "0.1");
                                                $(svg)
                                                    .find(zones.available_seat_no)
                                                    .find('circle:eq(1), rect')
                                                    .css("opacity", "0.1");
                                            } else {
                                                $(svg)
                                                    .find(zones.available_seat_no)
                                                    .find('circle:eq(1), rect')
                                                    .css("opacity", "");
                                            }
                                        });
                                        $('#' + zone_view_id)
                                            .css({
                                                "font-weight": "bold"
                                            });
                                    };
                                    $('.venue-images')
                                        .css({
                                            "visibility": "visible"
                                        });
                                    $('.loader-image')
                                        .css({
                                            "display": "none"
                                        });
                                }
                            }
                        }, 100);
                    });
            });
        $scope.addedItems = {};
        $scope.totalcost = 0;
        var count = 0;
        /**
         * @ngdoc method
         * @name ChooseSeatController.addItemsToCart
         * @methodOf module.ChooseSeatController
         * @description
         * This method will add items to addedItems object
         * @param {object,string, string,string} 
         */
        $scope.addItemsToCart = function(items, price, price_type_id, price_name, event_zone) {
            $scope.isDetail = true;
            items.price = {};
            items.price.price = price;
            items.price.price_type_id = price_type_id;
            items.price.name = price_name;
            items.event_start_date = $scope.startdate.start_date;
            items.event_schedule_id = $scope.startdate.id;
            items.event_end_date = $scope.startdate.end_date;
            items.event_id = model.chooseSeat.Event[0].id;
            items.category_id = model.chooseSeat.Event[0].category_id;
            $scope.addedItems[count] = {};
            $scope.addedItems[count].id = items.seat_id;
            $scope.addedItems[count].name = model.chooseSeat.Event[0].name;
            $scope.addedItems[count].price = items.price.price;
            $scope.addedItems[count].quantity = 1;
            $scope.addedItems[count].data = items;
            $scope.addedItems[count].venue_zone_section_id = items.venue_zone_section_id;
            $scope.addedItems[count].venue_zone_section_row_id = items.venue_zone_section_row_id;
            $scope.addedItems[count].event_zone_id = event_zone;
            $scope.totalcost = $scope.totalcost + price;
            count++;
        };
        /**
         * @ngdoc method
         * @name ChooseSeatController.removeAddedItem
         * @methodOf module.ChooseSeatController
         * @description
         * This method will remove single item to addedItems object
         * @param {string ,number, string} 
         */
        $scope.removeAddedItem = function(event, index, price) {
            $scope.totalcost = $scope.totalcost - price;
            $('#basket_row_' + index)
                .remove();
            delete $scope.addedItems[index];
        };
        $scope.addItemsToBasket = {};
        $scope.basketDetails = {};
        /**
         * @ngdoc method
         * @name ChooseSeatController.addToBasket
         * @methodOf module.ChooseSeatController
         * @description
         * This method will add items to Cart
         */
        $scope.addToBasket = function() {
            $scope.Cart = Cart;
            var session_id = $window.localStorage.getItem("session_id");
            if (angular.isDefined(session_id) && (session_id === null || session_id === '')) {
                session_id = $rootScope.generateSession();
                $window.localStorage.setItem("session_id", session_id);
            }
            angular.forEach($scope.addedItems, function(value, key) {
                $scope.addItemsToBasket[key] = {
                    'event_id': model.chooseSeat.Event[0].id,
                    'session_id': session_id,
                    "event_zone_id": value.event_zone_id,
                    'venue_zone_section_id': value.data.venue_zone_section_id,
                    'venue_zone_section_row_id': value.data.venue_zone_section_row_id,
                    'venue_zone_section_seat_id': value.data.seat_id,
                    'price': value.price,
                    'gift_voucher_id': 0,
                    'is_donation': 0,
                    'price_type_id': value.data.price.price_type_id,
                    "event_schedule_id": value.data.event_schedule_id
                };
            });
            if (Object.keys($scope.addedItems)
                .length > 0) {
                carts.addtocart($scope.addItemsToBasket, function(response) {
                    $scope.basketDetails.cart = response.data;
                    $scope.basketDetails.event = model.chooseSeat.Event;
                    Cart.setSessionId(session_id);
                    // $window.localStorage.setItem("session_id", response.data[0].session_id);
                    Cart.$restore(Cart.getCart());
                    flash.set("Added items to basket successfully", "success", false);
                    //start timer with 20 sec for fresh cart item else it will resume older time
                    if (!$rootScope.timerStarted) {
                        CountDown.stopTimer();
                        CountDown.startTimer(60 * 20);
                    }
                    $state.go('booking_basket', {});
                });
            } else {
                flash.set("No items added", "error", false);
            }
        };
    }]);