const packages = {
    sedan: [
      ["Interior deep clean", 700],
      ["Interior + engine bay wash", 850],
      ["Interior + normal car wash", 800],
      ["Exterior wash & shine + interior", 1100],
      ["Ceramic coating (add-on)", 400],
    ],
    suv: [
      ["Interior deep clean", 800],
      ["Interior + engine bay wash", 900],
      ["Interior + normal car wash", 950],
      ["Exterior wash & shine + interior", 1300],
      ["Ceramic coating (add-on)", 450],
    ],
    bakkie: [
      ["Interior deep clean", 800],
      ["Interior + engine bay wash", 900],
      ["Interior + normal car wash", 950],
      ["Exterior wash & shine + interior", 1300],
      ["Ceramic coating (add-on)", 450],
    ],
    minibus: [
      ["Exterior wash", 150],
      ["Interior deep clean", 1400],
      ["Interior deep clean + exterior wash", 1550],
    ],
    home: [
      ["Couch — 2 seats", 250],
      ["Couch — 3 seats", 350],
      ["Couch — 4 seats", 450],
      ["Couch — L shape", 500],
      ["Mattress — single bed", 350],
      ["Mattress — double/queen/king", 500],
    ]
  };

  window.addEventListener('load', () => {
    const loader = document.getElementById('siteLoader');
    window.setTimeout(() => {
      document.body.classList.remove('is-loading');
      if (loader) loader.setAttribute('aria-hidden', 'true');
    }, 650);
  });

  function updatePackages(){
    const cat = document.getElementById('cat').value;
    const pkgSelect = document.getElementById('pkg');
    pkgSelect.innerHTML = '';
    packages[cat].forEach((p, i) => {
      const opt = document.createElement('option');
      opt.value = p[1];
      opt.textContent = p[0] + ' — R' + p[1];
      pkgSelect.appendChild(opt);
    });
    updateEstimate();
  }

  function updateEstimate(){
    const val = document.getElementById('pkg').value;
    document.getElementById('estval').textContent = 'R' + val;
  }

  updatePackages();

  const menuToggle = document.querySelector('.menu-toggle');
  const navLinks = document.querySelector('.navlinks');
  if (menuToggle && navLinks) {
    navLinks.id = 'site-menu';
    menuToggle.addEventListener('click', () => {
      const isOpen = navLinks.classList.toggle('is-open');
      menuToggle.setAttribute('aria-expanded', String(isOpen));
      menuToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    });
    navLinks.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      navLinks.classList.remove('is-open');
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.setAttribute('aria-label', 'Open navigation');
    }));
  }

  const bookingForm = document.getElementById('bookingForm');
  const bookingSuccess = document.getElementById('bookingSuccess');
  const bookingService = document.getElementById('bookingService');
  const bookingPackage = document.getElementById('bookingPackage');
  const bookingSubmit = document.getElementById('bookingSubmit');

  function updateBookingPackages() {
    const category = bookingService.value;
    bookingPackage.innerHTML = '';
    bookingPackage.disabled = !category;

    if (!category) {
      bookingPackage.add(new Option('Select a service category first', ''));
      return;
    }

    bookingPackage.add(new Option('Select a package', ''));
    packages[category].forEach((item, index) => {
      bookingPackage.add(new Option(`${item[0]} — R${item[1]}`, `${category}:${index}`));
    });
    updateBookingTotal();
  }

  function updateBookingButton() {
    const paymentMethod = bookingForm.querySelector('[name="paymentMethod"]:checked').value;
    bookingSubmit.innerHTML = paymentMethod === 'online' ? 'Continue to secure payment <span aria-hidden="true">→</span>' : 'Request booking <span aria-hidden="true">→</span>';
  }

  function updateBookingTotal() {
    const total = document.getElementById('bookingTotal');
    const option = bookingPackage.options[bookingPackage.selectedIndex];
    total.textContent = option && option.value ? `R${packages[bookingService.value][Number(option.value.split(':')[1])][1]}` : 'Choose a package';
  }

  if (bookingForm) {
    bookingService.addEventListener('change', updateBookingPackages);
    bookingPackage.addEventListener('change', updateBookingTotal);
    bookingForm.querySelectorAll('[name="paymentMethod"]').forEach((choice) => {
      choice.addEventListener('change', updateBookingButton);
    });

    bookingForm.addEventListener('submit', function (event) {
      event.preventDefault();

      const name = bookingForm.querySelector('[name="name"]').value.trim();
      const phone = bookingForm.querySelector('[name="phone"]').value.trim();
      const email = bookingForm.querySelector('[name="email"]').value.trim();
      const service = bookingService.options[bookingService.selectedIndex].text;
      const packageOption = bookingPackage.options[bookingPackage.selectedIndex];
      const date = bookingForm.querySelector('[name="date"]').value;
      const notes = bookingForm.querySelector('[name="notes"]').value.trim();
      const paymentMethod = bookingForm.querySelector('[name="paymentMethod"]:checked').value;

      const body = [
        `Name: ${name || 'Not provided'}`,
        `Phone: ${phone || 'Not provided'}`,
        `Email: ${email || 'Not provided'}`,
        `Service: ${service || 'Not provided'}`,
        `Package: ${packageOption ? packageOption.text : 'Not provided'}`,
        `Preferred date: ${date || 'Not provided'}`,
        `Payment: ${paymentMethod === 'online' ? 'Paystack online payment' : 'Cash on arrival'}`,
        `Notes: ${notes || 'No additional notes'}`
      ].join('\n');

      if (paymentMethod === 'online') {
        bookingSubmit.disabled = true;
        bookingSubmit.textContent = 'Opening secure payment…';
        fetch('php/initialize-payment.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, phone, email, category: bookingService.value, package: bookingPackage.value, date, notes })
        })
          .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
          .then(({ ok, data }) => {
            if (!ok || !data.authorization_url) throw new Error(data.message || 'Unable to start payment.');
            window.location.assign(data.authorization_url);
          })
          .catch((error) => {
            bookingSuccess.textContent = error.message || 'Unable to start payment. Please try again or choose cash.';
            bookingSuccess.style.display = 'block';
            bookingSubmit.disabled = false;
            updateBookingButton();
          });
        return;
      }

      window.location.href = `mailto:nickmametja6@gmail.com?subject=${encodeURIComponent('New booking request from Boka Mobile Detailing')}&body=${encodeURIComponent(body)}`;

      bookingSuccess.textContent = `Thanks${name ? `, ${name}` : ''}! Your booking request is ready to send. We’ll follow up shortly.`;
      bookingSuccess.style.display = 'block';
      bookingForm.reset();
      updateBookingPackages();
      updateBookingTotal();
      updateBookingButton();
    });

    updateBookingPackages();
    updateBookingTotal();
    updateBookingButton();
  }
