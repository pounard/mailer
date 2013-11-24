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

    var $element, date = undefined;

    if ("string" === typeof this.date) {
      date = new Date(Date.parse(this.date));
      date = [date.getDay(), date.getMonth(), date.getFullYear()].join("/");
    }

    if (this.element) {
      $(this.element).remove();
    }

    $element = $(Template.render("mail", {
      persons: this.inbox.renderPersonImages([this.from]),
      from:    this.inbox.renderPersonLink(this.from),
      subject: this.subject,
      date:    date,
      classes: this.classes.join(" "),
      body:    this.bodyPlainFiltered || this.bodyPlain
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
   * Initialize behaviors
   */
  View.prototype.init = function () {
    // @todo
  };

}(jQuery));
