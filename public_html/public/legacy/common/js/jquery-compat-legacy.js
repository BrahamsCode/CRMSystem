/*!
 * CRM compatibility layer: code written for jQuery 1.2-1.8 running on jQuery 3.7 + jQuery Migrate 3.
 * jQuery Migrate 3 restores what 3.0 removed (.size, .andSelf, event .load/.unload/.error);
 * this restores what 1.9 removed and the screens and old plugins of this repository still use:
 *   $.browser, .live()/.die(), .selector/.context, $.curCSS, $.boxModel, $.attrFn
 * Each use warns in the console with the prefix "JQMIGRATE (legacy):" so it can be fixed at the source.
 * Part of the migration to PHP 8.5.12 (see DEPENDENCIES.md).
 */
(function ($) {
  'use strict';
  if (!$ || !$.fn) {
    return;
  }

  var warned = {};
  function warnOnce(key, text) {
    if (!warned[key] && window.console && console.warn) {
      warned[key] = true;
      console.warn('JQMIGRATE (legacy): ' + text);
    }
  }

  // $.browser (removed in 1.9): computed from the userAgent, same as jQuery 1.8
  if (!$.browser) {
    var ua = navigator.userAgent.toLowerCase();
    var m = /(edg)[ \/]([\w.]+)/.exec(ua) ||
      /(chrome)[ \/]([\w.]+)/.exec(ua) ||
      /(webkit)[ \/]([\w.]+)/.exec(ua) ||
      /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(ua) ||
      /(msie) ([\w.]+)/.exec(ua) ||
      (ua.indexOf('compatible') < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(ua)) || [];
    var browser = {};
    if (m[1]) {
      browser[m[1]] = true;
      browser.version = m[2] || '0';
    }
    if (browser.chrome || browser.edg) {
      browser.webkit = true;
    } else if (browser.webkit) {
      browser.safari = true;
    }
    $.browser = browser;
  }

  // .selector / .context (removed in 3.0): .live() needs them
  var initOriginal = $.fn.init;
  $.fn.init = function (selector, context, root) {
    var r = new initOriginal(selector, context, root);
    if (typeof selector === 'string') {
      r.selector = selector;
      r.context = context && context.nodeType ? context : document;
    }
    return r;
  };
  $.fn.init.prototype = $.fn;

  // .live() / .die() (removed in 1.9) -> delegation with .on() / .off()
  $.fn.live = function (types, data, fn) {
    warnOnce('live', '.live() was removed in jQuery 1.9; use $(document).on(event, selector, fn)');
    if (this.selector) {
      $(this.context || document).on(types, this.selector, data, fn);
    }
    return this;
  };
  $.fn.die = function (types, fn) {
    warnOnce('die', '.die() was removed in jQuery 1.9; use $(document).off(event, selector, fn)');
    if (this.selector) {
      $(this.context || document).off(types, this.selector, fn);
    }
    return this;
  };

  // Internal utilities used by old plugins (jQuery UI 1.7/1.8, lightbox...)
  if (!$.curCSS) {
    $.curCSS = function (elem, name) {
      warnOnce('curCSS', '$.curCSS() was removed; use $.css()');
      return $.css(elem, name);
    };
  }
  if ($.boxModel === undefined) {
    $.boxModel = document.compatMode === 'CSS1Compat';
  }
  if (!$.attrFn) {
    $.attrFn = {};
  }

  // Unquoted attribute values with brackets, input[name=month[]] (changed in 1.4): jQuery 1.3 accepted them,
  // jQuery 3 throws a syntax error and the handler stops (the rank schedule could not be saved). On that error the
  // selector is retried with the values quoted, with a warning; valid selectors never reach the retry.
  var findOriginal = $.find;
  var unquotedAttr = /\[\s*([\w-]+)\s*([~|^$*!]?=)\s*([^'"\[\]\s]+(?:\[[^\]]*\])+)\s*\]/g;
  $.find = function (selector) {
    try {
      return findOriginal.apply(this, arguments);
    } catch (e) {
      if (typeof selector !== 'string' || !unquotedAttr.test(selector)) {
        throw e;
      }
      unquotedAttr.lastIndex = 0;
      var args = Array.prototype.slice.call(arguments);
      args[0] = selector.replace(unquotedAttr, '[$1$2"$3"]');
      warnOnce('selector-' + selector, 'unquoted attribute value in selector "' + selector + '"; quote it: ' + args[0]);
      return findOriginal.apply(this, args);
    }
  };
  for (var findProp in findOriginal) {
    if (Object.prototype.hasOwnProperty.call(findOriginal, findProp)) {
      $.find[findProp] = findOriginal[findProp];
    }
  }

  // JSON responses (changed in 1.4): jQuery 1.3 evaluated them, so endpoints answering {'key' : 'value'} worked;
  // jQuery 3 uses JSON.parse, rejects them and the success callback never runs (the zip search, for example).
  // Strict parsing first; only if it fails, the old evaluation, with a warning naming the URL to fix at the source.
  $.ajaxSetup({
    converters: {
      'text json': function (text) {
        try {
          return JSON.parse(text);
        } catch (e) {
          if (window.console && console.warn) {
            console.warn('JQMIGRATE (legacy): invalid JSON evaluated as in jQuery 1.3; fix the response of ' + (this && this.url ? this.url : 'an AJAX call'));
          }
          return (new Function('return (' + text + ');'))();
        }
      }
    }
  });

  // .attr('value') / .attr('checked'|'selected'|'disabled'|'readonly') on form controls (changed in 1.9):
  // jQuery 1.3-1.8 read and wrote the live property, so .attr('value') returned what the user typed and
  // .attr('checked', true) checked a box the user had touched. jQuery 3 reads and writes the HTML attribute,
  // which only holds the initial state: the zip search sent an empty code and checks did nothing, with no error.
  // Restores the jQuery 1.3.2 behaviour (live value, true/false), which the screens compare with "== true".
  var formControl = /^(input|select|textarea|option|button)$/i;
  $.attrHooks.value = {
    get: function (elem) {
      if (formControl.test(elem.nodeName)) {
        warnOnce('attr-value', ".attr('value') reads the typed value as in jQuery 1.8; use .val()");
        return elem.value;
      }
      return null;
    },
    set: function (elem, value) {
      if (formControl.test(elem.nodeName)) {
        warnOnce('attr-value-set', ".attr('value', x) sets the live value as in jQuery 1.8; use .val(x)");
        elem.value = value;
      }
      // undefined: jQuery also writes the attribute, as before
    }
  };
  $.each(['checked', 'selected', 'disabled', 'readonly'], function (_, name) {
    var prop = name === 'readonly' ? 'readOnly' : name;
    $.attrHooks[name] = {
      get: function (elem) {
        if (formControl.test(elem.nodeName)) {
          warnOnce('attr-' + name, ".attr('" + name + "') reads the live state as in jQuery 1.8; use .prop('" + name + "')");
          return !!elem[prop];
        }
        return null;
      },
      set: function (elem, value) {
        var on = value !== false && value !== '' && value !== 0;
        if (formControl.test(elem.nodeName)) {
          warnOnce('attr-' + name + '-set', ".attr('" + name + "', x) sets the live state as in jQuery 1.8; use .prop('" + name + "', x)");
          elem[prop] = on;
        }
        if (on) {
          elem.setAttribute(name, name);
        } else {
          elem.removeAttribute(name);
        }
        return name;
      }
    };
  });
})(window.jQuery);
