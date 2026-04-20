<?php

$this->pageTitle = 'Coin Health - Admin';

echo '<div class="container-fluid py-4">';
echo getAdminSideBarLinks();

echo '<div class="d-flex align-items-center gap-3 mb-4">';
echo '  <h3 class="mb-0 fw-bold text-dark"><i class="fa fa-heartbeat me-2 text-danger"></i>Coin Health Monitor</h3>';
echo '  <span class="text-muted small">Auto-refreshes every 60s &mdash; data from last cronjob cycle</span>';
echo '  <button class="btn btn-sm btn-outline-secondary ms-auto rounded-pill px-3" onclick="main_refresh()"><i class="fa fa-sync-alt me-1"></i>Refresh Now</button>';
echo '</div>';

echo '<div id="health_summary" class="row g-3 mb-4"></div>';
echo '<div id="main_results">';
echo '  <div class="text-center py-5">';
echo '    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>';
echo '    <p class="mt-2 text-muted">Checking wallet health…</p>';
echo '  </div>';
echo '</div>';

echo '</div>';

?>
<script>
var main_delay = 60000;
var main_timeout;

function main_refresh() {
    clearTimeout(main_timeout);
    $.get('/admin/health_results', '', main_ready).fail(main_error);
}

function main_ready(data) {
    var parsed = typeof data === 'string' ? JSON.parse(data) : data;
    $('#health_summary').html(parsed.summary);
    $('#main_results').html(parsed.table);
    main_timeout = setTimeout(main_refresh, main_delay);
}

function main_error() {
    main_timeout = setTimeout(main_refresh, main_delay * 2);
}

$(function() { main_refresh(); });
</script>
