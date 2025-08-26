// Fallback: add a Nearby button to each existing server-rendered row
const tbody = document.getElementById('smartlockTableBody');
if (tbody) {
  [...tbody.querySelectorAll('tr')].forEach(tr => {
    const name = tr.cells?.[0]?.textContent?.trim() || '';
    // create Actions cell if it doesn't exist
    const actionsCell = tr.cells.length >= 5 ? tr.cells[4] : tr.insertCell(-1);
    actionsCell.classList.add('text-center');

    const btn = document.createElement('button');
    btn.className = 'btn btn-primary btn-sm getNearbyDevices';
    btn.dataset.name = name;
    btn.textContent = 'Nearby Devices';
    actionsCell.appendChild(btn);
  });
}