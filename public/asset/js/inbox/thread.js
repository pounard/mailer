/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global jQuery, Template, Inbox, inboxInstance, InboxObject, Mail */

var Thread;

(function ($) {
  "use strict";

  Thread = function Thread () {};
  Thread.prototype = new InboxObject();
  Thread.prototype.constructor = Thread;

  Thread.prototype.render = function () {
    var date = this.updated || this.created;
    if ("string" === typeof date) {
      date = Inbox.formatDate(date);
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
    return "api/thread/" + this.folder.path + '/' + this.getId();
  };

  Thread.prototype.getId = function () {
    return this.uid;
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

  Thread.prototype.getActions = function () {
    var self = this;
    return {
      "delete": {
        title: "Delete",
        type: "delete",
        url: this.getUrl(),
        success: function () {
          self.detach(true);
        }
      },
      refresh: {
        title: "Refresh",
        type: "get",
        url: this.getUrl(),
        success: function () {
          self.refresh(true);
        }
      }
    };
  };

  /**
   * Load thread data
   */
  Thread.prototype.loadMails = function () {
    var self = this;
    this.inbox.openThreadView(true);
    this.inbox.dispatcher.get({
      url: 'api/thread/' + this.folder.path + '/' + this.uid + '/mail',
      data: {
        complete: 1,
        reverse: 1
      },
      success: function (data) {
        $.each(data, function (id, child) {
          var mail = new Mail();
          child.folder = self.folder;
          child.thread = self;
          mail.init(child, self.inbox, [self]);
          self.inbox.addMail(mail);
          // Update the reply form
          self.inbox.currentThread = self;
          self.inbox.replyEnable(self);
        });
      }
    }, this.inbox.getViewContainer());
  };

}(jQuery));
