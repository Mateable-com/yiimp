<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

$server = getparam('server');
$this->pageTitle = 'Wallets - Admin';

echo '<div class="container-fluid py-4">';

// --- Admin Navigation Bar ---
echo getAdminSideBarLinks();

echo '<div class="row g-4 mb-4 align-items-center">';
echo '  <div class="col-md-6">';
echo '    <div class="d-flex align-items-center gap-3">';
echo '      <h3 class="mb-0 fw-bold text-dark"><i class="fa fa-wallet me-2 text-primary"></i>Coin Wallets</h3>';
echo '      <a href="/admin/coincreate" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm"><i class="fa fa-plus-circle me-2"></i>ADD NEW COIN</a>';
echo '    </div>';
echo '  </div>';

echo '  <div class="col-md-6">';
echo '    <div class="d-flex justify-content-md-end gap-2">';
echo '      <div class="input-group shadow-sm border-0 rounded-pill overflow-hidden" style="max-width: 300px;">';
echo '        <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-server text-muted small"></i></span>';
echo '        <select id="server_select" class="form-select border-0 bg-white small fw-bold">';
echo '          <option value="">All Servers</option>';
$serverlist = dbolist("SELECT DISTINCT rpchost FROM coins WHERE rpchost != '' ORDER BY rpchost");
foreach ($serverlist as $srv) {
    $selected = ($server == $srv['rpchost']) ? 'selected' : '';
    echo '<option value="'.$srv['rpchost'].'" '.$selected.'>'.$srv['rpchost'].'</option>';
}
echo '        </select>';
echo '      </div>';

echo '      <div class="input-group shadow-sm border-0 rounded-pill overflow-hidden" style="max-width: 250px;">';
echo '        <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-search text-muted small"></i></span>';
echo '        <input class="form-control border-0 bg-white small search" type="search" data-column="all" placeholder="Filter wallets..." />';
echo '      </div>';

echo '      <a href="/admin/emptymarkets" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" title="Empty Markets"><i class="fa fa-shopping-cart"></i></a>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '<div id="main_results" class="mb-5">';
echo '  <div class="text-center py-5">';
echo '    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
echo '    <p class="mt-2 text-muted">Synchronizing wallet data...</p>';
echo '  </div>';
echo '</div>';

echo '</div>'; // close container

?>

<script type="text/javascript">

$('#server_select').change(function(event)
{
	var server = $('#server_select').val();
	clearTimeout(main_timeout);
	window.location.href = '/admin/coinwallets?server='+server;
});

$(function()
{
	main_refresh();
});

var main_delay=30000;
var main_timeout;
var lastSearch = false;
var checkedIds = [];

function saveChecked()
{
	checkedIds = [];
	$('.coin-checkbox:checked').each(function() {
		checkedIds.push($(this).val());
	});
}

function restoreChecked()
{
	if (checkedIds.length === 0) return;
	$('.coin-checkbox').each(function() {
		if (checkedIds.indexOf($(this).val()) !== -1) {
			$(this).prop('checked', true);
		}
	});
	if (typeof updateBulkBtn === 'function') updateBulkBtn();
}

function main_ready(data)
{
	saveChecked();
	$('#main_results').html(data);
	restoreChecked();

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
	var url = "/admin/coinwallet_results?server=<?=urlencode($server)?>";

	clearTimeout(main_timeout);
	lastSearch = $('input.search').val();
	$.get(url, '', main_ready).fail(main_error);
}

</script>

<style>
    .search:focus, #server_select:focus { box-shadow: none !important; }
    .btn-group .btn.active { background-color: #0d6efd !important; color: white !important; }
    .btn-group .btn { border-color: #dee2e6 !important; }
</style>
