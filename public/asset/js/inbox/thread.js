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

    // Magic happens from there
    this.render();
    this.init();
  };

  /**
   * Render the folder
   */
  Thread.prototype.render = function () {
    var $element, $container, date = this.lastUpdate || this.startDate;

    $container = this.inbox.getInboxContainer();

    if (!this.classes) {
      this.classes = [];
    }
    this.classes.push("thread");

    if ("string" === typeof date) {
      date = new Date(date);
      date = [date.getDay(), date.getMonth(), date.getFullYear()].join("/");
    }

    if (this.element) {
      $(this.element).remove();
    }

    $element = $(Template.render("thread", {
      persons: this.inbox.renderPersons(this.persons),
      subject: this.subject,
      date:    date,
      unseen:  this.unseenCount,
      classes: this.classes.join(" ")
    }));
    this.element = $element.get(0);

    $container.append($element);
    this.inbox.threads[this.id] = this;
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
    this.inbox.dispatcher.fetchJson(this.inbox.getViewContainer(true), {
      url: 'thread/' + this.id,
      success: function (data) {
        new View(data, self.folder);
      }
    });
  };

}(jQuery));
