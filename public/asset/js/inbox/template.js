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
         '<li>'
       + '<a href="#">'
       + '{{name}}'
       + '{{#unseen}}'
       + '<span class="unseen">{{{unseen}}}</span>'
       + '{{/unseen}}'
       + '{{#total}}'
       + '<span class="total">{{{total}}}</span>'
       + '</a>'
       + '{{/total}}'
       + '<ul class="children">'
       + '</ul>'
       + '</li>';

  Template.mail =
        '<div class="{{classes}}">'
      + '<div class="date">{{{date}}}</div>'
      + '<div class="subject">{{subject}}</div>'
      + '<div class="clear"></div>'
      + '<div class="from">'
      + '<div class="star {{starred-class}}"><a href="#" title="Star this mail">&nbsp;&nbsp;&nbsp;</a></div>'
      + 'From {{{from}}}'
      + '</div>'
      + '<a class="delete" href="#">Delete</a>'
      + '<div class="body">{{{body}}}</div>'
      + '</div>';

  Template.thread =
        '<div>'
      + '{{#unseen}}'
      + '<div class="unseen">{{{unseen}}}</div>'
      + '{{/unseen}}'
      + '<div class="date">{{{date}}}</div>'
      + '<div class="people">{{{persons}}}</div>'
      + '<a href="#">'
      + '<div class="subject">{{subject}}</div>'
      + '<div class="clear"></div>'
      + '<p class="summary">{{{summary}}}</p>'
      + '</a>'
      + '</div>';

  Template.personImage =
        '<span class="{{classes}}">'
      + '<a href="mailto://{{mail}}" title="{{name}}">'
      + '<img src="{{image}}" title="{{name}}"/>'
      + '</a>'
      + '</span>';

  Template.personLink =
        '<span class="{{classes}}">'
      + '<a href="mailto://{{mail}}" title="{{name}}">{{name}}</a>'
      + '</span>';

}(jQuery));
