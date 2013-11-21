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
    this.mails           = {};
  };

  /**
   * Tell if the object is an array
   */
  Inbox.isArray = function (value) {
    return '[object Array]' === Object.prototype.toString.call(value);
  };

  /**
   * Get the right container for displaying the mail view
   *
   * @return
   *   jQuery selector for parent container
   */
  Inbox.prototype.getViewContainer = function () {
    return this.$view.find("#thread-view .content");
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
      thread.remove();
      if (thread.element) {
        $(thread.element).remove();
      }
    });
    $(this.$inbox).find('.content').html("");
  };

  /**
   * Reset current mail view
   */
  Inbox.prototype.resetMails = function () {
    $.each(this.mails, function (key, view) {
      view.remove();
      if (view.element) {
        $(view.element).remove();
      }
    });
    $(this.$view).find('#thread-view .content').html("");
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
   * Close view and destroy current registered instance
   */
  Inbox.prototype.closePane = function () {
    this.$view.hide();
    this.$view.find("#thread-view").hide();
    this.$view.find("#compose").hide();
    this.resetMails();
  };

  /**
   * Open pane
   */
  Inbox.prototype.openThreadView = function (empty) {
    if (empty) {
      this.resetMails();
    }
    this.$view.find("#thread-view").show();
    this.$view.show();
  };

  Inbox.prototype.addFolder = function (folder) {
    this.folders[folder.path] = folder;
    this.getFolderContainer(folder).append(folder.render());
  };

  Inbox.prototype.addThread = function (thread) {
    this.threads[thread.id] = thread;
    this.getInboxContainer().append(thread.render());
  };

  Inbox.prototype.addView = function (view) {
    this.openThreadView();
    if (view) {
      this.view = view;
      this.$view.find("#thread-view .content").append(view.render());
    }
  };

  /**
   * Force refresh of folder list
   */
  Inbox.prototype.refreshFolderList = function () {
    var self = this;
    this.dispatcher.fetchJson(this.$folders, {
      'url': 'folder',
      'success': function (data) {
        $.each(data, function (path, data) {
          self.addFolder(new Folder(data, self));
        });
      }
    });
  };

  // Run all the things!
  $(document).ready(function () {
    inboxInstance = new Inbox();
    inboxInstance.refreshFolderList();
  });

}(jQuery, document));
