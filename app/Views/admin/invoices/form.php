<?php
use App\Core\View;
use App\Models\Patient;
?>
<h2 class="mb-4"><?= View::e($page_title) ?></h2>

<form method="post" action="/admin/invoices" id="invoiceForm">
  <?= $csrf_field ?>

  <div class="card p-4 mb-3">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Patient <span class="text-danger">*</span></label>
        <input type="text" id="patientFilter" class="form-control mb-2" placeholder="Type to filter patients...">
        <select name="patient_id" id="patientSelect" class="form-select" required>
          <option value="">Select a patient...</option>
          <?php foreach ($patients as $p): ?>
            <option value="<?= (int) $p['id'] ?>" data-name="<?= View::e(Patient::fullName($p)) ?>" <?= $preselectedPatientId === (int) $p['id'] ? 'selected' : '' ?>>
              <?= View::e($p['patient_code'] . ' - ' . Patient::fullName($p)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Invoice Date</label>
        <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Due Date</label>
        <input type="date" name="due_date" class="form-control">
      </div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <h6 class="text-uppercase text-muted small mb-3">Line Items</h6>
    <table class="table" id="itemsTable">
      <thead><tr><th>Service</th><th>Description</th><th style="width:90px;">Qty</th><th style="width:120px;">Unit Price</th><th style="width:90px;">GST %</th><th style="width:40px;"></th></tr></thead>
      <tbody id="itemsBody"></tbody>
    </table>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn"><i class="fa-solid fa-plus me-1"></i> Add Line Item</button>

    <div class="row g-3 mt-3 justify-content-end">
      <div class="col-md-3">
        <label class="form-label">Discount Amount (&#8377;)</label>
        <input type="number" step="0.01" name="discount_amount" id="discountInput" class="form-control" value="0">
      </div>
    </div>
    <div class="text-end mt-2">
      <div>Subtotal: &#8377;<span id="subtotalDisplay">0.00</span></div>
      <div>GST: &#8377;<span id="gstDisplay">0.00</span></div>
      <div class="fw-bold">Total: &#8377;<span id="totalDisplay">0.00</span></div>
    </div>
  </div>

  <div class="card p-4 mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="2"></textarea>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Create Invoice</button>
    <a href="/admin/invoices" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<script>
(function () {
  var services = <?= json_encode(array_map(static function (array $s): array {
      return ['id' => (int) $s['id'], 'name' => $s['name'], 'fee' => (float) $s['fee'], 'gst_rate' => (float) $s['gst_rate']];
  }, $services)) ?>;
  var itemsBody = document.getElementById('itemsBody');
  var itemIndex = 0;

  function serviceOptions(selectedId) {
    var html = '<option value="">&mdash; Custom &mdash;</option>';
    services.forEach(function (s) {
      html += '<option value="' + s.id + '" data-fee="' + s.fee + '" data-gst="' + s.gst_rate + '" data-name="' + s.name.replace(/"/g, '&quot;') + '"' + (selectedId === s.id ? ' selected' : '') + '>' + s.name + '</option>';
    });
    return html;
  }

  function addItemRow() {
    var idx = itemIndex++;
    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td><select name="items[' + idx + '][service_id]" class="form-select form-select-sm service-select">' + serviceOptions(null) + '</select></td>' +
      '<td><input type="text" name="items[' + idx + '][description]" class="form-control form-control-sm description-input" required></td>' +
      '<td><input type="number" name="items[' + idx + '][quantity]" class="form-control form-control-sm" value="1" min="1"></td>' +
      '<td><input type="number" step="0.01" name="items[' + idx + '][unit_price]" class="form-control form-control-sm price-input" value="0" min="0"></td>' +
      '<td><input type="number" step="0.01" name="items[' + idx + '][gst_rate]" class="form-control form-control-sm gst-input" value="0" min="0"></td>' +
      '<td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="fa-solid fa-trash"></i></button></td>';
    itemsBody.appendChild(tr);

    var select = tr.querySelector('.service-select');
    var description = tr.querySelector('.description-input');
    var price = tr.querySelector('.price-input');
    var gst = tr.querySelector('.gst-input');

    select.addEventListener('change', function () {
      var opt = select.options[select.selectedIndex];
      if (opt.value) {
        description.value = opt.dataset.name;
        price.value = opt.dataset.fee;
        gst.value = opt.dataset.gst;
      }
      recalculate();
    });
    [price, gst, tr.querySelector('input[name$="[quantity]"]')].forEach(function (el) {
      el.addEventListener('input', recalculate);
    });
    tr.querySelector('.remove-item').addEventListener('click', function () {
      tr.remove();
      recalculate();
    });
    recalculate();
  }

  function recalculate() {
    var subtotal = 0, gstTotal = 0;
    itemsBody.querySelectorAll('tr').forEach(function (tr) {
      var qty = parseFloat(tr.querySelector('input[name$="[quantity]"]').value) || 0;
      var price = parseFloat(tr.querySelector('.price-input').value) || 0;
      var gstRate = parseFloat(tr.querySelector('.gst-input').value) || 0;
      var lineBase = qty * price;
      subtotal += lineBase;
      gstTotal += lineBase * (gstRate / 100);
    });
    var discount = parseFloat(document.getElementById('discountInput').value) || 0;
    document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2);
    document.getElementById('gstDisplay').textContent = gstTotal.toFixed(2);
    document.getElementById('totalDisplay').textContent = Math.max(0, subtotal + gstTotal - discount).toFixed(2);
  }

  document.getElementById('addItemBtn').addEventListener('click', addItemRow);
  document.getElementById('discountInput').addEventListener('input', recalculate);
  addItemRow();

  var patientSelect = document.getElementById('patientSelect');
  var patientFilter = document.getElementById('patientFilter');
  patientFilter.addEventListener('input', function () {
    var term = patientFilter.value.toLowerCase();
    Array.prototype.forEach.call(patientSelect.options, function (opt) {
      if (opt.value === '') { return; }
      var name = (opt.dataset.name || '').toLowerCase();
      var text = opt.textContent.toLowerCase();
      opt.hidden = term !== '' && name.indexOf(term) === -1 && text.indexOf(term) === -1;
    });
  });
})();
</script>
