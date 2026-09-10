<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0">Operational Calendar</h5>
  <div class="ms-auto d-flex gap-2 flex-wrap" style="font-size:.85rem;">
    <span class="badge" style="background:#d4a017;">Lead</span>
    <span class="badge" style="background:#1d6cb1;">Booking</span>
    <span class="badge" style="background:#166c3b;">Trip</span>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <form id="cal-filters" class="row g-2 mb-3">
      <div class="col-12 col-md-4">
        <label class="form-label" style="font-size:.85rem;">Assigned to</label>
        <select id="f-user" class="form-select form-select-sm">
          <option value="<?= (int) $me ?>">Just me</option>
          <option value="">Everyone</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int) $u['id'] ?>"><?= esc($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label" style="font-size:.85rem;">Client</label>
        <select id="f-client" class="form-select form-select-sm">
          <option value="">All clients</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= esc($c['company_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label" style="font-size:.85rem;">Show</label>
        <div class="d-flex gap-3 align-items-center" style="padding-top:.4rem;">
          <label><input type="checkbox" class="cal-type" value="lead" checked> Leads</label>
          <label><input type="checkbox" class="cal-type" value="booking" checked> Bookings</label>
          <label><input type="checkbox" class="cal-type" value="trip" checked> Trips</label>
        </div>
      </div>
    </form>

    <div id="staff-calendar"></div>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
(function () {
  var el = document.getElementById('staff-calendar');
  if (!el || !window.FullCalendar) return;

  var feedUrl = <?= json_encode(site_url('calendar/feed')) ?>;
  var ufield  = document.getElementById('f-user');
  var cfield  = document.getElementById('f-client');
  var typeBox = document.querySelectorAll('.cal-type');

  function selectedTypes() {
    var t = [];
    typeBox.forEach(function (b) { if (b.checked) t.push(b.value); });
    return t.join(',');
  }

  var isMobile = window.matchMedia('(max-width: 768px)').matches;
  var cal = new FullCalendar.Calendar(el, {
    initialView: isMobile ? 'listWeek' : 'dayGridMonth',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
    height: 'auto',
    events: function (info, success, fail) {
      var url = feedUrl + '?start=' + encodeURIComponent(info.startStr)
                       + '&end='   + encodeURIComponent(info.endStr)
                       + '&user_id='   + encodeURIComponent(ufield.value || '')
                       + '&client_id=' + encodeURIComponent(cfield.value || '')
                       + '&types='     + encodeURIComponent(selectedTypes());
      fetch(url).then(function (r) { return r.json(); }).then(success).catch(fail);
    },
  });
  cal.render();

  function refetch() { cal.refetchEvents(); }
  ufield.addEventListener('change', refetch);
  cfield.addEventListener('change', refetch);
  typeBox.forEach(function (b) { b.addEventListener('change', refetch); });
})();
</script>
