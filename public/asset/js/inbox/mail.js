/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global Template, Inbox, inboxInstance */

var Mail;

(function ($) {
  "use strict";

  Mail = function () {};
  Mail.prototype = new InboxObject();
  Mail.prototype.constructor = Mail;

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
    return "api/mail/" + this.folder.path + '/' + this.id;
  };

  Mail.prototype.getDefaultClasses = function () {
    var classes = ["mail"];
    if (this.unseen) {
      classes.push("mail-new");
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
    setTimeout(function () {
      //self.seen(true);
    }, 1000);
  };

  /**
   * Move this mail to trash
   */
  Mail.prototype.moveToTrash = function () {
    var self = this;
    /*
    this.inbox.dispatcher.fetchJson(this.element, {
      url: 'api/mail/' + this.uid + '/flag',
      data: {
        complete: 1,
        reverse: 1
      },
      success: function (data) {
        $.each(data, function (id, view) {
          self.inbox.addMail(new Mail(view, self.folder));
        });
      }
    });
     */
  };

  /**
   * Star or unstar this mail
   */
  Mail.prototype.star = function (toggle) {
    var self = this,
        action = toggle ? 'star' : 'unstar';
    this.inbox.dispatcher.fetchJson(this.element, {
      url: 'api/folder/' + this.folder.path + '/' + action + '/' + this.uid,
      success: function (data) {
        $.each(data, function () {
          if (toggle) {
            // Update element
          } else {
            // Update element
          }
        });
      }
    });
  };

  /**
   * Mark or unmark this mail as seen
   */
  Mail.prototype.seen = function (toggle) {
    var self = this,
    action = toggle ? 'seen' : 'unseen';
    this.inbox.dispatcher.fetchJson(null, {
      url: 'api/folder/' + this.folder.path + '/' + action + '/' + this.uid,
      success: function (data) {
        $.each(data, function () {
          if (toggle) {
            // Update element
          } else {
            // Update element
          }
        });
      }
    });
  };

}(jQuery));
