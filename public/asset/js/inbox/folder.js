/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, todo: true, indent: 2 */
/*global Template, Inbox, inboxInstance */

var Folder;

(function ($) {
  "use strict";

  Folder = function (data, inbox) {
    this.name = data.name;
    this.parent = data.parent || undefined;
    this.lastUpdate = new Date(data.lastUpdate);
    this.messageCount = data.messageCount || 0;
    this.recentCount = data.recentCount || 0;
    this.unreadCount = data.unreadCount || 0;
    this.delimiter = data.delimiter || ".";
    this.path = data.path || this.name;
    this.inbox = inbox || inboxInstance;
    this.special = data.special || false;
    this.threads = {};
    this.element = undefined;
    this.classes = ["folder"];
    this.touch = undefined;
    // Magic happens from there
    this.render();
    this.init();
  };

  /**
   * Render the folder
   */
  Folder.prototype.render = function () {
    var $container, $element;

    $container = this.inbox.getFolderContainer(this);

    if (this.element) {
      // @todo Keep children aside for later
      $(this.element).remove();
    }

    $element = $(Template.render("folder", {
      name:    this.name,
      unread:  this.unreadCount,
      total:   this.messageCount,
      classes: this.classes.join(" ")
    }));
    this.element  = $element.get(0);
    this.children = $element.find('ul.children').get(0);

    $container.append($element);
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
    this.touch = new Date();
    this.inbox.dispatcher.fetchJson(this.inbox.$inbox, {
      url: 'folder/' + this.path + '/list',
      success: function (data) {
        $.each(data, function (id, thread) {
          self.inbox.addThread(thread, self);
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
      url: 'folder/' + this.path + '/list',
      since: since,
      success: function (data) {
        if (data.threads) {
          $.each(data.threads, function (id, thread) {
            self.inbox.addThread(thread, self);
          });
        }
      }
    });
  };

}(jQuery));
