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
    var self = this;
    options = options || {};
    this.dispatcher      = new Dispatcher(options);
    this.$inbox          = $("#inbox");
    this.$folders        = $("#folders");
    this.$specialFolders = $("#special-folders");
    this.$allFolders     = $("#all-folders > li > ul");
    this.$view           = $("#viewpane");
    this.$replyForm      = $("#reply-form");
    this.$replyForm.ajaxForm({
      resetForm: true,
      success: function (data) {
        self.replyPosted();
      }
    });
    this.currentThread   = undefined;
    this.settings        = {};
    this.instances       = {};
    this.$view.find("a.close").on("click", function () {
      self.closePane();
    });
  };

  /**
   * Parse ISO8601 date
   *
   * @param string dateString
   *
   * @return Date
   */
  Inbox.parseDate = function (dateString) {
    return moment(dateString);
  };

  /**
   * Log a debug entry
   *
   * @param value
   */
  Inbox.debug = function (value) {
    if (console && console.log) {
      console.log(value);
    }
  };

  /**
   * Format a date using configuration
   *
   * @param string|Date date
   *
   * @return string
   */
  Inbox.formatDate = function (date, withTime) {
    if (!date) {
      return "";
    }
    if ("string" === typeof date) {
      date = Inbox.parseDate(date);
    }

    if (withTime) {
      return date.format("MM/DD/YYYY HH:mm");
    }
    if (moment().diff(date, 'days') < 7) {
      return date.fromNow();
    }
    return date.format("MM/DD/YYYY");
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
    var k = 0;
    this.closePane();
    this.resetMails();
    if (this.instances.Thread) {
      for (k in this.instances.Thread) {
        this.instances.Thread[k].detach();
        delete this.instances.Thread[k];
      }
    }
    this.currentThread = undefined;
    $(this.$inbox).find('.content').html("");
  };

  /**
   * Reset current mail view
   */
  Inbox.prototype.resetMails = function () {
    var k = 0;
    for (k in this.instances.Mail) {
      this.instances.Mail[k].detach();
      delete this.instances.Mail[k];
    }
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
      if (this.settings && this.settings[name]) {
        return this.settings[name];
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
    this.register(folder);

    // Check for parent case in which the folder must belong
    // to the parent folder container
    if (folder.parent && this.instances.Folder[folder.parent]) {
      parent = this.instances.Folder[folder.parent];
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
    this.register(thread);
    thread.attach(this.getInboxContainer());
  };

  /**
   * Register item
   *
   * @param InboxObject object
   */
  Inbox.prototype.register = function (object) {
    var type = object.constructor.name, id = object.getId();
    if (!this.instances[type]) {
      this.instances[type] = {};
    }
    this.instances[type][id] = object;
  };

  /**
   * Unregister object
   *
   * @param InboxObject object
   * @param boolean refresh
   *   If set to true register related items
   */
  Inbox.prototype.unregister = function (object, refresh) {
    var type = object.constructor.name, id = object.getId();
    if (this.instances[type]) {
      if (this.instances[type][id]) {
        if (refresh) {
          this.instances[type][id].change();
        }
        delete this.instances[type][id];
      }
    }
    object.detach();
  };

  /**
   * Unregister all objects of the given type
   *
   * @param string type
   *   Object constructor name
   * @param boolean refresh
   *   If set to true register related items
   */
  Inbox.prototype.unregisterAll = function (type, refresh) {
    var k = 0;
    if (this.instances[type]) {
      for (k in this.instances[type]) {
        this.instances[type].detach();
        if (refresh) {
          this.instances[type][k].change();
        }
        delete this.instances[type][k];
      }
    }
  };

  /**
   * Enable the reply form
   *
   * @param Thread|Mail target
   */
  Inbox.prototype.replyEnable = function (target) {
    var types = ["submit", "button", "input", "textarea"], k = 0;
    if (!target.uid) {
      throw "Invalid target for reply form";
    }
    for (k in types) {
      this.$replyForm.find(types[k]).removeAttr("disabled");
    }
    this.$replyForm.find("input[name=inReplyToUid]").val(target.uid);
    this.$replyForm.find("input[name=subject]").val("Re: " + target.subject);
    this.$replyForm.find("input[name=to]").val(target.from.name + " <" + target.from.mail + ">");
  };

  /**
   * Disable the reply form
   */
  Inbox.prototype.replyDisable = function () {
    var types = ["submit", "button", "input", "textarea"], k = 0;
    this.$replyForm.find("input[type=hidden]").val();
    for (k in types) {
      this.$replyForm.find(types[k]).attr({disabled: "disabled"});
    }
  };

  /**
   * Reply posted
   */
  Inbox.prototype.replyPosted = function (data) {
    if (this.currentThread) {
      this.currentThread.refresh();
      this.currentThread.loadMails();
    }
  };

  /**
   * Add view
   */
  Inbox.prototype.addMail = function (mail) {
    this.openThreadView();
    if (mail) {
      this.register(mail);
      mail.attach(this.$view.find("#thread-view .content"));
    }
  };

  /**
   * Force refresh of folder list
   */
  Inbox.prototype.refreshFolderList = function () {
    var self = this;
    this.dispatcher.get({
      url: "api/folder",
      success: function (data) {
        $.each(data, function (path, data) {
          var folder = new Folder();
          folder.init(data, self);
          self.addFolder(folder);
        });
      }
    }, this.$folders);
  };

  // Run all the things!
  $(document).ready(function () {
    if ($("#folders").length) {
      inboxInstance = new Inbox();
      // Do a preflight request for fetching server capabilities, see:
      // http://stackoverflow.com/questions/13642044/does-the-jquery-ajax-call-support-patch
      // http://www.w3.org/TR/cors/#resource-preflight-requests
      inboxInstance.dispatcher.send({
        url: "api",
        type: "options"
      });
      inboxInstance.dispatcher.get({
        url: "api/settings",
        success: function (data) {
          inboxInstance.settings = data;
          inboxInstance.refreshFolderList();
        }
      });
    }
  });

}(jQuery, document));
