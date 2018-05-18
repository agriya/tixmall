 'use strict';
 /**
  * @ngdoc directive
  * @name tixmall.directive:calendar
  * @scope
  * @restrict E
  *
  * @description
  * It a calendar used in home and event schedule page
  * Mini  mode calendar
  *
  * @param {callback, type}  field   
  * type field might be home or eventschedule
  * callback field is callback function, it will be triggered on event click, it will automatically parent elements on it trigger
  */
 angular.module('tixmall')
     .directive('calendar', ['eventView', '$state', 'events', '$filter', function(eventView, $state, eventsList, $filter) {
         return {
             restrict: "E",
             templateUrl: "views/calendar.html",
             scope: {
                 refreshEvents: '&',
                 type: '@'
             },
             link: function(scope) {
                 var current_date = $filter('date')(new Date(), 'yyyy-MM-dd');
                 //jshint unused:false
                 /*jshint -W117 */
                 $('#calendar-new')
                     .fullCalendar({
                         fixedWeekCount: false,
                         height: "auto",
                         header: {
                             left: 'prev',
                             center: 'title',
                             right: 'next'
                         },
                         defaultDate: new Date(),
                         eventAfterRender: function() {
                             // add titles to "+# more links"
                             $('.fc-more-cell a')
                                 .each(function() {
                                     this.title = this.textContent;
                                 });
                         },
                         // add event name to title attribute on mouseover
                         eventMouseover: function(event, jsEvent, view) {
                             if (view.name !== 'agendaDay') {
                                 $(jsEvent.target)
                                     .attr('title', event.title);
                             }
                         },
                         eventRender: function(event, element, view) {
                             // event.start is already a moment.js object
                             // we can apply .format()
                             var dateString = event.start.format("YYYY-MM-DD");
                             // console.log($(view.el[0]).find('.fc-day[data-date=' + dateString + ']'));
                             if (scope.type === "home") {
                                 if ($(view.el[0])
                                     .find('.fc-day-number[data-date=' + dateString + '] a')
                                     .hasClass('event_mark')) {
                                     //$(view.el[0]).find('.fc-day[data-date=' + dateString + ']').find('.event_count').replace('<div class="event_count"><span class="badge text-danger">5</span></div>')
                                 } else {
                                     $(view.el[0])
                                         .find('.fc-day-number[data-date=' + dateString + ']')
                                         .append('<p class="event_mark_parent"><a class="event_mark"><i class="fa fa-circle fa-fw text-danger" aria-hidden="true"></i></p>');
                                 }
                             } else {
                                 var event_background = (dateString < current_date) ? '#c00d01' : '#006400';
                                 var hover_event = (dateString < current_date) ? 'default' : 'pointer';
                                 $(view.el[0])
                                     .find('.fc-day-number[data-date=' + dateString + ']')
                                     .css({
                                         background: event_background,
                                         position: 'relative',
                                         color: '#fff',
                                         cursor: hover_event
                                     });
                                 if ($(view.el[0])
                                     .find('.fc-day-number[data-date=' + dateString + '] div')
                                     .hasClass('event_count')) {
                                     //$(view.el[0]).find('.fc-day[data-date=' + dateString + ']').find('.event_count').replace('<div class="event_count"><span class="badge text-danger">5</span></div>')
                                 } else {
                                     $(view.el[0])
                                         .find('.fc-day-number[data-date=' + dateString + ']')
                                         .append('<div class="event_count"><span class="badge">' + event.count + '</span></div>');
                                 }
                             }
                         },
                         editable: true,
                         eventLimit: true, // allow "more" link when too many events
                         eventColor: '#fff',
                         dayClick: function(date, jsEvent, view) {
                             if (moment()
                                 .diff(date, 'days') > 0) {
                                 $('#calendar-new')
                                     .fullCalendar('unselect');
                                 // or display some sort of alert
                                 return false;
                             }
                             var currentDate = date.format("YYYY-MM-DD");
                             $(".fc-event-highlight")
                                 .removeClass("fc-event-highlight");
                             if ($(jsEvent.target)
                                 .hasClass('fc-day-number')) {
                                 $(jsEvent.target)
                                     .addClass("fc-event-highlight");
                                 if (scope.type === "home") {
                                     //path to go events lists
                                     $state.go('events', {
                                         date: moment(date)
                                             .format('YYYY-MM-DD')
                                     });
                                 } else {
                                     // its a callbak function on coded in parent controller
                                     scope.refreshEvents({
                                         dateFilter: moment(date)
                                             .format('YYYY-MM-DD')
                                     });
                                 }
                             }
                         },
                         events: function(start, end, timezone, callback) {
                             var events = [];
                             var start_date = moment(start)
                                 .format('YYYY-MM-DD');
                             var end_date = moment(end)
                                 .format('YYYY-MM-DD');
                             var params;
                             var getDateResult;
                             var getDate = moment(new Date())
                                 .format('YYYY-MM-DD');
                             if (start_date <= getDate && getDate <= end_date) {
                                 getDateResult = getDate;
                             } else {
                                 getDateResult = start_date;
                             }
                             /* scope.refreshEvents({
                                  dateFilter: getDateResult
                              });*/
                             if (scope.type === "home") {
                                 params = {
                                     event_date: start_date,
                                     event_end_date: end_date,
                                 };
                                 eventsList.get(params)
                                     .$promise.then(function(response) {
                                         angular.forEach(response.data, function(value) {
                                             angular.forEach(value.event_schedule, function(value1) {
                                                 events.push({
                                                     title: response.data.name,
                                                     start: value1.start_date,
                                                     end: value1.end_date, // will be parsed
                                                 });
                                             });
                                         });
                                         callback(events);
                                     });
                             } else {
                                 params = {
                                     event_date: start_date,
                                     event_end_date: end_date,
                                     id: $state.params.id
                                 };
                                 eventView.get(params)
                                     .$promise.then(function(response) {
                                         angular.forEach(response.data.event_schedule, function(value) {
                                             angular.forEach(value.schedule_timing, function(value1) {
                                                 events.push({
                                                     title: response.data.name,
                                                     start: value1.start_date,
                                                     end: value1.end_date, // will be parsed
                                                     count: value.event_count
                                                 });
                                             });
                                         });
                                         callback(events);
                                     });
                             }
                         }
                     });
             }
         };
     }]);