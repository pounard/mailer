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
    this.settings        = {};
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
  Inbox.prototype.renderPersonImages = function (persons) {
    var out = [];
    if (!Inbox.isArray(persons)) {
      persons = [persons];
    }
    $.each(persons, function (key, person) {
      out.push(Template.render("personImage", {
        classes: "person",
        image: "/asset/img/icons/person-32.png",
        name: person.name || person.mail,
        mail: person.mail
      }));
    });
    return out.join("");
  };

  /**
   * Render array of persons
   */
  Inbox.prototype.renderPersonLink = function (person) {
    return Template.render("personLink", {
      classes: "person-link",
      name: person.name || person.mail,
      mail: person.mail
    });
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

  /**
   * Get setting value
   *
   * This function will first attempt to find it in user configuration then
   * in global configuration
   */
  Inbox.prototype.getSetting = function (name, defaultValue) {
    if (this.settings) {
      if (this.settings.user && this.settings.user[name]) {
        return this.settings.user[name];
      }
      if (this.settings.global && this.settings.global[name]) {
        return this.settings.global[name];
      }
    }
    return defaultValue;
  };

  /**
   * Add folder
   */
  Inbox.prototype.addFolder = function (folder) {
    var key = 0, parent, $container = undefined, folders;

    // Register new folder
    this.folders[folder.path] = folder;

    // Check for parent case in which the folder must belong
    // to the parent folder container
    if (folder.parent && this.folders[folder.parent]) {
      parent = this.folders[folder.parent];
      parent.addClass("parent");
      $container = $(parent.children);
    } else {
      // Check for special folders that need specific placement
      // in the folder pane: start with the obvious INBOX folder
      if ("INBOX" === folder.name) {
        folder.addClass("folder-inbox");
        $container = this.$specialFolders;
      } else {
        folders = this.getSetting("mailboxes", {});
        // All other must be determined from the server given
        // special folder list
        for (key in folders) {
          if (folders[key] === folder.name) {
            folder.addClass("folder-" + key);
            $container = this.$specialFolders;
            break;
          }
        }
      }

      if (!$container) {
        $container = this.$allFolders;
      }
      folder.attach($container);
    }
  };

  /**
   * Add thread
   */
  Inbox.prototype.addThread = function (thread) {
    this.threads[thread.id] = thread;
    thread.attach(this.getInboxContainer());
  };

  /**
   * Add view
   */
  Inbox.prototype.addMail = function (mail) {
    this.openThreadView();
    if (mail) {
      this.mail = mail;
      mail.attach(this.$view.find("#thread-view .content"));
    }
  };

  /**
   * Force refresh of folder list
   */
  Inbox.prototype.refreshFolderList = function () {
    var self = this;
    this.dispatcher.fetchJson(this.$folders, {
      'url': 'api/folder',
      'success': function (data) {
        $.each(data, function (path, data) {
          var folder = new Folder();
          folder.init(data, self);
          self.addFolder(folder);
        });
      }
    });
  };

  // Run all the things!
  $(document).ready(function () {
    if ($("#folders").length) {
      inboxInstance = new Inbox();
      inboxInstance.dispatcher.fetchJson(null, {
        url: 'api/settings',
        success: function (data) {
          inboxInstance.settings = data;
          inboxInstance.refreshFolderList();
        }
      });
    }
  });

}(jQuery, document));
