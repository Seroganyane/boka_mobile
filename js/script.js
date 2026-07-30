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
    home: [
      ["Couch — 2 seats", 250],
      ["Couch — 3 seats", 350],
      ["Couch — 4 seats", 450],
      ["Couch — L shape", 500],
      ["Mattress — single bed", 350],
      ["Mattress — double/queen/king", 500],
    ]
  };

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