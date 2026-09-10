<?php
/**
 * Reusable repeating "Next of kin" rows. Used by hrms/my_profile and
 * hrms/employee_edit. Each row submits as PHP arrays so the server reads
 * $_POST['kin_relation'][], $_POST['kin_name'][], etc.
 */
$relations = ['Father','Mother','Sister','Brother','Husband','Wife','Son','Daughter','Guardian','Father-in-law','Mother-in-law','Other'];
$kin = $kin ?? [];
// Always render at least 1 row so users see the inputs even on a new profile.
if (empty($kin)) $kin = [['relation'=>'','name'=>'','phone'=>'','alt_phone'=>'','email'=>'','address'=>'','is_emergency_contact'=>0]];

$renderRow = function (array $row) use ($relations) {
    ob_start();
    ?>
    <div class="kin-row card mb-2" style="border:1px solid #e5e7eb;background:#fafbfc;">
      <div class="card-body p-3">
        <div class="row g-2 align-items-end">
          <div class="col-md-2">
            <label class="form-label" style="font-size:.74rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Relation *</label>
            <select class="form-select form-select-sm" name="kin_relation[]" required>
              <option value="">— pick —</option>
              <?php foreach ($relations as $r): ?>
                <option value="<?= esc($r) ?>" <?= ($row['relation'] ?? '') === $r ? 'selected' : '' ?>><?= esc($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label" style="font-size:.74rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Name *</label>
            <input class="form-control form-control-sm" type="text" name="kin_name[]" maxlength="150" placeholder="e.g. Suresh Kumar" value="<?= esc($row['name'] ?? '') ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label" style="font-size:.74rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Phone *</label>
            <input class="form-control form-control-sm" type="tel" name="kin_phone[]" maxlength="30" placeholder="10-digit mobile" value="<?= esc($row['phone'] ?? '') ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label" style="font-size:.74rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Alt phone</label>
            <input class="form-control form-control-sm" type="tel" name="kin_alt_phone[]" maxlength="30" value="<?= esc($row['alt_phone'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label" style="font-size:.74rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Email</label>
            <input class="form-control form-control-sm" type="email" name="kin_email[]" maxlength="150" value="<?= esc($row['email'] ?? '') ?>">
          </div>
          <div class="col-md-10">
            <label class="form-label" style="font-size:.74rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Address (optional)</label>
            <input class="form-control form-control-sm" type="text" name="kin_address[]" maxlength="400" placeholder="Their address, city" value="<?= esc($row['address'] ?? '') ?>">
          </div>
          <div class="col-md-2 d-flex flex-column align-items-end gap-2">
            <div class="form-check" style="font-size:.82rem;">
              <input class="form-check-input" type="checkbox" name="kin_is_emergency[<?= rand(0, 99999) ?>]" value="1" <?= !empty($row['is_emergency_contact']) ? 'checked' : '' ?> onchange="this.name='kin_is_emergency['+ (Array.from(document.querySelectorAll('.kin-row')).indexOf(this.closest('.kin-row'))) +']'">
              <label class="form-check-label" title="Call this person first in an emergency">
                <i class="bi bi-star-fill" style="color:#f59e0b;"></i> Emergency
              </label>
            </div>
            <button type="button" class="btn btn-sm btn-light text-danger" onclick="this.closest('.kin-row').remove()">
              <i class="bi bi-trash"></i> Remove
            </button>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
};
?>

<div id="kin-rows-container">
  <?php foreach ($kin as $row) echo $renderRow($row); ?>
</div>

<button type="button" class="btn btn-sm btn-light" id="addKinBtn"><i class="bi bi-plus-circle"></i> Add another family contact</button>

<script>
(function () {
  const tpl = <?= json_encode($renderRow([
      'relation' => '', 'name' => '', 'phone' => '', 'alt_phone' => '',
      'email' => '', 'address' => '', 'is_emergency_contact' => 0,
  ])) ?>;
  document.getElementById('addKinBtn')?.addEventListener('click', function () {
    const c = document.getElementById('kin-rows-container');
    const wrap = document.createElement('div');
    wrap.innerHTML = tpl.trim();
    c.appendChild(wrap.firstChild);
  });

  // Re-key the kin_is_emergency[] checkboxes so submitted indexes line up with
  // the visible row order even after add/remove (PHP receives a plain numeric array).
  function reindexEmergencyCheckboxes() {
    document.querySelectorAll('.kin-row').forEach((row, i) => {
      const cb = row.querySelector('input[type="checkbox"]');
      if (cb) cb.name = 'kin_is_emergency[' + i + ']';
    });
  }
  document.addEventListener('change', (e) => {
    if (e.target.matches('.kin-row input[type="checkbox"]')) reindexEmergencyCheckboxes();
  });
  document.addEventListener('click', (e) => {
    if (e.target.closest('.kin-row .btn-light.text-danger')) {
      setTimeout(reindexEmergencyCheckboxes, 0);
    }
  });
  reindexEmergencyCheckboxes();
})();
</script>
