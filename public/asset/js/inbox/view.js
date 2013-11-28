/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global Template, Inbox, inboxInstance */

var View;

(function ($) {
  "use strict";

  /**
   * View is a complete thread
   */
  View = function (data, folder) {
    var k = undefined;
    for (k in data) {
      if (data.hasOwnProperty(k)) {
        this[k] = data[k];
      }
    }
    this.folder = folder;
    this.inbox = folder.inbox;

    this.classes = ["mail"];
    if (this.unseen) {
      this.classes.push("mail-new");
    }
    if (this.recent) {
        this.classes.push("mail-recent");
    }
    if (this.flagged) {
        this.classes.push("mail-flagged");
    }
    if (this.answered) {
        this.classes.push("mail-answered");
    }
  };

  /**
   * Render the folder
   */
  View.prototype.render = function () {

    var $element, date = undefined, body = undefined;

    if ("string" === typeof this.date) {
      date = new Date(Date.parse(this.date));
      date = [date.getDay(), date.getMonth(), date.getFullYear()].join("/");
    }

    if (this.element) {
      $(this.element).remove();
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

    $element = $(Template.render("mail", {
      persons: this.inbox.renderPersonImages([this.from]),
      from:    this.inbox.renderPersonLink(this.from),
      subject: this.subject,
      date:    date,
      classes: this.classes.join(" "),
      body:    body
    }));
    this.element = $element.get(0);

    this.init();

    return $element;
  };

  /**
   * Remove this folder if exists from the DOM
   *
   * Note that Folder class is not responsible for registration so you must
   * ensure the folder don't exist in the inbox registry anymore before
   * calling this
   */
  View.prototype.remove = function () {
    if (this.element) {
      $(this.element).remove();
    }
  };

  /**
   * Move this mail to trash
   */
  View.prototype.moveToTrash = function () {
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
          self.inbox.addView(new View(view, self.folder));
        });
      }
    });
     */
  };

  /**
   * Star or unstar this mail
   */
  View.prototype.star = function (toggle) {
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
  View.prototype.seen = function (toggle) {
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

  /**
   * Initialize behaviors
   */
  View.prototype.init = function () {
    var self = this;
    $(this.element).find("a.delete").on("click", function () {
      self.moveToTrash();
    });
    $(this.element).find("a.star").on("click", function () {
      self.star(!self.flagged);
    });
    setTimeout(function () {
      //self.seen(true);
    }, 1000);
  };

}(jQuery));
