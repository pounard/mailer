/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global Mustache */

var Template;

(function ($) {
  "use strict";

  if (!Mustache) {
    throw "Needs the mustache template engine";
  }

  Template = {};

  Template.render = function (template, data) {
    if (!Template[template]) {
      throw "Template " + template + " does not exist";
    }
    return Mustache.render(Template[template], data);
  };

  Template.folder =
         '<li class="{{classes}}">'
       + '<a href="#">'
       + '{{name}}'
       + '<span class="total">{{{total}}}</span>'
       + '{{#unread}}'
       + '<span class="unread">{{{unread}}}</span>'
       + '{{/unread}}'
       + '</a>'
       + '<ul class="children">'
       + '</ul>'
       + '</li>';

  Template.thread =
        '<div class="{{classes}}">'
      + '{{#unread}}'
      + '<div class="unread">{{{unread}}}</div>'
      + '{{/unread}}'
      + '<div class="date">{{{date}}}</div>'
      + '<div class="people">{{{persons}}}</div>'
      + '<a href="#">'
      + '<div class="subject">{{subject}}</div>'
      + '<div class="clear"></div>'
      + '<p class="summary">{{{summary}}}</p>'
      + '</a>'
      + '</div>';

  Template.person =
        '<span class="{{classes}}">'
      + '<img src="{{image}}" title="{{name}}"/>'
      + '</span>';

}(jQuery));
