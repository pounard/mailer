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
   * @param element
   *   DOM element or selector query
   * @param options
   *   Options for jQuery.ajax() call
   */
  Dispatcher.prototype.fetch = function (element, options) {
    var $element = $(element), complete;
    $element.show();
    $.extend(options, this.ajaxOptions);
    // We need at list an URL
    if (!options || !options.url) {
      throw "Options needs at least an URL";
    }
    options.url = this.basepath + options.url;
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
    $.ajax(options);
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

}(jQuery));
