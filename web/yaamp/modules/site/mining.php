<?php

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.cursor.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$algo = user()->getState('yaamp-algo');
$target = yaamp_hashrate_constant($algo);
$interval = yaamp_hashrate_step();
$delay = time()-$interval;

echo '<div class="container-fluid py-4">';

// --- Stats Hero Header ---
echo '<div class="card shadow-lg border-0 mb-4 bg-dark text-white rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4 shadow-sm border border-primary border-opacity-25">';
echo '      <i class="fa fa-chart-line fa-2x text-primary"></i>';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h2 class="mb-0 fw-bold">Network Statistics <span class="text-primary fs-5 text-uppercase">'.($algo == 'all' ? 'Global' : $algo).'</span></h2>';
echo '      <p class="text-muted small mb-0 mt-1">Real-time performance metrics and historical data for all active algorithms.</p>';
echo '    </div>';
echo '    <div class="ms-auto d-flex gap-2">';
echo '      <div class="dropdown">';
echo '        <button class="btn btn-outline-light btn-sm rounded-pill px-4 dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">Select Algorithm</button>';
echo '        <ul class="dropdown-menu dropdown-menu-end shadow border-0">';
echo '          <li><a class="dropdown-item fw-bold" href="javascript:select_algo(\'all\')">All Algorithms</a></li>';
echo '          <li><hr class="dropdown-divider"></li>';
foreach(yaamp_get_algos() as $a) {
    echo '<li><a class="dropdown-item" href="javascript:select_algo(\''.$a.'\')">'.strtoupper($a).'</a></li>';
}
echo '        </ul></div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

// --- Results Containers ---
echo '<div id="resume_update_button" class="alert alert-warning text-center shadow-sm mb-4 fw-bold" style="cursor: pointer; display: none;" onclick="auto_page_resume();"><i class="fa fa-play me-2"></i>Live Data Paused - Click to Resume</div>';

echo '<div class="row g-4">';
echo '  <div class="col-lg-12">';
echo '    <div id="pool_current_results"></div>';
echo '  </div>';
echo '  <div class="col-lg-12">';
echo '    <div id="pool_mining_results"></div>';
echo '  </div>';
echo '  <div class="col-lg-12">';
echo '    <div id="pool_history_results"></div>';
echo '  </div>';
echo '</div>';

echo '</div>'; // close container

?>

<script type="text/javascript">
function page_refresh() {
    pool_current_refresh();
    pool_mining_refresh();
    pool_history_refresh();
}

function select_algo(algo) {
    window.location.href = '/site/algo?algo='+algo+'&r=/site/mining';
}

function pool_current_ready(data) { $('#pool_current_results').html(data); }
function pool_current_refresh() { $.get("/site/current_results", '', pool_current_ready); }

function pool_mining_ready(data) { $('#pool_mining_results').html(data); }
function pool_mining_refresh() { $.get("/site/mining_results", '', pool_mining_ready); }

function pool_history_ready(data) { $('#pool_history_results').html(data); }
function pool_history_refresh() { $.get("/site/history_results", '', pool_history_ready); }

$(function() {
    pool_current_refresh();
    pool_mining_refresh();
    pool_history_refresh();
});
</script>
