/** Main object prototype */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global jQuery, Dispatcher, Template, Folder */

var InboxObject;

(function ($, document) {
  "use strict";

  /**
   * Init the object from the environment and context
   */
  InboxObject.prototype.init = function (data, related, inbox) {
    this.setData(data);
    this.rendered = false;
    this.classes = [];
    this.related = related || [];
    this.inbox = inbox;
  };

  /**
   * Set object properties
   */
  InboxObject.prototype.setData = function (data) {
    var k = undefined;
    for (k in data) {
      if (data.hasOwnProperty(k)) {
        this[k] = data[k];
      }
    }
  };

  /**
   * Render the object
   *
   * @return string|element
   */
  InboxObject.prototype.render = function () {
    // Override me!
    return "<div></div>";
  };

  /**
   * Object should attach its own events from there
   *
   * @param selector|element
   *   Context where the rendered element should be in
   */
  InboxObject.prototype.attachEvents = function (context) {
    // Override me!
    return undefined;
  };

  /**
   * Do render the object
   */
  InboxObject.prototype.attach = function () {
    if (!this.rendered) {
      this.element = $(this.render()).get(0);
      this.rendered = true;
      return this.element;
    }
  };

  /**
   * Add class to the object
   */
  InboxObject.prototype.addClass = function (name) {
    var k = 0;
    for (k in this.classes) {
      if (this.classes[k] === name) {
        if (this.element) {
          $(this.element).addClass(name);
        }
        return;
      }
    }
    this.classes.push(name);
  };

  /**
   * Remove class from the object
   */
  InboxObject.prototype.removeClass = function (name) {
    var k = 0;
    for (k in this.classes) {
      if (this.classes[k] === name) {
        if (this.element) {
          $(this.element).removeClass(name);
        }
        delete this.classes[k];
        return;
      }
    }
  };

  InboxObject.prototype.refresh = function () {
    // @todo async run refresh related
    // @todo reload data
    // @todo remove element
    // @todo refresh display
    // @todo create new element
    // @todo init
  };

}(jQuery, document));
