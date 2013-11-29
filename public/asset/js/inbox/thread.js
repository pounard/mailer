/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global jQuery, Template, Inbox, inboxInstance, InboxObject, View */

var Thread;

(function ($) {
  "use strict";

  Thread = function () {};
  Thread.prototype = new InboxObject();
  Thread.prototype.constructor = Thread;

  Thread.prototype.render = function () {
    var date = this.updated || this.created;
    if ("string" === typeof date) {
      date = new Date(Date.parse(date));
      date = [date.getDay(), date.getMonth(), date.getFullYear()].join("/");
    }
    return Template.render("thread", {
      persons: this.inbox.renderPersonImages(this.persons),
      subject: this.subject,
      date:    date,
      total:   this.total,
      recent:  this.recent,
      unseen:  this.unseen,
      summary: this.summary
    });
  };

  Thread.prototype.getUrl = function () {
    return "api/thread/" + this.folder.path + '/' + this.id;
  };

  Thread.prototype.getDefaultClasses = function () {
    return ["thread"];
  };

  Thread.prototype.attachEvents = function (context) {
    var self = this;
    $(context).find("a").on("click", function () {
      self.loadMails();
    });
  };

  /**
   * Load thread data
   */
  Thread.prototype.loadMails = function () {
    var self = this;
    this.inbox.openThreadView(true);
    this.inbox.dispatcher.fetchJson(this.inbox.getViewContainer(), {
      url: 'api/thread/' + this.folder.path + '/' + this.uid + '/mail',
      data: {
        complete: 1,
        reverse: 1
      },
      success: function (data) {
        $.each(data, function (id, view) {
          self.inbox.addView(new View(view, self.folder));
        });
      }
    });
  };

}(jQuery));
