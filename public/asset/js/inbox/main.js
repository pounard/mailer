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
    this.$View           = $("#viewpane");
    this.folders         = {};
  };

  /**
   * Tell if the object is an array
   */
  Inbox.isArray = function (value) {
    return '[object Array]' === Object.prototype.toString.call(value);
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
        image: "/public/asset/img/icons/person-32.png",
        name: person.name || person.mail
      }));
    });
    return out.join("");
  };

  /**
   * Refresh thread display
   */
  Inbox.prototype.addThread = function (thread, folder) {
    var jElement;

    if (!thread.classes) {
      thread.classes = [];
    }
    thread.classes.push("thread");

    if (thread.element) {
      $(thread.element).remove();
    }

    jElement = $(Template.render("thread", {
      persons: this.renderPersons(thread.persons),
      subject: thread.subject,
      date:    thread.lastUpdate,
      unseen:  thread.unseenCount,
      classes: thread.classes.join(" ")
    }));
    thread.element = jElement.get(0);

    this.jInbox.find(".content").append(jElement);
    this.threads[thread.id] = thread;
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
