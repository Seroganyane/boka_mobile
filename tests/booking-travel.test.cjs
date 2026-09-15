const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function page() {
  const elements = new Map();
  const element = (id) => {
    if (!elements.has(id)) elements.set(id, { textContent: '', disabled: false, value: '' });
    return elements.get(id);
  };
  let type = 'dropoff';
  const context = vm.createContext({
    document: { readyState: 'loading', addEventListener() {}, getElementById: element },
    setTimeout: () => 1, clearTimeout() {}, AbortController,
    form: { querySelector: () => ({ value: type }) },
  });
  vm.runInContext(fs.readFileSync(require('node:path').join(__dirname, '../js/script.js'), 'utf8'), context);
  vm.runInContext("bookingForm = form; bookingService = {value:'sedan'}; bookingPackage = {options:[{value:'sedan:0'}], selectedIndex:0};", context);
  return { context, element, setType: (value) => { type = value; }, run: (code) => vm.runInContext(code, context) };
}

test('manual kilometres update fees and totals immediately', () => {
  const p = page();
  p.run('updateBookingTotal()');
  assert.equal(p.element('bookingTotal').textContent, 'R700.00');
  p.setType('housecall');
  p.run('updateVisitType()');
  assert.equal(p.element('bookingTotal').textContent, 'Enter kilometres first');
  p.element('bookingKilometers').value = '12.5';
  p.run('updateBookingTotal()');
  assert.equal(p.element('bookingTravelFee').textContent, 'R62.50');
  assert.equal(p.element('bookingTotal').textContent, 'R762.50');
  p.element('bookingKilometers').value = '20';
  p.run('updateBookingTotal()');
  assert.equal(p.element('bookingTotal').textContent, 'R800.00');
  p.setType('dropoff');
  p.run('updateVisitType()');
  assert.equal(p.element('bookingTotal').textContent, 'R700.00');
  assert.equal(p.element('bookingKilometers').disabled, true);
});

test('invalid distances do not produce a house-call total', () => {
  const p = page();
  p.setType('housecall');
  for (const value of ['', '-1', '0', 'NaN', 'Infinity', '10000', '1.2345']) {
    p.element('bookingKilometers').value = value;
    p.run('updateBookingTotal()');
    assert.equal(p.element('bookingTotal').textContent, 'Enter kilometres first');
  }
});
