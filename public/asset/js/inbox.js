/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, indent: 2 */

var Inbox = {}, Templates = {}, instance;

(function ($) {
  "use strict";

  Templates.render = function (template, data) {
    if (!Templates[template]) {
      throw "Template " + template + " does not exist";
    }
    return Mustache.render(Templates[template], data);
  };

  Templates.folder = '<li class="{{classes}}">{{name}}<span class="unread">{{unread}}</span><ul class="children"></ul></li>';

  /**
   * Constructor
   */
  Inbox = function () {
    // HTML elements we will manipulate the most
    this.jInbox          = $("#inbox");
    this.jFolders        = $("#folders");
    this.jSpecialFolders = $("#special-folders");
    this.jAllFolders     = $("#all-folders > li > ul");
    this.jView           = $("#viewpane");
    this.folders         = {};
  };

  /**
   * Load content from AJAX and do something about it
   *
   * @param element
   *   DOM element or selector query
   * @param options
   *   Object containing variou jQuery.ajax options
   */
  Inbox.load = function (element, options) {
    var jElement = $(element), complete;
    // We need at list an URL
    if (!options || !options.url) {
      throw "Options needs at least an URL";
    }
    options.async = options.async || true;
    options.cache = options.cache || false;
    if (options.complete) {
      complete = options.complete;
    }
    options.complete = function () {
      if ("function" === typeof complete) {
        complete();
      }
      jElement.removeClass('loading');
    };
    jElement.addClass('loading');
    $.ajax(options);
  };

  /**
   * Load alias tailored for JSON requests
   */
  Inbox.fetchJson = function (element, options) {
    options = options || {};
    options.async = true;
    options.cache = false;
    options.contentType = "application/json";
    options.dataType = "json";
    Inbox.load(element, options);
  };

  /**
   * Refresh folder display
   */
  Inbox.prototype.addFolder = function (folder) {
    var jParent, jElement, parent;

    if (!folder.classes) {
      folder.classes = [];
    }
    folder.classes.push("folder");

    if (folder.parent && this.folders[folder.parent]) {
      parent = this.folders[folder.parent];
      parent.classes.push("parent");
      folder.classes.push("child");
      jParent = $(parent.children);
    } else {
      if (folder.special) {
        jParent = this.jSpecialFolders;
      } else {
        jParent = this.jAllFolders;
      }
    }

    if (folder.element) {
      // FIXME This will remove children as well => BAD
      $(folder.element).remove();
    }

    jElement = $(Templates.render("folder", {
      name :   folder.name,
      unread:  folder.unreadCount,
      classes: folder.classes.join(" ")
    }));
    folder.element  = jElement.get(0);
    folder.children = jElement.find('ul.children').get(0);

    jParent.append(jElement);

    this.folders[folder.path] = folder;
  };

  /**
   * Force refresh of folder list
   */
  Inbox.prototype.refreshFolders = function () {
    var self = this;

    // We don't need this to flicker for displaying the same thing
    Inbox.fetchJson(this.jFolders, {
      'url': '/folder',
      'success': function (data) {
        $.each(data, function (path, folder) {
          self.addFolder(folder);
        });
      }
    });
  };

  /**
   * Load folder content and display it
   */
  Inbox.prototype.loadFolder = function (folder) {

    var jInbox = $("#inbox");

    jInbox.html(""); // Start by emptying content

    Inbox.load(jInbox, {
      //
    });
  };

  $("body").on('load', function () {
    instance = new Inbox();
    instance.refreshFolders();
  });

}(jQuery));
