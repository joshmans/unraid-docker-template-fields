<script>
/* Runs against the mock page with ?selftest=1 and prints PASS/FAIL lines; the page title ends in PASS or FAIL. */
(function () {
  var out = [], fails = 0;
  function check(label, ok, detail) {
    if (!ok) fails++;
    out.push((ok ? 'ok   ' : 'FAIL ') + label + (!ok && detail !== undefined ? '  -> ' + JSON.stringify(detail) : ''));
  }
  var form = document.querySelector('form');

  // what the browser would send, taken after the plugin's submit handler and before the page navigates
  function snapshot() {
    var data = null;
    var h = function (e) { e.preventDefault(); data = new FormData(form); };
    form.addEventListener('submit', h);
    form.requestSubmit();
    form.removeEventListener('submit', h);
    return data;
  }
  function names() { return snapshot().getAll('confName[]'); }
  function types() { return snapshot().getAll('confType[]'); }
  function row(n) { return $('#ConfigNum' + n).filter(function () { return this.parentNode.id.indexOf('configLocation') === 0; }); }
  // waits for the plugin's mutation handling, then a little longer so a snapshot's undo (setTimeout 0) has run
  function tick(fn) { return new Promise(function (res, rej) { setTimeout(function () { try { fn(); } catch (e) { return rej(e); } setTimeout(res, 15); }, 30); }); }

  // the page draws rows with ids 1,3,5,7,9,11 (see the double increment of confNum)
  var ID = { web: 1, debug: 3, config: 5, tz: 7, mode: 9, tag: 11 };

  Promise.resolve().then(function () {
    return tick(function () {
      check('basic list has 3 rows', $('#configLocation').children('div[id^=ConfigNum]').length === 3);
      check('advanced list has 3 rows', $('#configLocationAdvanced').children('div[id^=ConfigNum]').length === 3);
      check('every row has one drag handle and one switch', $('div[id^=ConfigNum]').filter(function () { return this.parentNode.id.indexOf('configLocation') === 0; }).toArray().every(function (r) {
        return $(r).children('.tf-controls').length === 1 && $(r).find('> .tf-controls .tf-handle').length === 1 && $(r).find('> .tf-controls .tf-switch').length === 1;
      }));
      check('disabled template rows are dimmed', row(ID.debug).hasClass('tf-off') && row(ID.mode).hasClass('tf-off'));
      check('enabled rows are not dimmed', !row(ID.web).hasClass('tf-off') && !row(ID.config).hasClass('tf-off') && !row(ID.tz).hasClass('tf-off') && !row(ID.tag).hasClass('tf-off'));
      check('page holds plain types (prefix stripped on load)', $('input[name="confType[]"]').toArray().every(function (i) { return i.value.indexOf(':') < 0; }), $('input[name="confType[]"]').toArray().map(function (i) { return i.value; }));
      check('a disabled Port still got the Port value widget', row(ID.debug).find('input[name="confValue[]"]').hasClass('numbersOnly'));
      check('a disabled Variable with options still got the dropdown', row(ID.mode).find('select[name="confValue[]"]').hasClass('selectVariable'));
      check('the disabled required field no longer blocks submit', snapshot() !== null);
    });
  }).then(function () {
    var t = types();
    check('submit prefixes exactly the two disabled rows', JSON.stringify(t) === JSON.stringify(['Port', 'Disabled:Port', 'Path', 'Variable', 'Disabled:Variable', 'Label']), t);
    return tick(function () {
      check('prefix is taken off again after submit', $('input[name="confType[]"]').toArray().every(function (i) { return i.value.indexOf(':') < 0; }), $('input[name="confType[]"]').toArray().map(function (i) { return i.value; }));
    });
  }).then(function () {
    // enabling a required field needs a value, exactly as it does on the real page
    check('an enabled required field that is empty blocks submit', (function () { row(ID.debug).find('.tf-switch').trigger('click'); return snapshot() === null; })());
    row(ID.debug).find('input[name="confValue[]"]').val('9999');
    return tick(function () {
      check('toggle on: row no longer dimmed', !row(ID.debug).hasClass('tf-off'));
      check('toggle on: type is sent plain', types()[1] === 'Port', types());
      check('toggle on: required is back', row(ID.debug).find('input[name="confValue[]"]').prop('required') === true);
      row(ID.debug).find('.tf-switch').trigger('click');
    });
  }).then(function () {
    return tick(function () {
      check('toggle off again: prefixed', types()[1] === 'Disabled:Port', types());
      check('toggle off again: required dropped', row(ID.debug).find('input[name="confValue[]"]').prop('required') === false);
      row(ID.web).find('.tf-switch').trigger('click');
    });
  }).then(function () {
    return tick(function () {
      check('a freshly disabled required row can be submitted', snapshot() !== null && types()[0] === 'Disabled:Port', types());
      row(ID.web).find('.tf-switch').trigger('click'); // back on
    });
  }).then(function () {
    var before = names();
    $('#ConfigNum' + ID.debug + ' .tf-handle').trigger($.Event('keydown', { key: 'ArrowUp' }));
    return tick(function () {
      var after = names();
      check('arrow up moves a row before its neighbour', JSON.stringify(after.slice(0, 3)) === JSON.stringify(['Debug port', 'WebUI', 'Config']), after);
      check('the moved row kept its disabled state', types()[0] === 'Disabled:Port', types());
      $('#ConfigNum' + ID.debug + ' .tf-handle').trigger($.Event('keydown', { key: 'ArrowUp' }));
    });
  }).then(function () {
    return tick(function () {
      check('arrow up on the first row of a list does nothing', names().slice(0, 3).join() === 'Debug port,WebUI,Config', names());
      $('#ConfigNum' + ID.config + ' .tf-handle').trigger($.Event('keydown', { key: 'ArrowDown' }));
    });
  }).then(function () {
    return tick(function () {
      check('rows do not cross from the basic list into the advanced list', $('#configLocation').children('div[id^=ConfigNum]').length === 3 && names().indexOf('Config') === 2, names());
    });
  }).then(function () {
    // the edit dialog: a same-Display edit rewrites the row's contents
    editConfigPopup(ID.debug);
    var $d = $('#dialogAddConfig');
    check('opening the edit dialog does not throw on the plugin controls', $d.find('input[name="Name"]').val() === 'Debug port');
    check('the edit dialog sees the plain type', $d.find('input[name="Type"]').val() === 'Port', $d.find('input[name="Type"]').val());
    $d.dialog('option', 'buttons')['Save'].call($d[0]);
    return tick(function () {
      var $r = row(ID.debug);
      check('after a same-Display edit: one set of controls', $r.children('.tf-controls').length === 1, $r.children('.tf-controls').length);
      check('after a same-Display edit: still disabled', $r.hasClass('tf-off') && types().indexOf('Disabled:Port') >= 0, types());
    });
  }).then(function () {
    // Display change: the page removes the row and adds a new one with the same id
    editConfigPopup(ID.debug);
    $('#dialogAddConfig').find('select[name="Display"]').val('advanced');
    $('#dialogAddConfig').dialog('option', 'buttons')['Save'].call($('#dialogAddConfig')[0]);
    return tick(function () {
      var $r = $('#configLocationAdvanced').children('#ConfigNum' + ID.debug);
      check('after a Display change: the row is in the advanced list', $r.length === 1 && $('#configLocation').children('#ConfigNum' + ID.debug).length === 0);
      check('after a Display change: it is still disabled with controls', $r.hasClass('tf-off') && $r.children('.tf-controls').length === 1);
      check('after a Display change: it is still sent disabled', types().indexOf('Disabled:Port') >= 0, types());
    });
  }).then(function () {
    removeConfig(ID.mode);
    return new Promise(function (r) { setTimeout(r, 400); });
  }).then(function () {
    addConfig();
    return tick(function () {
      var $new = $('#configLocation').children('div[id^=ConfigNum]').last();
      check('a row added after a removal starts enabled', !$new.hasClass('tf-off') && $new.children('.tf-controls').length === 1);
      check('removed disabled row is gone from the submit', names().indexOf('Mode') < 0, names());
      check('exactly one disabled row remains in the submit', types().filter(function (x) { return x.indexOf('Disabled:') === 0; }).length === 1, types());
    });
  }).then(function () {
    document.getElementById('selftest').textContent = out.join('\n') + '\n\n' + (fails ? fails + ' FAILED' : 'ALL PASSED');
    document.title = 'selftest ' + (fails ? 'FAIL' : 'PASS');
  }).catch(function (e) {
    document.getElementById('selftest').textContent = out.join('\n') + '\n\nEXCEPTION: ' + (e && e.stack || e);
    document.title = 'selftest FAIL';
  });
})();
</script>
