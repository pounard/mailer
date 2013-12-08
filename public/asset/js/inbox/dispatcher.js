/*jslint browser: true, devel: true, todo: true, indent: 2 */

var Dispatcher;

(function ($) {
  "use strict";

  /**
   * Server connector
   *
   * @param options
   *   Object containing server options
   */
  Dispatcher = function (options) {
    options = options || {};

    this.pipeline = false;
    this.commands = [];

    // Server parameters
    this.basepath = options.basepath || "/";

    // Default options for jQuery.ajax() operations
    this.ajaxOptions = {
      async: true,
      cache: false
    };

    // Default JSON options for jQuery.ajax() operations
    this.jsonOptions = {
      contentType: "application/json",
      dataType: "json"
    };
  };

  /**
   * Get real URL from path
   *
   * @param path
   *
   * @return string
   */
  Dispatcher.prototype.pathToUrl = function (path) {
    return this.basepath + path;
  };

  /**
   * Set the dispatcher into pipeline mode
   */
  Dispatcher.prototype.start = function (reset) {
    Inbox.debug("Starting pipeline");
    this.pipeline = true;
    if (reset && this.commands.length) {
      this.commands = [];
    }
  };

  /**
   * Cancel current pipeline
   */
  Dispatcher.prototype.cancel = function () {
    Inbox.debug("Canceling pipeline");
    if (this.commands.length) {
      this.commands = [];
    }
    this.pipeline = false;
  };

  /**
   * Send all pipelined commands and reset state
   */
  Dispatcher.prototype.exec = function () {
    var k = 0;
    Inbox.debug("Exec pipeline");
    if (this.commands.length) {
      for (k in this.commands) {
        $.ajax(this.commands[k]);
      }
    }
    this.commands = [];
    this.pipeline = false;
  };

  /**
   * Get options for ajax queries
   *
   * @param options
   */
  Dispatcher.prototype.getOptions = function (options) {
    options = options || {};
    return $.extend(options, this.ajaxOptions, this.jsonOptions);
  };

  /**
   * Load content from AJAX and do something about it
   *
   * @param options
   *   Options for jQuery.ajax() call
   * @param element
   *   DOM element or selector query
   */
  Dispatcher.prototype.send = function (options, element) {
    var $element = $(element), complete, success;
    options = this.getOptions(options);
    // We need at list an URL
    if (!options || !options.url) {
      throw "Options needs at least an URL";
    }
    $element.show();
    // Complete option will allow us to remove the
    // loader whatever happens good or bad
    if (options.complete) {
      complete = options.complete;
    }
    options.complete = function (jqXhr, textStatus) {
      if ("function" === typeof complete) {
        complete(jqXhr, textStatus);
      }
      $element.removeClass('loading');
    };
    if (options.success) {
      success = options.success;
    }
    options.success = function (data) {
      if ("function" === typeof success) {
        if (data.data) {
          success(data.data);
        } else {
          success();
        }
      }
      // @todo Check for message and add them.
      $element.removeClass('loading');
    };
    $element.addClass('loading');
    options.url = this.pathToUrl(options.url);
    if (this.pipeline) {
      this.commands.push(options);
    } else {
      $.ajax(options);
    }
  };

  /**
   * Send a DELETE request
   *
   * @param element
   *   FROM element or selector query
   * @param options
   *   Options for jQuery.ajax() call
   */
  Dispatcher.prototype.del = function (options, element) {
    options = options || {};
    options.type = "delete";
    this.send(options, element);
  };

  /**
   * Send a GET request
   *
   * @param element
   *   FROM element or selector query
   * @param options
   *   Options for jQuery.ajax() call
   */
  Dispatcher.prototype.get = function (options, element) {
    this.send(options, element);
  };

  /**
   * Send a POST request
   *
   * @param options
   *   Options for jQuery.ajax() call
   * @param content
   *   Content to send
   */
  Dispatcher.prototype.post = function (options, content, element) {
    options = options || {};
    options.type = "post";
    if (!content) {
      throw "Cannot POST without content";
    }
    options.data = content;
    this.send(options, element);
  };

  /**
   * Send a PATCH request
   *
   * @param options
   *   Options for jQuery.ajax() call
   * @param content
   *   Content to send
   */
  Dispatcher.prototype.patch = function (options, content, element) {
    options = options || {};
    options.type = "patch";
    if (!content) {
      throw "Cannot PATCH without content";
    }
    options.data = content;
    this.send(options, element);
  };

}(jQuery));
