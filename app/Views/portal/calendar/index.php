<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0">Calendar</h5>
  <div class="ms-auto d-flex gap-2 flex-wrap" style="font-size:.78rem;">
    <span class="badge" style="background:#d4a017;">Pending request</span>
    <span class="badge" style="background:#1d6cb1;">Booking</span>
    <span class="badge" style="background:#166c3b;">Trip</span>
  </div>
</div>

<div class="card">
  <div class="card-body portal-cal-body">
    <p class="text-muted mb-3" style="font-size:.85rem;">
      Click an empty day to <strong>request a truck</strong> for that date. Click an item to view it.
    </p>
    <div id="portal-calendar"></div>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<style>
  /* FullCalendar mobile polish — stack the toolbar and shrink the chrome
     so the month grid / list isn't squeezed on a phone. */
  @media (max-width: 575.98px) {
    .portal-cal-body { padding: .85rem .65rem; }
    .fc .fc-toolbar.fc-header-toolbar {
      flex-direction: column;
      gap: .5rem;
      align-items: stretch;
      margin-bottom: .75rem;
    }
    .fc .fc-toolbar-chunk { display: flex; justify-content: center; flex-wrap: wrap; gap: .35rem; }
    .fc .fc-toolbar-title { font-size: 1.05rem; line-height: 1.2; text-align: center; }
    .fc .fc-button { padding: .35rem .6rem; font-size: .78rem; }
    .fc .fc-button-group { gap: 0; }
    .fc .fc-daygrid-day-number { font-size: .78rem; padding: .15rem .25rem; }
    .fc .fc-col-header-cell-cushion { font-size: .7rem; padding: .25rem; }
    .fc .fc-daygrid-event { font-size: .68rem; padding: 1px 3px; }
    .fc .fc-list-day-cushion { padding: .4rem .6rem; font-size: .8rem; }
    .fc .fc-list-event-title, .fc .fc-list-event-time { font-size: .8rem; }
  }
  @media (max-width: 767.98px) {
    .fc .fc-toolbar-title { font-size: 1.15rem; }
  }
</style>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
(function () {
  var el = document.getElementById('portal-calendar');
  if (!el || !window.FullCalendar) return;

  var feedUrl    = <?= json_encode(site_url('portal/calendar/feed')) ?>;
  var requestUrl = <?= json_encode(site_url('portal/request')) ?>;
  var canRequest = <?= $clientAuth->can('request_booking') ? 'true' : 'false' ?>;

  var mqMobile = window.matchMedia('(max-width: 575.98px)');
  // Smaller / friendlier toolbar on phones: drop the "today" button so the
  // remaining controls have room (CSS stacks the chunks vertically below 576px).
  function toolbarFor(isMobile) {
    return isMobile
      ? { left: 'prev,next',        center: 'title', right: 'dayGridMonth,listWeek' }
      : { left: 'prev,next today',  center: 'title', right: 'dayGridMonth,listWeek' };
  }

  var cal = new FullCalendar.Calendar(el, {
    initialView: mqMobile.matches ? 'listWeek' : 'dayGridMonth',
    headerToolbar: toolbarFor(mqMobile.matches),
    height: 'auto',
    selectable: canRequest,
    dayMaxEventRows: 3,
    events: function (info, success, fail) {
      var url = feedUrl + '?start=' + encodeURIComponent(info.startStr)
                       + '&end='   + encodeURIComponent(info.endStr);
      fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(success)
        .catch(fail);
    },
    dateClick: function (info) {
      if (!canRequest) return;
      // Don't navigate to request form if user clicked on a past day
      var today = new Date(); today.setHours(0,0,0,0);
      var d = new Date(info.dateStr); d.setHours(0,0,0,0);
      if (d < today) return;
      window.location = requestUrl + '?date=' + encodeURIComponent(info.dateStr);
    },
    windowResize: function () {
      cal.setOption('headerToolbar', toolbarFor(mqMobile.matches));
    },
  });
  cal.render();
})();
</script>
