<?php

$id = getiparam('id');
$coin = getdbo('db_coins', $id);

echo '<div class="container-fluid py-4">';

// --- Explorer Hero Header ---
echo '<div class="card shadow-lg border-0 mb-4 bg-dark text-white rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4 shadow-sm border border-primary border-opacity-25">';
echo '      <i class="fa fa-cubes fa-2x text-primary"></i>';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h2 class="mb-0 fw-bold">Block Explorer '.($coin ? '<span class="text-primary fs-5">('.$coin->symbol.')</span>' : '').'</h2>';
echo '      <p class="text-muted small mb-0 mt-1">Real-time ledger of recently discovered blocks and network rewards.</p>';
echo '    </div>';
echo '    <div class="ms-auto">';
echo '      <a href="/site/mining" class="btn btn-outline-light btn-sm rounded-pill px-4 fw-bold shadow-sm"><i class="fa fa-chart-line me-1 opacity-50"></i> Pool Stats</a>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '<div id="resume_update_button" class="alert alert-warning text-center shadow-sm mb-4 fw-bold animate-pulse" style="cursor: pointer; display: none;" onclick="auto_page_resume();"><i class="fa fa-play me-2"></i>Live Explorer Paused - Click to Resume</div>';

echo '<div id="main_results"></div>';

echo '</div>'; // close container

?>

<script type="text/javascript">
$(function() {
	main_refresh();
});

var main_delay=60000;
var main_timeout;

function main_ready(data) {
	$('#main_results').html(data);
	main_timeout = setTimeout(main_refresh, main_delay);
}

function main_error() {
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function main_refresh() {
	var url = "/site/block_results?id=<?=$id?>";
	clearTimeout(main_timeout);
	$.get(url, '', main_ready).error(main_error);
}
</script>

<style>
    .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
</style>