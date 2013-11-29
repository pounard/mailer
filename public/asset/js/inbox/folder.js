/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global jQuery, Template, Inbox, inboxInstance, InboxObject, Thread */

var Folder;

(function ($) {
  "use strict";

  Folder = function () {};
  Folder.prototype = new InboxObject();
  Folder.prototype.constructor = Folder;

  Folder.prototype.render = function () {
    return Template.render("folder", {
      name:    this.name,
      unseen:  this.unseen,
      recent:  this.recent,
      total:   this.total
    });
  };

  Folder.prototype.getUrl = function () {
    return "api/folder/" + this.path;
  };

  Folder.prototype.getDefaultClasses = function () {
    return ["folder"];
  };

  Folder.prototype.attachEvents = function (context) {
    var self = this;
    $(context).find("a").on("click", function () {
      self.loadInbox();
    });
  };

  /**
   * Create thread instanc from data and attach it
   */
  Folder.prototype.createThread = function (data) {
    var thread = new Thread();
    data.folder = this;
    thread.init(data, this.inbox, [this]);
    this.inbox.addThread(thread);
  };

  /**
   * Load thread data
   */
  Folder.prototype.loadInbox = function () {
    var self = this;
    this.inbox.resetThreads();
    this.inbox.closePane();
    this.touch = new Date();
    this.inbox.dispatcher.fetchJson(this.inbox.$inbox, {
      url: "api/thread/" + this.path,
      success: function (data) {
        $.each(data, function (id, child) {
          self.createThread(child);
        });
      }
    });
  };

  /**
   * Refresh thread data
   */
  Folder.prototype.refreshInbox = function () {
    var self = this, since;
    // This will force a check
    if (this.touch) {
      since = Math.round(this.touch.getTime() / 1000);
    } else {
      since = 0;
    }
    this.touch = new Date();
    this.inbox.dispatcher.fetchJson(this.inbox.$inbox, {
      url: "api/thread/" + this.path,
      since: since,
      success: function (data) {
        if (data.threads) {
          $.each(data.threads, function (id, child) {
            self.createThread(child);
          });
        }
      }
    });
  };

}(jQuery));
