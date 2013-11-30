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
   * Load content from AJAX and do something about it
   *
   * @param options
   *   Options for jQuery.ajax() call
   * @param element
   *   DOM element or selector query
   */
  Dispatcher.prototype.send = function (options, element) {
    var $element = $(element), complete;
    $.extend(options, this.ajaxOptions, this.jsonOptions);
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
    $element.addClass('loading');
    options.url = this.basepath + options.url;
    $.ajax(options);
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
    $.extend(options, this.jsonOptions);
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
    $.extend(options, this.jsonOptions);
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
    $.extend(options, this.jsonOptions);
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
    $.extend(options, this.jsonOptions);
    options.type = "patch";
    if (!content) {
      throw "Cannot PATCH without content";
    }
    options.data = content;
    this.send(options, element);
  };

}(jQuery));
