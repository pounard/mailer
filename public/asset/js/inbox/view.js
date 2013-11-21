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
  };

  /**
   * Render the folder
   */
  View.prototype.render = function () {
    this.inbox.refreshView(this);
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
