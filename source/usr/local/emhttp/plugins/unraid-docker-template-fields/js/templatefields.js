/* Template Fields: drag to reorder, and switch off, the Path/Port/Variable/Label/Device
 * rows on Unraid's Add/Edit Container page.
 *
 * The page keeps each row as <div id="ConfigNum<n>"> holding hidden confName[],
 * confType[]... inputs, and posts them in DOM order, so moving a row moves it in
 * the saved template. A disabled row is saved with "Disabled:" in front of its
 * Type. Unraid's `docker create` builder only acts on Path, Port, Label, Variable
 * and Device, so the entry stays in the template but is left out of the container.
 * The prefix is added on submit only and taken off again when the template is
 * loaded, so the page's own edit dialog never sees it. */
(function ($) {
  'use strict';
  if (!$ || window.__templateFields) return;

  var opts = $.extend({ reorder: true, toggle: true }, window.TemplateFieldsConfig || {});
  var PREFIX = 'Disabled:';
  var LISTS = '#configLocation, #configLocationAdvanced';
  var ROW = 'div[id^="ConfigNum"]';
  var off = {};        // row id -> true while that field is disabled
  var pendingOff = {}; // ids of rows the loaded template has disabled, until the page draws them
  window.__templateFields = { off: off, pending: pendingOff }; // read-only view, for tests

  function typeInput(row) { return $(row).find('input[name="confType[]"]').first(); }
  function rows() { return $(LISTS).children(ROW); }

  /* Take the prefix off the loaded template before the page draws it, so it builds the right
   * kind of value field for the row, and tag the entry. The page hands each entry to
   * makeConfig() with the row number it is about to use, so that is where the tag is read. */
  function readTemplate() {
    if (typeof Settings === 'undefined' || !Settings || !Array.isArray(Settings.Config)) return;
    $.each(Settings.Config, function (i, c) {
      if (c && typeof c.Type === 'string' && c.Type.indexOf(PREFIX) === 0) {
        c.Type = c.Type.slice(PREFIX.length);
        c.__tfOff = true;
      }
    });
    var draw = window.makeConfig;
    if (typeof draw !== 'function' || draw.__tf) return;
    window.makeConfig = function (o) {
      var html = draw.apply(this, arguments);
      if (o && o.__tfOff && o.Number != null) pendingOff['ConfigNum' + o.Number] = true;
      return html;
    };
    window.makeConfig.__tf = true;
  }

  function changed() {
    // the same nudge the page gives after adding or removing a row
    $('input[name="contName"]').trigger('change');
  }

  function controls(hasSwitch) {
    var $c = $('<span class="tf-controls"></span>');
    if (opts.reorder) {
      $('<span class="tf-handle" tabindex="0" role="button"></span>')
        .attr({ title: 'Drag to reorder (or press the up and down arrow keys)', 'aria-label': 'Reorder this field' })
        .appendTo($c);
    }
    if (hasSwitch) {
      $('<span class="tf-switch" tabindex="0" role="switch"><span class="tf-knob"></span></span>').appendTo($c);
    }
    return $c;
  }

  /* Make one row match the state: dim it, sync the switch, and drop `required` so the
   * browser does not block saving because a field you switched off is empty. */
  function paint(row) {
    var isOff = !!off[row.id], $r = $(row);
    $r.toggleClass('tf-off', isOff);
    $r.children('.tf-controls').find('.tf-switch')
      .attr('aria-checked', isOff ? 'false' : 'true')
      .attr('title', isOff ? 'Disabled: left out of the container. Click to enable' : 'Enabled. Click to disable without deleting it');
    var $v = $r.find('[name="confValue[]"]').first();
    if (isOff) {
      if ($v.prop('required')) $v.attr('data-tf-required', '1').prop('required', false);
    } else if ($v.attr('data-tf-required')) {
      $v.prop('required', true).removeAttr('data-tf-required');
    }
  }

  function ensure(row) {
    if (pendingOff[row.id]) { off[row.id] = true; delete pendingOff[row.id]; }
    var $r = $(row);
    // The prefix can only be on a row's type when this script ran after the page drew it. Only look
    // the first time a row is seen: later on the prefix may just be a submit in flight.
    if (!$r.data('tfSeen')) {
      $r.data('tfSeen', true);
      var $t = typeInput(row);
      if ($t.length && $t.val().indexOf(PREFIX) === 0) { $t.val($t.val().slice(PREFIX.length)); off[row.id] = true; }
    }
    var hasSwitch = opts.toggle || !!off[row.id];
    var $c = $r.children('.tf-controls');
    if (!$c.length && (opts.reorder || hasSwitch)) $r.prepend(controls(hasSwitch));
    paint(row);
  }

  function setOff(row, value) {
    if (value) off[row.id] = true; else delete off[row.id];
    paint(row);
    changed();
  }

  function initSortable() {
    if (!opts.reorder || !$.fn.sortable) return;
    $(LISTS).each(function () {
      var $l = $(this);
      if ($l.data('tfSortable')) return;
      $l.data('tfSortable', true).sortable({
        items: '> ' + ROW,
        handle: '.tf-handle',
        axis: 'y',
        tolerance: 'pointer',
        cursor: 'grabbing',
        placeholder: 'tf-placeholder',
        forcePlaceholderSize: true,
        update: changed
      });
    });
  }

  /* The edit dialog rewrites a row's contents, or replaces the row when its Display
   * changes. Rows keep their id, so state is kept by id and put back on the new row. */
  function scan() {
    bindForm();
    rows().each(function () { ensure(this); });
    $.each(off, function (id) { if (!document.getElementById(id)) delete off[id]; });
    initSortable();
  }

  var bound = null;
  function bindForm() {
    var form = $(LISTS).first().closest('form')[0];
    if (!form || form === bound) return;
    bound = form;
    // The values are read right after the submit handlers return, so the prefix goes on
    // now and comes off again on the next tick in case the page stays open.
    form.addEventListener('submit', function () {
      var undo = [];
      rows().each(function () {
        if (!off[this.id]) return;
        var $t = typeInput(this), v = $t.val();
        if ($t.length && v.indexOf(PREFIX) !== 0) { $t.val(PREFIX + v); undo.push([$t, v]); }
      });
      setTimeout(function () { $.each(undo, function (_, u) { u[0].val(u[1]); }); }, 0);
    }, true);
  }

  function moveBy(row, dir) {
    var $row = $(row), $sib = dir < 0 ? $row.prevAll(ROW).first() : $row.nextAll(ROW).first();
    if (!$sib.length) return;
    if (dir < 0) $row.insertBefore($sib); else $row.insertAfter($sib);
    $row.children('.tf-controls').find('.tf-handle').trigger('focus');
    changed();
  }

  $(document)
    .on('click', '.tf-switch', function () {
      var row = $(this).closest(ROW)[0];
      if (row) setOff(row, !off[row.id]);
    })
    .on('keydown', '.tf-switch', function (e) {
      if (e.key !== ' ' && e.key !== 'Enter') return;
      e.preventDefault();
      $(this).trigger('click');
    })
    .on('keydown', '.tf-handle', function (e) {
      if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
      e.preventDefault();
      var row = $(this).closest(ROW)[0];
      if (row) moveBy(row, e.key === 'ArrowUp' ? -1 : 1);
    });

  // registered here, in <head>, so this runs before the page's own ready handler draws the rows
  $(function () {
    readTemplate();
    var lists = $(LISTS).get();
    if (window.MutationObserver) {
      var mo = new MutationObserver(scan);
      $.each(lists, function (_, l) { mo.observe(l, { childList: true, subtree: true }); });
    }
    scan();
  });
})(window.jQuery);
