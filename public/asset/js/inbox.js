/** Integration for INBOX logic. */
/*jslint browser: true, devel: true, indent: 2 */

var Inbox = {}, Templates = {}, instance;

(function ($) {
  "use strict";

  Templates.render = function (template, data) {
    if (!Templates[template]) {
      throw "Template " + template + " does not exist";
    }
    return Mustache.render(Templates[template], data);
  };

  Templates.folder =
         '<li class="{{classes}}">'
       + '{{name}}'
       + '<span class="unread">{{{unread}}}</span>'
       + '<ul class="children">'
       + '</ul>'
       + '</li>';

  Templates.thread =
        '<div class="{{classes}}">'
      + '{{#unseen}}'
      + '<div class="unseen">{{{unseen}}}</div>'
      + '{{/unseen}}'
      + '<div class="people">{{{persons}}}</div>'
      + '<div class="subject">{{subject}}</div>'
      + '<div class="date">{{{date}}}</div>'
      + '<p class="summary">{{{summary}}}</p>'
      + '</div>';

  Templates.person =
        '<span class="{{classes}}">'
      + '<img src="{{image}}" title="{{name}}"/>'
      + '</span>';

  /**
   * Constructor
   */
  Inbox = function () {
    this.jInbox          = $("#inbox");
    this.jFolders        = $("#folders");
    this.jSpecialFolders = $("#special-folders");
    this.jAllFolders     = $("#all-folders > li > ul");
    this.jView           = $("#viewpane");
    this.folders         = {};
    this.threads         = {};
  };

  /**
   * Tell if the object is an array
   */
  Inbox.isArray = function (value) {
      return '[object Array]' === Object.prototype.toString.call(value);
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
   * Render array of persons
   */
  Inbox.prototype.renderPersons = function (persons) {
    var out = [];
    if (!Inbox.isArray(persons)) {
      persons = [persons];
    }
    $.each(persons, function (key, person) {
      out.push(Templates.render("person", {
        classes: "person",
        image: "/public/asset/img/icons/person-32.png",
        name: person.name || person.mail
      }));
    });
    return out.join("");
  };

  /**
   * Refresh folder display
   */
  Inbox.prototype.addFolder = function (folder) {
    var jParent, jElement, parent;

    if (!folder.classes) {
      folder.classes = [];
    }
    folder.classes.push("folder");

    if (folder.parent && this.folders[folder.parent]) {
      parent = this.folders[folder.parent];
      parent.classes.push("parent");
      folder.classes.push("child");
      jParent = $(parent.children);
    } else {
      if (folder.special) {
        jParent = this.jSpecialFolders;
      } else {
        jParent = this.jAllFolders;
      }
    }

    if (folder.element) {
      // FIXME This will remove children as well => BAD
      $(folder.element).remove();
    }

    jElement = $(Templates.render("folder", {
      name:    folder.name,
      unread:  folder.unreadCount,
      classes: folder.classes.join(" ")
    }));
    folder.element  = jElement.get(0);
    folder.children = jElement.find('ul.children').get(0);

    jParent.append(jElement);

    this.folders[folder.path] = folder;
  };

  /**
   * Refresh thread display
   */
  Inbox.prototype.addThread = function (thread, folder) {
    var jElement;

    if (!thread.classes) {
      thread.classes = [];
    }
    thread.classes.push("thread");

    if (thread.element) {
      $(thread.element).remove();
    }

    jElement = $(Templates.render("thread", {
      persons: this.renderPersons(thread.persons),
      subject: thread.subject,
      date:    thread.lastUpdate,
      unseen:  thread.unseenCount,
      classes: thread.classes.join(" ")
    }));
    thread.element = jElement.get(0);

    this.jInbox.find(".content").append(jElement);
    this.threads[thread.id] = thread;
  };

  /**
   * Force refresh of folder list
   */
  Inbox.prototype.refreshFolders = function () {
    var self = this;
    Inbox.fetchJson(this.jFolders, {
      'url': '/folder',
      'success': function (data) {
        $.each(data, function (path, folder) {
          self.addFolder(folder);
        });
      }
    });
  };

  /**
   * Load folder content and display it
   */
  Inbox.prototype.loadFolder = function (folder) {
    var self = this;
    Inbox.fetchJson(this.jInbox, {
      'url': '/folder/' + folder.name + '/list',
      'success': function (data) {
        $.each(data, function (key, thread) {
          self.addThread(thread, folder);
        });
      }
    });
  };

  $("body").on('load', function () {
    instance = new Inbox();
    instance.refreshFolders();
  });

}(jQuery));
