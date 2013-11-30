/** Main object prototype */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global jQuery, Dispatcher, Template, Folder */

var InboxObject;

(function ($) {
  "use strict";

  /**
   * Defines at least the constructor
   */
  InboxObject = function () {
    this.isObject = true;
  };

  /**
   * Render the object
   *
   * @return string|element
   */
  InboxObject.prototype.render = function () {
    // Override me!
    return "";
  };

  /**
   * Get default class
   *
   * @return string[]
   */
  InboxObject.prototype.getDefaultClasses = function () {
    // Override me!
    return [];
  };

  /**
   * Get the refresh URL
   */
  InboxObject.prototype.getUrl = function () {
    // Override me
    return false;
  };

  /**
   * Object should attach its own events from there
   *
   * @param selector|element context
   *   Context where the rendered element should be in
   */
  InboxObject.prototype.attachEvents = function (context) {
    // Override me!
    return false;
  };

  /**
   * Init the object from the environment and context
   */
  InboxObject.prototype.init = function (data, inbox, related) {
    this.setData(data);
    this.rendered = this.rendered || false;
    this.classes = [];
    this.related = related || this.related || [];
    this.inbox = inbox || this.inbox;
  };

  /**
   * Set object properties
   */
  InboxObject.prototype.setData = function (data) {
    var k = 0;
    for (k in data) {
      if (data.hasOwnProperty(k)) {
        this[k] = data[k];
      }
    }
  };

  /**
   * Force the object refresh
   */
  InboxObject.prototype.refresh = function (overlay) {
    var self = this, url = this.getUrl(), element = undefined;
    if (url) {
      if (overlay) {
        element = this.element;
      }
      // If we have an URL force data refresh
      this.inbox.dispatcher.get({
        url: url,
        data: {
          refresh: true
        },
        success: function (data) {
          if (data) {
            self.init(data);
            if (self.rendered) {
              // Force rerendering if element is already rendered
              self.attach(undefined, true);
            }
          } else {
            throw "Could not load " + url;
          }
        },
        error: function () {
          console.log("An error happened here, oups");
        }
      }, element);
    }
    return false;
  };

  /**
   * The object is being attached to the DOM
   */
  InboxObject.prototype.attach = function (container, force) {
    var output = "", defClasses = [], k = 0;
    if (!this.rendered || force) {
      output = $(this.render());
      // Attach all classes
      defClasses = this.getDefaultClasses();
      if (defClasses) {
        for (k in defClasses) {
          this.addClass(defClasses[k]);
        }
      }
      for (k in this.classes) {
        output.addClass(this.classes[k]);
      }
      // Okay now complete the object
      this.rendered = true;
      this.attachEvents(output);
      if (container) {
        if (this.element) {
          // If the caller gave a container we need to drop the
          // actual rendering because we are going to attach it
          // elsewhere
          $(this.element).remove();
        }
        container.append(output);
      } else if (this.element) {
        // When we have no container we probably are in the case
        // of an element refresh, case in which we are just going
        // to replace it
        $(this.element).replaceWith(output);
      } else {
        // We cannot attach the element
        throw "Nowhere to attach";
      }
      this.element = output.get(0);
    }
  };

  /**
   * The object is being detached from the DOM
   *
   * @param boolean remove
   *   If set to true this will force a refresh of all related
   *   components
   */
  InboxObject.prototype.detach = function (remove) {
    $(this.element).remove();
  };

  /**
   * The object is being updated
   *
   * This will refresh all related components
   */
  InboxObject.prototype.change = function (overlay) {
    var k = 0;
    for (k in this.related) {
      if ("function" === typeof this.related[k].refresh) {
        this.related[k].refresh(overlay);
      }
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

}(jQuery));
