const packages = {
  sedan: [
    ['Interior deep clean', 700],
    ['Interior + engine bay wash', 850],
    ['Interior + normal car wash', 800],
    ['Exterior wash & shine + interior', 1100],
    ['Ceramic coating (add-on)', 400]
  ],
  suv: [
    ['Interior deep clean', 800],
    ['Interior + engine bay wash', 900],
    ['Interior + normal car wash', 950],
    ['Exterior wash & shine + interior', 1300],
    ['Ceramic coating (add-on)', 450]
  ],
  bakkie: [
    ['Interior deep clean', 800],
    ['Interior + engine bay wash', 900],
    ['Interior + normal car wash', 950],
    ['Exterior wash & shine + interior', 1300],
    ['Ceramic coating (add-on)', 450]
  ],
  minibus: [
    ['Exterior wash', 150],
    ['Interior deep clean', 1400],
    ['Interior deep clean + exterior wash', 1550]
  ],
  home: [
    ['Couch — 2 seats', 250],
    ['Couch — 3 seats', 350],
    ['Couch — 4 seats', 450],
    ['Couch — L shape', 500],
    ['Mattress — single bed', 350],
    ['Mattress — double/queen/king', 500]
  ]
};

let bookingForm;
let bookingSuccess;
let bookingSubmit;
let bookingService;
let bookingPackage;
function visitType() {
  return bookingForm.querySelector('[name="visitType"]:checked')?.value || '';
}

function money(cents) {
  return `R${(cents / 100).toFixed(2)}`;
}

function enteredTravel() {
  const value = document.getElementById('bookingKilometers').value.trim();
  if (!/^\d{1,4}(\.\d{1,3})?$/.test(value)) return null;
  const meters = Math.round(Number(value) * 1000);
  if (meters <= 0 || meters > 9999000) return null;
  return { distanceKm: meters / 1000, feeCents: Math.round(meters / 2) };
}

function updateVisitType() {
  const houseCall = visitType() === 'housecall';
  document.getElementById('houseCallFields').hidden = !houseCall;
  document.getElementById('bookingLocation').required = houseCall;
  document.getElementById('bookingKilometers').required = houseCall;
  document.getElementById('bookingKilometers').disabled = !houseCall;
  updateBookingTotal();
}

function buildCustomSelect(select) {
  if (!select || select.dataset.customized === 'true') return;

  const wrapper = document.createElement('div');
  wrapper.className = 'custom-select';

  const parent = select.parentNode;
  parent.insertBefore(wrapper, select);
  wrapper.appendChild(select);

  const trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.className = 'custom-select-trigger';
  trigger.setAttribute('aria-haspopup', 'listbox');
  trigger.setAttribute('aria-expanded', 'false');
  wrapper.appendChild(trigger);

  const menu = document.createElement('ul');
  menu.className = 'custom-select-menu';
  menu.setAttribute('role', 'listbox');
  wrapper.appendChild(menu);

  const syncMenu = () => {
    const selected = select.options[select.selectedIndex];
    trigger.textContent = selected ? selected.textContent : 'Select';
    Array.from(menu.children).forEach((item) => {
      const isSelected = item.dataset.value === select.value;
      item.classList.toggle('is-selected', isSelected);
      item.setAttribute('aria-selected', String(isSelected));
    });
  };

  Array.from(select.options).forEach((option) => {
    const item = document.createElement('li');
    item.className = 'custom-select-option';
    item.dataset.value = option.value;
    item.textContent = option.textContent;
    item.setAttribute('role', 'option');
    item.addEventListener('click', () => {
      select.value = option.value;
      syncMenu();
      menu.classList.remove('is-open');
      trigger.setAttribute('aria-expanded', 'false');
      select.dispatchEvent(new Event('change', { bubbles: true }));
    });
    menu.appendChild(item);
  });

  trigger.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = menu.classList.toggle('is-open');
    trigger.setAttribute('aria-expanded', String(isOpen));
  });

  document.addEventListener('click', (event) => {
    if (!wrapper.contains(event.target)) {
      menu.classList.remove('is-open');
      trigger.setAttribute('aria-expanded', 'false');
    }
  });

  select.dataset.customized = 'true';
  select.style.display = 'none';
  syncMenu();
}

function updatePackages() {
  const catSelect = document.getElementById('cat');
  const pkgSelect = document.getElementById('pkg');
  if (!catSelect || !pkgSelect || !packages[catSelect.value]) return;

  const cat = catSelect.value;
  pkgSelect.innerHTML = '';

  packages[cat].forEach((item) => {
    const opt = document.createElement('option');
    opt.value = item[1];
    opt.textContent = `${item[0]} — R${item[1]}`;
    pkgSelect.appendChild(opt);
  });

  if (pkgSelect.dataset.customized === 'true') {
    const wrapper = pkgSelect.parentNode;
    const menu = wrapper.querySelector('.custom-select-menu');
    menu.innerHTML = '';

    Array.from(pkgSelect.options).forEach((option) => {
      const item = document.createElement('li');
      item.className = 'custom-select-option';
      item.dataset.value = option.value;
      item.textContent = option.textContent;
      item.addEventListener('click', () => {
        pkgSelect.value = option.value;
        menu.classList.remove('is-open');
        wrapper.querySelector('.custom-select-trigger').setAttribute('aria-expanded', 'false');
        pkgSelect.dispatchEvent(new Event('change', { bubbles: true }));
        syncCustomSelect(pkgSelect);
      });
      menu.appendChild(item);
    });

    syncCustomSelect(pkgSelect);
  }

  updateEstimate();
}

function syncCustomSelect(select) {
  if (!select || select.dataset.customized !== 'true') return;
  const wrapper = select.parentNode;
  const trigger = wrapper.querySelector('.custom-select-trigger');
  const menu = wrapper.querySelector('.custom-select-menu');
  if (!trigger || !menu) return;

  const selected = select.options[select.selectedIndex];
  trigger.textContent = selected ? selected.textContent : 'Select';

  Array.from(menu.children).forEach((item) => {
    const isSelected = item.dataset.value === select.value;
    item.classList.toggle('is-selected', isSelected);
    item.setAttribute('aria-selected', String(isSelected));
  });
}

function updateEstimate() {
  const pkgSelect = document.getElementById('pkg');
  const estimate = document.getElementById('estval');
  if (!pkgSelect || !estimate) return;

  estimate.textContent = pkgSelect.value ? `R${pkgSelect.value}` : 'Choose a package';
}

function updateBookingPackages() {
  if (!bookingService || !bookingPackage) {
    return;
  }

  const category = bookingService.value;
  bookingPackage.innerHTML = '';
  bookingPackage.disabled = !category;

  if (!category) {
    bookingPackage.add(new Option('Select a service category first', ''));
    if (bookingPackage.dataset.customized === 'true') {
      syncCustomSelect(bookingPackage);
    }
    updateBookingTotal();
    return;
  }

  bookingPackage.add(new Option('Select a package', ''));
  packages[category].forEach((item, index) => {
    bookingPackage.add(new Option(`${item[0]} — R${item[1]}`, `${category}:${index}`));
  });

  if (bookingPackage.dataset.customized === 'true') {
    const wrapper = bookingPackage.parentNode;
    const menu = wrapper.querySelector('.custom-select-menu');
    if (menu) {
      menu.innerHTML = '';

      Array.from(bookingPackage.options).forEach((option) => {
        const item = document.createElement('li');
        item.className = 'custom-select-option';
        item.dataset.value = option.value;
        item.textContent = option.textContent;
        item.addEventListener('click', () => {
          bookingPackage.value = option.value;
          menu.classList.remove('is-open');
          wrapper.querySelector('.custom-select-trigger').setAttribute('aria-expanded', 'false');
          bookingPackage.dispatchEvent(new Event('change', { bubbles: true }));
          syncCustomSelect(bookingPackage);
        });
        menu.appendChild(item);
      });

      syncCustomSelect(bookingPackage);
    }
  }

  updateBookingTotal();
}

function updateBookingButton() {
  const paymentMethod = bookingForm.querySelector('[name="paymentMethod"]:checked').value;
  bookingSubmit.innerHTML = paymentMethod === 'online'
    ? 'Continue to secure payment <span aria-hidden="true">→</span>'
    : 'Request booking <span aria-hidden="true">→</span>';
}

function updateBookingTotal() {
  const total = document.getElementById('bookingTotal');
  const option = bookingPackage.options[bookingPackage.selectedIndex];
  const baseCents = option && option.value ? packages[bookingService.value][Number(option.value.split(':')[1])][1] * 100 : null;
  const type = visitType();
  const travelQuote = enteredTravel();
  document.getElementById('bookingBasePrice').textContent = baseCents === null ? 'Choose a package' : money(baseCents);
  document.getElementById('bookingDistance').textContent = type === 'dropoff' ? 'Not applicable' : travelQuote ? `${travelQuote.distanceKm.toFixed(3)} km` : type ? 'Enter kilometres' : 'Choose a visit option';
  document.getElementById('bookingTravelFee').textContent = type === 'dropoff' ? money(0) : travelQuote ? money(travelQuote.feeCents) : type ? 'Enter kilometres' : 'Choose a visit option';
  total.textContent = baseCents === null ? 'Choose a package' : !type ? 'Choose a visit option' : type === 'housecall' && !travelQuote ? 'Enter kilometres first' : money(baseCents + (type === 'housecall' ? travelQuote.feeCents : 0));
}

let bookingInitialized = false;

function initializeBookingPage() {
  if (bookingInitialized) {
    return;
  }

  bookingInitialized = true;

  const loader = document.getElementById('siteLoader');
  setTimeout(() => {
    document.body.classList.remove('is-loading');
    if (loader) loader.setAttribute('aria-hidden', 'true');
  }, 650);

  bookingForm = document.getElementById('bookingForm');
  bookingSuccess = document.getElementById('bookingSuccess');
  bookingSubmit = document.getElementById('bookingSubmit');
  bookingService = document.getElementById('bookingService');
  bookingPackage = document.getElementById('bookingPackage');

  if (!bookingForm || !bookingSuccess || !bookingSubmit || !bookingService || !bookingPackage) {
    return;
  }

  const catSelect = document.getElementById('cat');
  const pkgSelect = document.getElementById('pkg');

  if (catSelect && pkgSelect) {
    buildCustomSelect(catSelect);
    buildCustomSelect(pkgSelect);
    catSelect.addEventListener('change', updatePackages);
    pkgSelect.addEventListener('change', updateEstimate);
    updatePackages();
  }

  const menuToggle = document.querySelector('.menu-toggle');
  const navLinks = document.querySelector('.navlinks');
  if (menuToggle && navLinks) {
    navLinks.id = 'site-menu';
    menuToggle.addEventListener('click', () => {
      const isOpen = navLinks.classList.toggle('is-open');
      menuToggle.setAttribute('aria-expanded', String(isOpen));
      menuToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    });
    navLinks.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Open navigation');
      });
    });
  }

  bookingService.addEventListener('change', updateBookingPackages);
  bookingPackage.addEventListener('change', updateBookingTotal);
  bookingForm.querySelectorAll('[name="visitType"]').forEach((choice) => {
    choice.addEventListener('change', updateVisitType);
  });
  document.getElementById('bookingKilometers').addEventListener('input', updateBookingTotal);
  bookingForm.querySelectorAll('[name="paymentMethod"]').forEach((choice) => {
    choice.addEventListener('change', updateBookingButton);
  });

  bookingForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const name = bookingForm.querySelector('[name="name"]').value.trim();
    const phone = bookingForm.querySelector('[name="phone"]').value.trim();
    const email = bookingForm.querySelector('[name="email"]').value.trim();
    const location = bookingForm.querySelector('[name="location"]').value.trim();
    const date = bookingForm.querySelector('[name="date"]').value;
    const notes = bookingForm.querySelector('[name="notes"]').value.trim();
    const paymentMethod = bookingForm.querySelector('[name="paymentMethod"]:checked').value;
    const selectedVisit = visitType();
    if (selectedVisit === 'housecall' && !enteredTravel()) {
      document.getElementById('bookingKilometers').reportValidity();
      document.getElementById('bookingKilometers').focus();
      return;
    }

    if (paymentMethod === 'online') {
      bookingSubmit.disabled = true;
      bookingSubmit.textContent = 'Opening secure payment…';

      fetch('php/initialize-payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name,
          phone,
          email,
          location,
          visitType: selectedVisit,
          distanceKm: selectedVisit === 'housecall' ? document.getElementById('bookingKilometers').value : '',
          category: bookingService.value,
          package: bookingPackage.value,
          date,
          notes
        })
      })
        .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
          if (!ok || !data.process_url || !data.fields) {
            throw new Error(data.message || 'Unable to start payment.');
          }

          const payFastForm = document.createElement('form');
          payFastForm.method = 'POST';
          payFastForm.action = data.process_url;
          Object.entries(data.fields).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            payFastForm.appendChild(input);
          });
          document.body.appendChild(payFastForm);
          payFastForm.submit();
        })
        .catch((error) => {
          bookingSuccess.textContent = error.message || 'Unable to start payment. Please try again or choose cash.';
          bookingSuccess.style.display = 'block';
          bookingSubmit.disabled = false;
          updateBookingButton();
        });
      return;
    }

    bookingSubmit.disabled = true;
    bookingSubmit.textContent = 'Sending booking...';
    fetch('php/create-booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name,
        phone,
        email,
        location,
        visitType: selectedVisit,
        distanceKm: selectedVisit === 'housecall' ? document.getElementById('bookingKilometers').value : '',
        category: bookingService.value,
        package: bookingPackage.value,
        date,
        notes
      })
    })
      .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
      .then(({ ok, data }) => {
        if (!ok) throw new Error(data.message || 'Unable to send booking.');
        bookingSuccess.textContent = `Thanks${name ? `, ${name}` : ''}! Your booking was received. We sent a confirmation to ${email}.`;
        bookingSuccess.style.display = 'block';
        bookingForm.reset();
        updateVisitType();
        updateBookingPackages();
        updateBookingTotal();
      })
      .catch((error) => {
        bookingSuccess.textContent = error.message || 'Unable to send booking. Please call us.';
        bookingSuccess.style.display = 'block';
      })
      .finally(() => {
        bookingSubmit.disabled = false;
        updateBookingButton();
      });
  });

  updateBookingPackages();
  updateBookingTotal();
  updateBookingButton();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeBookingPage, { once: true });
} else {
  initializeBookingPage();
}
