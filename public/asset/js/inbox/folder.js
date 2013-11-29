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

  /**
   * Get the refresh URL
   */
  InboxObject.prototype.getUrl = function () {
    return "api/folder/" + this.path;
  };

  InboxObject.prototype.getDefaultClasses = function () {
    return ["folder"];
  };

  InboxObject.prototype.attachEvents = function (context) {
    var self = this;
    $(context).find("a").on("click", function () {
      self.loadInbox();
    });
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
        $.each(data, function (id, thread) {
          self.inbox.addThread(new Thread(thread, self));
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
          $.each(data.threads, function (id, thread) {
            this.inbox.addThread(new Thread(thread, self));
          });
        }
      }
    });
  };

}(jQuery));
