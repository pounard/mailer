/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, indent: 2 */

var Inbox = {}, instance;

(function ($) {
  "use strict";

  /**
   * Constructor
   */
  Inbox = function () {
    // HTML elements we will manipulate the most
    this.jInbox   = $("#inbox");
    this.jFolders = $("#folders");
    this.jView    = $("#viewpane");
  };

  /**
   * Load content from AJAX and do something about it
   *
   * @param element
   *   DOM element or selector query
   * @param options
   *   Object containing variou jQuery.ajax options
   */
  Inbox.load = function (element, options) {
    var jElement = $(element), complete;
    // We need at list an URL
    if (!options || !options.url) {
      throw "Options needs at least an URL";
    }
    options.async = options.async || true;
    options.cache = options.cache || false;
    if (options.complete) {
      complete = options.complete;
    }
    options.complete = function () {
      if ("function" === typeof complete) {
        complete();
      }
      jElement.removeClass('loading');
    };
    jElement.addClass('loading');
    $.ajax(options);
  };

  /**
   * Load alias tailored for JSON requests
   */
  Inbox.fetchJson = function (element, options) {
    options = options || {};
    options.async = true;
    options.cache = false;
    options.contentType = "application/json";
    options.dataType = "json";
    Inbox.load(element, options);
  };

  /**
   * Force refresh of folder list
   */
  Inbox.prototype.refreshFolders = function () {
    var self = this;
    // We don't need this to flicker for displaying the same thing
    Inbox.fetchJson(this.jFolders, {
      'url': '/folder',
      'success': function (data) {
        $.each(data, function (path, folder) {
          self.jFolders.append(path + "<br/>");
        });
      }
    });
  };

  /**
   * Load folder content and display it
   */
  Inbox.prototype.loadFolder = function (folder) {

    var jInbox = $("#inbox");

    jInbox.html(""); // Start by emptying content

    Inbox.load(jInbox, {
      //
    });
  };

  $("body").on('load', function () {
    instance = new Inbox();
    instance.refreshFolders();
  });

}(jQuery));
