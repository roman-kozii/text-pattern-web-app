/* ========================================================================
 * Bootstrap: dropdown.js v3.4.1
 * https://getbootstrap.com/docs/3.4/javascript/#dropdowns
 * ========================================================================
 * Copyright 2011-2019 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */
!function(i){"use strict";function a(t){i(t).on("click.bs.dropdown",this.toggle)}var o='[data-toggle="dropdown"]';function s(t){var e=t.attr("data-target"),e="#"!==(e=e||(e=t.attr("href"))&&/#[A-Za-z]/.test(e)&&e.replace(/.*(?=#[^\s]*$)/,""))?i(document).find(e):null;return e&&e.length?e:t.parent()}function r(a){a&&3===a.which||(i(".dropdown-backdrop").remove(),i(o).each(function(){var t=i(this),e=s(t),n={relatedTarget:this};!e.hasClass("open")||a&&"click"==a.type&&/input|textarea/i.test(a.target.tagName)&&i.contains(e[0],a.target)||(e.trigger(a=i.Event("hide.bs.dropdown",n)),a.isDefaultPrevented())||(t.attr("aria-expanded","false"),e.removeClass("open").trigger(i.Event("hidden.bs.dropdown",n)))}))}a.VERSION="3.4.1",a.prototype.toggle=function(t){var e=i(this);if(!e.is(".disabled, :disabled")){var n=s(e),a=n.hasClass("open");if(r(),!a){"ontouchstart"in document.documentElement&&!n.closest(".navbar-nav").length&&i(document.createElement("div")).addClass("dropdown-backdrop").insertAfter(i(this)).on("click",r);a={relatedTarget:this};if(n.trigger(t=i.Event("show.bs.dropdown",a)),t.isDefaultPrevented())return;e.trigger("focus").attr("aria-expanded","true"),n.toggleClass("open").trigger(i.Event("shown.bs.dropdown",a))}return!1}},a.prototype.keydown=function(t){if(/(38|40|27|32)/.test(t.which)&&!/input|textarea/i.test(t.target.tagName)){var e=i(this);if(t.preventDefault(),t.stopPropagation(),!e.is(".disabled, :disabled")){var n=s(e),a=n.hasClass("open");if(!a&&27!=t.which||a&&27==t.which)return 27==t.which&&n.find(o).trigger("focus"),e.trigger("click");a=n.find(".dropdown-menu li:not(.disabled):visible a");a.length&&(e=a.index(t.target),38==t.which&&0<e&&e--,40==t.which&&e<a.length-1&&e++,a.eq(e=~e?e:0).trigger("focus"))}}};var t=i.fn.dropdown;i.fn.dropdown=function(n){return this.each(function(){var t=i(this),e=t.data("bs.dropdown");e||t.data("bs.dropdown",e=new a(this)),"string"==typeof n&&e[n].call(t)})},i.fn.dropdown.Constructor=a,i.fn.dropdown.noConflict=function(){return i.fn.dropdown=t,this},i(document).on("click.bs.dropdown.data-api",r).on("click.bs.dropdown.data-api",".dropdown form",function(t){t.stopPropagation()}).on("click.bs.dropdown.data-api",o,a.prototype.toggle).on("keydown.bs.dropdown.data-api",o,a.prototype.keydown).on("keydown.bs.dropdown.data-api",".dropdown-menu",a.prototype.keydown)}(jQuery),function(i){"use strict";function o(t,e){this.$element=i(t),this.options=i.extend({},o.DEFAULTS,e),this.$trigger=i('[data-toggle="collapse"][href="#'+t.id+'"],[data-toggle="collapse"][data-target="#'+t.id+'"]'),this.transitioning=null,this.options.parent?this.$parent=this.getParent():this.addAriaAndCollapsedClass(this.$element,this.$trigger),this.options.toggle&&this.toggle()}function n(t){t=t.attr("data-target")||(t=t.attr("href"))&&t.replace(/.*(?=#[^\s]+$)/,"");return i(document).find(t)}function s(a){return this.each(function(){var t=i(this),e=t.data("bs.collapse"),n=i.extend({},o.DEFAULTS,t.data(),"object"==typeof a&&a);!e&&n.toggle&&/show|hide/.test(a)&&(n.toggle=!1),e||t.data("bs.collapse",e=new o(this,n)),"string"==typeof a&&e[a]()})}o.VERSION="3.4.1",o.TRANSITION_DURATION=350,o.DEFAULTS={toggle:!0},o.prototype.dimension=function(){return this.$element.hasClass("width")?"width":"height"},o.prototype.show=function(){if(!this.transitioning&&!this.$element.hasClass("in")){var t=this.$parent&&this.$parent.children(".panel").children(".in, .collapsing");if(!(t&&t.length&&(a=t.data("bs.collapse"))&&a.transitioning)){var e=i.Event("show.bs.collapse");if(this.$element.trigger(e),!e.isDefaultPrevented()){t&&t.length&&(s.call(t,"hide"),a||t.data("bs.collapse",null));var n=this.dimension(),e=(this.$element.removeClass("collapse").addClass("collapsing")[n](0).attr("aria-expanded",!0),this.$trigger.removeClass("collapsed").attr("aria-expanded",!0),this.transitioning=1,function(){this.$element.removeClass("collapsing").addClass("collapse in")[n](""),this.transitioning=0,this.$element.trigger("shown.bs.collapse")});if(!i.support.transition)return e.call(this);var a=i.camelCase(["scroll",n].join("-"));this.$element.one("bsTransitionEnd",i.proxy(e,this)).emulateTransitionEnd(o.TRANSITION_DURATION)[n](this.$element[0][a])}}}},o.prototype.hide=function(){if(!this.transitioning&&this.$element.hasClass("in")){var t=i.Event("hide.bs.collapse");if(this.$element.trigger(t),!t.isDefaultPrevented()){var t=this.dimension(),e=(this.$element[t](this.$element[t]())[0].offsetHeight,this.$element.addClass("collapsing").removeClass("collapse in").attr("aria-expanded",!1),this.$trigger.addClass("collapsed").attr("aria-expanded",!1),this.transitioning=1,function(){this.transitioning=0,this.$element.removeClass("collapsing").addClass("collapse").trigger("hidden.bs.collapse")});if(!i.support.transition)return e.call(this);this.$element[t](0).one("bsTransitionEnd",i.proxy(e,this)).emulateTransitionEnd(o.TRANSITION_DURATION)}}},o.prototype.toggle=function(){this[this.$element.hasClass("in")?"hide":"show"]()},o.prototype.getParent=function(){return i(document).find(this.options.parent).find('[data-toggle="collapse"][data-parent="'+this.options.parent+'"]').each(i.proxy(function(t,e){e=i(e);this.addAriaAndCollapsedClass(n(e),e)},this)).end()},o.prototype.addAriaAndCollapsedClass=function(t,e){var n=t.hasClass("in");t.attr("aria-expanded",n),e.toggleClass("collapsed",!n).attr("aria-expanded",n)};var t=i.fn.collapse;i.fn.collapse=s,i.fn.collapse.Constructor=o,i.fn.collapse.noConflict=function(){return i.fn.collapse=t,this},i(document).on("click.bs.collapse.data-api",'[data-toggle="collapse"]',function(t){var e=i(this),t=(e.attr("data-target")||t.preventDefault(),n(e)),e=t.data("bs.collapse")?"toggle":e.data();s.call(t,e)})}(jQuery),/** global: localStorage */
function(){"use strict";var e=document.body.classList,t=document.getElementById("lightswitch");null!==t&&t.addEventListener("click",function(t){e.contains("darkmode")?(e.remove("darkmode"),localStorage.setItem("prefers-color-scheme","light")):(e.add("darkmode"),localStorage.setItem("prefers-color-scheme","dark")),t.preventDefault()})}();