/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global jQuery, Dispatcher, Template, Folder */

var Inbox, inboxInstance;

(function ($, document) {
  "use strict";

  /**
   * Constructor
   */
  Inbox = function (options) {
    options = options || {};
    this.dispatcher      = new Dispatcher(options);
    this.$inbox          = $("#inbox");
    this.$folders        = $("#folders");
    this.$specialFolders = $("#special-folders");
    this.$allFolders     = $("#all-folders > li > ul");
    this.$view           = $("#viewpane");
    this.folders         = {};
    this.threads         = {};
    this.view            = {};
  };

  /**
   * Tell if the object is an array
   */
  Inbox.isArray = function (value) {
    return '[object Array]' === Object.prototype.toString.call(value);
  };

  /**
   * Refresh the view using the given View instance
   *
   * @param view
   */
  Inbox.prototype.refreshView = function (view) {
      this.view = view;
      this.$view.show();
  };

  /**
   * Close view and destroy current registered instance
   */
  Inbox.prototype.closeView = function () {
      this.$view.hide();
      if (this.view) {
          this.view.remove();
          delete this.view;
      }
  };

  /**
   * Render array of persons
   */
  Inbox.prototype.renderPersons = function (persons) {
    var out = [];
    if (!Inbox.isArray(persons)) {
      persons = [persons];
    }
    $.each(persons, function (key, person) {
      out.push(Template.render("person", {
        classes: "person",
        image: "/asset/img/icons/person-32.png",
        name: person.name || person.mail
      }));
    });
    return out.join("");
  };

  /**
   * Reset current threads
   */
  Inbox.prototype.resetThreads = function () {
    $.each(this.threads, function (key, thread) {
      if (thread.element) {
        $(thread.element).remove();
      }
    });
    $(this.$inbox).find('.content').html("");
  };

  /**
   * Get the right container for the given folder
   *
   * @param folder
   *   Folder instance
   *
   * @return
   *   jQuery selector for parent container
   */
  Inbox.prototype.getFolderContainer = function (folder) {
    var parent, $container;
    if (folder.parent && this.folders[folder.parent]) {
      parent = this.folders[folder.parent];
      parent.classes.push("parent");
      $container = $(parent.children);
    } else {
      if (folder.special) {
        $container = this.$specialFolders;
      } else {
        $container = this.$allFolders;
      }
    }
    return $container.eq(0);
  };

  /**
   * Get the right container for displaying the mail view
   *
   * @return
   *   jQuery selector for parent container
   */
  Inbox.prototype.getInboxContainer = function () {
    return this.$inbox.find(".content");
  };

  /**
   * Get the right container for displaying the mail view
   *
   * @return
   *   jQuery selector for parent container
   */
  Inbox.prototype.getViewContainer = function (refresh) {
    if (refresh) {
      this.$view.show();
    }
    return this.$view.find(".content");
  };

  /**
   * Force refresh of folder list
   */
  Inbox.prototype.refreshFolders = function () {
    var self = this;
    this.dispatcher.fetchJson(this.$folders, {
      'url': 'folder',
      'success': function (data) {
        $.each(data, function (path, data) {
          var folder = new Folder(data, self);
          self.folders[folder.path] = folder;
        });
      }
    });
  };

  // Run all the things!
  $(document).ready(function () {
    inboxInstance = new Inbox();
    inboxInstance.refreshFolders();
  });

}(jQuery, document));
