/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global Template, Inbox, inboxInstance */

var Thread;

(function ($) {
  "use strict";

  Thread = function (data, folder) {
    var k = undefined;
    for (k in data) {
      if (data.hasOwnProperty(k)) {
        this[k] = data[k];
      }
    }
    this.folder = folder;
    this.inbox = folder.inbox;
    this.classes = ["thread"];
  };

  /**
   * Render the folder
   */
  Thread.prototype.render = function () {
    var $element, date = this.updated || this.created;

    if ("string" === typeof date) {
      date = new Date(Date.parse(date));
      date = [date.getDay(), date.getMonth(), date.getFullYear()].join("/");
    }

    if (this.element) {
      $(this.element).remove();
    }

    $element = $(Template.render("thread", {
      persons: this.inbox.renderPersonImages(this.persons),
      subject: this.subject,
      date:    date,
      total:   this.total,
      recent:  this.recent,
      unseen:  this.unseen,
      classes: this.classes.join(" "),
      summary: this.summary
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
  Thread.prototype.remove = function () {
    if (this.element) {
      $(this.element).remove();
    }
  };

  /**
   * Initialize behaviors
   */
  Thread.prototype.init = function () {
    var self = this;
    $(this.element).find("a").on("click", function () {
      self.load();
    });
  };

  /**
   * Load thread data
   */
  Thread.prototype.load = function () {
    var self = this;
    this.inbox.openThreadView(true);
    this.inbox.dispatcher.fetchJson(this.inbox.getViewContainer(), {
      url: 'folder/' + this.folder.path + '/thread/' + this.uid,
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
