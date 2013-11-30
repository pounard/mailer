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
   * Send comment
   *
   * @param options
   *   Options for jQuery.ajax() call
   */
  Dispatcher.prototype.send = function (options) {
    $.extend(options, this.ajaxOptions);
    // We need at list an URL
    if (!options || !options.url) {
      throw "Options needs at least an URL";
    }
    options.url = this.basepath + options.url;
    $.ajax(options);
  };

  /**
   * Load content from AJAX and do something about it
   *
   * @param element
   *   DOM element or selector query
   * @param options
   *   Options for jQuery.ajax() call
   */
  Dispatcher.prototype.fetch = function (element, options) {
    var $element = $(element), complete;
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
    $element.addClass('loading');
    this.send(options);
  };

  /**
   * Load alias tailored for JSON requests
   *
   * @param element
   *   FROM element or selector query
   * @param options
   *   Options for jQuery.ajax() call
   */
  Dispatcher.prototype.fetchJson = function (element, options) {
    $.extend(options, this.jsonOptions);
    this.fetch(element, options);
  };

  /**
   * Send a post command
   *
   * @param options
   *   Options for jQuery.ajax() call
   * @param content
   *   Content to send
   */
  Dispatcher.prototype.postJson = function (options, content) {
    $.extend(options, this.jsonOptions);
    options.type = "post";
    if (!content) {
      throw "Cannot POST without content";
    }
    options.data = content;
    this.send(options);
  };

  /**
   * Send a patch command
   *
   * @param options
   *   Options for jQuery.ajax() call
   * @param content
   *   Content to send
   */
  Dispatcher.prototype.patchJson = function (options, content) {
    $.extend(options, this.jsonOptions);
    options.type = "patch";
    if (!content) {
      throw "Cannot PATCH without content";
    }
    options.data = content;
    this.send(options);
  };

}(jQuery));
