<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

$algo = user()->getState('yaamp-algo');
$this->pageTitle = 'Workers - Admin';

echo '<div class="container-fluid py-4">';

// --- Admin Navigation Bar ---
echo getAdminSideBarLinks();

echo '<div class="row g-4 mb-4 align-items-center">';
echo '  <div class="col-md-6">';
echo '    <h3 class="mb-0 fw-bold text-dark"><i class="fa fa-microchip me-2 text-primary"></i>Rig Management</h3>';
echo '  </div>';

echo '  <div class="col-md-6">';
echo '    <div class="d-flex justify-content-md-end gap-2">';
echo '      <div class="input-group shadow-sm border-0 rounded-pill overflow-hidden" style="max-width: 250px;">';
echo '        <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-filter text-muted small"></i></span>';
echo '        <select id="algo_select" class="form-select border-0 bg-white small fw-bold">';
$algos = yaamp_get_algos();
foreach ($algos as $a) {
    $selected = ($a == $algo) ? 'selected' : '';
    echo '<option value="'.$a.'" '.$selected.'>'.strtoupper($a).'</option>';
}
echo '        </select>';
echo '      </div>';

echo '      <div class="input-group shadow-sm border-0 rounded-pill overflow-hidden" style="max-width: 300px;">';
echo '        <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-search text-muted small"></i></span>';
echo '        <input class="form-control border-0 bg-white small search" type="search" data-column="all" placeholder="Search rigs..." />';
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '<div id="main_results" class="mb-5">';
echo '  <div class="text-center py-5">';
echo '    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
echo '    <p class="mt-2 text-muted">Scanning active rigs...</p>';
echo '  </div>';
echo '</div>';

echo '</div>'; // close container

?>

<script type="text/javascript">

$(function()
{
	main_refresh();
});

var main_delay=30000;
var main_timeout;
var lastSearch = false;

function main_ready(data)
{
	$('#main_results').html(data);
	
	if (lastSearch !== false) {
		$('input.search').val(lastSearch);
		$('table.dataGrid').trigger('search');
	}

	main_timeout = setTimeout(main_refresh, main_delay);
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var url = '/admin/worker_results?algo=' + $('#algo_select').val();

	clearTimeout(main_timeout);
	lastSearch = $('input.search').val();
	$.get(url, '', main_ready).fail(main_error);
}

$('#algo_select').change(function() {
	main_refresh();
});

</script>

<style>
    .search:focus, #algo_select:focus { box-shadow: none !important; }
</style>
