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
   * Get the object identifier
   */
  InboxObject.prototype.getId = function () {
    // Override me.
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
  InboxObject.prototype.refresh = function (overlay, doRelated) {
    var self = this, url = this.getUrl(), element = undefined;
    if (url) {
      this.inbox.dispatcher.start();
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
        error: function (jqXHR) {
          // In case of 404 Not Found the object does not exist
          // on server anymore, remove it from the UI
          switch (jqXHR.status) {
              case 404:
                  self.detach();
                  break;
          }
        }
      }, element);
      if (doRelated) {
        this.change(overlay);
      }
      this.inbox.dispatcher.exec();
    }
    return false;
  };

  /**
   * The object is being attached to the DOM
   */
  InboxObject.prototype.attach = function (container, force) {
    var output = "", defClasses = [], k = 0, actions;
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
      actions = this.buildActions();
      if (actions) {
        output.append(actions);
      }
      this.element = output.get(0);
    }
  };

  /**
   * The object is being detached from the DOM
   *
   * @param boolean doRelated
   * @param boolean overlay
   */
  InboxObject.prototype.detach = function (doRelated, overlay) {
    $(this.element).remove();
    if (doRelated) {
      this.inbox.dispatcher.start();
      this.change(overlay, doRelated);
      this.inbox.dispatcher.exec();
    }
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
        this.related[k].refresh(overlay, true);
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
        return;
      }
      if (this.element) {
        $(this.element).addClass(name);
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

  /**
   * Get object actions
   *
   * @return Array
   *   Each value must be a object containing the following keys:
   *     - url : String
   *     - type : String ("get", "put", "patch", "delete", "post")
   *       but also can be specific options: "open" or "blank" for
   *       non ajax links (where "blank" means a new window)
   *     - title : String
   *     - success : function
   *     - blocking: boolean
   *     - refresh : boolean
   *     - spacer: boolean if this value is set all others are being
   *       ignored
   */
  InboxObject.prototype.getActions = function () {
    // Override me.
    return [];
  };

  /**
   * Get real URL from path
   *
   * @param path
   *
   * @return string
   */
  InboxObject.prototype.pathToUrl = function (path) {
    return this.inbox.dispatcher.pathToUrl(path);
  };

  /**
   * Build the action links for the object
   */
  InboxObject.prototype.buildActions = function () {
    var
      actions = this.getActions(),
      k = 0,
      item,
      items = [],
      output,
      el,
      self = this,
      displayed = false;

    // Using a for () will make our action variable being
    // overwritten in the loop and all actions will do the
    // same as the last one (which is delete): bad idea...
    // $.each() isolate the k and the value variables and
    // reduce their scope to the function
    $.each(actions, function (k, action) {
      action = actions[k];
      if (action.spacer) {
        item = $(Template.render('actionspacer'));
      } else {
        if ("blank" === action.type) {
          item = $(Template.render('action', {
            title: action.title,
            href: self.pathToUrl(action.url),
            target: "blank",
            id: k
          }));
        } else if ("open" === action.type) {
          item = $(Template.render('action', {
            title: action.title,
            href: self.pathToUrl(action.url),
            id: k
          }));
        } else {
          item = $(Template.render('action', {
            title: action.title,
            href: "#",
            id: k
          }));
          item.find("a").on("click", function (e) {
            // Prepare variables for the dispatcher
            if (action.blocking) {
              el = self.element;
            } else {
              el = undefined;
            }
            // Where the magic actually happen
            self.inbox.dispatcher.send(action, el);
          });
        };
      }
      items.push(item);
    });

    if (items.length) {

      output = $(Template.render('actions'));
      item = output.find("ul");
      for (k in items) {
        item.append(items[k]);
      }

      // Hide/show on click
      output.find("> a").on("click", function () {
        if (displayed) {
          item.hide();
          displayed = false;
        } else {
          item.show();
          displayed = true;
        }
      });

      return output;
    }
  };

}(jQuery));
