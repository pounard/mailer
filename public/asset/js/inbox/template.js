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

  Template.action =
        '<li><a class="{{id}}" href="#">{{title}}</a></li>';

  Template.actions =
        '<div class="actions">'
      + '<a href="#">Open</a>'
      + '<ul>'
      + '</ul>'
      + '</div>';

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
        '<div>'
      + '<div class="date">{{{date}}}</div>'
      + '<div class="subject">'
      + '{{subject}}'
      + '<a class="delete hover-link" href="#" title="Delete">Delete</a>'
      // + '<div class="source hover-link"><a href="#" title="View source">View source</a></div>'
      + '</div>'
      + '<div class="clear"></div>'
      + '<div class="from">'
      + '<div class="star-shortcut {{starred-class}}"><a href="#" title="Star this mail">&nbsp;&nbsp;&nbsp;</a></div>'
      + 'From {{{from}}}'
      + '</div>'
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
