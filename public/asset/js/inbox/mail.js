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
    if ("string" === typeof this.date) {
      date = new Date(Date.parse(this.date));
      date = [date.getDay(), date.getMonth(), date.getFullYear()].join("/");
    }
    // Compute a few classes

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
    if (this.unseen) {
      classes.push("mail-new");
    }
    if (this.unseen) {
      classes.push("mail-deleted");
    }
    if (this.recent) {
      classes.push("mail-recent");
    }
    if (this.flagged) {
      classes.push("mail-flagged");
    }
    if (this.answered) {
      classes.push("mail-answered");
    }
    return classes;
  };

  Mail.prototype.attachEvents = function (context) {
    var self = this;
    $(context).find("a.delete").on("click", function () {
      self.moveToTrash();
    });
    $(context).find("a.star").on("click", function () {
      self.star(!self.flagged);
    });
    if (!this.isSeen) {
      setTimeout(function () {
        self.seen(true);
      }, 1000);
    }
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
      success: function (data) {
        $.each(data, function () {
          if (toggle) {
            self.removeClass("mail-flagged");
          } else {
            self.addClass("mail-flagged");
          }
          self.change();
        });
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
      success: function (data) {
        $.each(data, function () {
          if (toggle) {
            self.removeClass("mail-new");
            self.removeClass("mail-recent");
          } else {
            self.addClass("mail-new");
            self.addClass("mail-recent");
          }
          self.change(false);
        });
      }
    }, {
      seen: toggle
    });
  };

}(jQuery));
