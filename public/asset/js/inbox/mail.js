/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global Template, Inbox, inboxInstance */

var Mail;

(function ($) {
  "use strict";

  Mail = function Mail () {};
  Mail.prototype = new InboxObject();
  Mail.prototype.constructor = Mail;
  Mail.prototype.parent = InboxObject.prototype;

  Mail.prototype.render = function () {
    var date = undefined, body = undefined;

    if ("string" === typeof this.created) {
      date = Inbox.formatDate(this.created, true);
    }

    // Which body to display: using
    // this.bodyPlain || this.bodyHtml || this.summary
    // cannot work because they are arrays
    if (this.bodyPlain.length) {
      body = this.bodyPlain;
    } else if (this.bodyHtml.length) {
      body = this.bodyHtml;
    } else {
      body = this.summary;
    }

    return Template.render("mail", {
      persons: this.inbox.renderPersonImages([this.from]),
      from:    this.inbox.renderPersonLink(this.from),
      subject: this.subject,
      date:    date,
      classes: this.classes.join(" "),
      body:    body
    });
  };

  Mail.prototype.getUrl = function () {
    return "api/mail/" + this.folder.path + '/' + this.getId();
  };

  Mail.prototype.getId = function () {
    return this.uid;
  };

  Mail.prototype.getDefaultClasses = function () {
    var classes = ["mail"];
    if (!this.isSeen) {
      classes.push("mail-new");
    }
    if (this.isDeleted) {
      classes.push("mail-deleted");
    }
    if (this.isRecent) {
      classes.push("mail-recent");
    }
    if (this.isFlagged) {
      classes.push("mail-flagged");
    }
    if (this.isAnswered) {
      classes.push("mail-answered");
    }
    return classes;
  };

  Mail.prototype.attachEvents = function (context) {
    var self = this;
    $(context).find("a.delete").on("click", function () {
      self.moveToTrash();
    });
    $(context).find(".star > a").on("click", function () {
      self.star(!self.isFlagged);
    });
    if (!this.isSeen) {
      setTimeout(function () {
        self.seen(true);
      }, 1000);
    }
  };

  Mail.prototype.getActions = function () {
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
      },
      read: {
        title: "Mark as read",
        type: "get",
        url: this.getUrl(),
        success: function () {
          self.refresh(true);
        }
      },
      star: {
        title: "Star",
        type: "get",
        url: this.getUrl(),
        success: function () {
          self.refresh(true);
        }
      }
    };
  };

  /**
   * Move this mail to trash
   */
  Mail.prototype.moveToTrash = function () {
    var self = this;
    this.inbox.dispatcher.del({
      url: this.getUrl(),
      success: function () {
        self.inbox.unregister(self, true);
      }
    }, this.element);
  };

  /**
   * Star or unstar this mail
   */
  Mail.prototype.star = function (toggle) {
    var self = this;
    this.inbox.dispatcher.patch({
      url: this.getUrl(),
      success: function () {
        self.isFlagged = toggle;
        if (toggle) {
          self.addClass("mail-flagged");
        } else {
          self.removeClass("mail-flagged");
        }
        self.change();
      }
    }, {
      flagged: toggle
    });
  };

  /**
   * Mark or unmark this mail as seen
   */
  Mail.prototype.seen = function (toggle) {
    var self = this;
    this.inbox.dispatcher.patch({
      url: this.getUrl(),
      success: function () {
        if (toggle) {
          self.removeClass("mail-new");
          self.removeClass("mail-recent");
        } else {
          self.addClass("mail-new");
          self.addClass("mail-recent");
        }
        self.change(false);
      }
    }, {
      seen: toggle
    });
  };

}(jQuery));
