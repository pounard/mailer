/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global Template, Inbox, inboxInstance */

var Folder;

(function ($) {
  "use strict";

  Folder = function (data, inbox) {
    var k = undefined;
    for (k in data) {
      if (data.hasOwnProperty(k)) {
        this[k] = data[k];
      }
    }
    this.inbox = inbox;
    this.element = undefined;
    this.children = undefined;
    this.classes = ["folder"];
    this.touch = undefined;
  };

  /**
   * Render the folder
   */
  Folder.prototype.render = function () {
    var $element;

    if (this.element) {
      // @todo Keep children aside for later
      $(this.element).remove();
    }

    $element = $(Template.render("folder", {
      name:    this.name,
      unseen:  this.unseen,
      recent:  this.recent,
      total:   this.total,
      classes: this.classes.join(" ")
    }));
    this.element  = $element.get(0);
    this.children = $element.find('ul.children').get(0);

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
  Folder.prototype.remove = function () {
    if (this.element) {
      $(this.element).remove();
    }
  };

  /**
   * Initialize behaviors
   */
  Folder.prototype.init = function () {
    var self = this;
    $(this.element).find("a").on("click", function () {
      self.load();
    });
  };

  /**
   * Load thread data
   */
  Folder.prototype.load = function () {
    var self = this;
    this.inbox.resetThreads();
    this.inbox.closePane();
    this.touch = new Date();
    this.inbox.dispatcher.fetchJson(this.inbox.$inbox, {
      url: 'api/thread/' + this.path,
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
  Folder.prototype.refresh = function () {
    var self = this, since;
    // This will force a check
    if (this.touch) {
      since = Math.round(this.touch.getTime() / 1000);
    } else {
      since = 0;
    }
    this.touch = new Date();

    this.inbox.dispatcher.fetchJson(this.inbox.$inbox, {
      url: 'api/thread/' + this.path,
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
