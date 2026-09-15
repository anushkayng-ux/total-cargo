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

$renderKinRow = function (array $row) use ($relations) {
    ob_start();
    ?>
    <tr class="kin-row">
      <td>
        <select class="retro-box" style="width:100%;" name="kin_relation[]" required>
          <option value="">— pick —</option>
          <?php foreach ($relations as $r): ?>
            <option value="<?= esc($r) ?>" <?= ($row['relation'] ?? '') === $r ? 'selected' : '' ?>><?= esc($r) ?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td><input class="retro-box" style="width:100%;" type="text" name="kin_name[]" maxlength="150" placeholder="e.g. Suresh Kumar" value="<?= esc($row['name'] ?? '') ?>"></td>
      <td><input class="retro-box" style="width:100%;" type="tel" name="kin_phone[]" maxlength="30" placeholder="10-digit mobile" value="<?= esc($row['phone'] ?? '') ?>"></td>
      <td><input class="retro-box" style="width:100%;" type="tel" name="kin_alt_phone[]" maxlength="30" value="<?= esc($row['alt_phone'] ?? '') ?>"></td>
      <td><input class="retro-box" style="width:100%;" type="email" name="kin_email[]" maxlength="150" value="<?= esc($row['email'] ?? '') ?>"></td>
      <td><input class="retro-box" style="width:100%;" type="text" name="kin_address[]" maxlength="400" placeholder="Their address, city" value="<?= esc($row['address'] ?? '') ?>"></td>
      <td class="text-center">
        <input type="checkbox" name="kin_is_emergency[<?= rand(0, 99999) ?>]" value="1" title="Call this person first in an emergency" <?= !empty($row['is_emergency_contact']) ? 'checked' : '' ?> onchange="this.name='kin_is_emergency['+ (Array.from(document.querySelectorAll('.kin-row')).indexOf(this.closest('.kin-row'))) +']'">
      </td>
      <td class="text-center">
        <button type="button" class="retro-tbtn" style="width:auto;flex-direction:row;padding:2px 6px;" onclick="this.closest('.kin-row').remove()" aria-label="Remove"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
    <?php
    return ob_get_clean();
};
?>

<div class="gridwrap" style="padding:0;flex:0 0 auto;">
  <table class="table grid mb-0">
    <thead>
      <tr>
        <th>Relation *</th><th>Name *</th><th>Phone *</th><th>Alt phone</th><th>Email</th><th>Address</th>
        <th style="width:80px;" title="Call this person first in an emergency"><i class="bi bi-star-fill" style="color:#f59e0b;"></i> Emerg.</th>
        <th style="width:50px;"></th>
      </tr>
    </thead>
    <tbody id="kin-rows-container">
      <?php foreach ($kin as $row) echo $renderKinRow($row); ?>
    </tbody>
  </table>
</div>

<div class="retro-row" style="margin-top:10px;">
  <button type="button" class="retro-tbtn" style="width:auto;flex-direction:row;" id="addKinBtn"><i class="bi bi-plus-lg"></i>Add another family contact</button>
</div>

<script>
(function () {
  const tpl = <?= json_encode($renderKinRow([
      'relation' => '', 'name' => '', 'phone' => '', 'alt_phone' => '',
      'email' => '', 'address' => '', 'is_emergency_contact' => 0,
  ])) ?>;
  document.getElementById('addKinBtn')?.addEventListener('click', function () {
    const c = document.getElementById('kin-rows-container');
    const wrap = document.createElement('tbody');
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
    if (e.target.closest('.kin-row .retro-tbtn')) {
      setTimeout(reindexEmergencyCheckboxes, 0);
    }
  });
  reindexEmergencyCheckboxes();
})();
</script>
