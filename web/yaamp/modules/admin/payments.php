<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

$coin_id = getiparam('id');
$target_coin = $coin_id ? getdbo('db_coins', $coin_id) : null;
$this->pageTitle = 'Payments' . ($target_coin ? ' - '.$target_coin->symbol : '') . ' - Admin';

echo '<div class="container-fluid py-4">';

// --- Admin Navigation Bar ---
echo getAdminSideBarLinks();

echo '<div class="row g-4 mb-4 align-items-center">';
echo '  <div class="col-md-6">';
echo '    <h3 class="mb-0 fw-bold text-dark"><i class="fa fa-money-check-alt me-2 text-primary"></i>Pending Payouts</h3>';
echo '  </div>';

echo '  <div class="col-md-6">';
echo '    <div class="d-flex justify-content-md-end gap-2">';
if ($target_coin) {
    echo '      <div class="badge bg-success rounded-pill px-3 py-2 d-flex align-items-center"><img src="'.$target_coin->image.'" width="16" class="me-2 rounded-circle shadow-sm"> '.$target_coin->name.' ('.$target_coin->symbol.')</div>';
    echo '      <a href="/admin/payments" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm btn-sm">Clear Filter</a>';
}
echo '      <div class="input-group shadow-sm border-0 rounded-pill overflow-hidden" style="max-width: 300px;">';
echo '        <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-search text-muted small"></i></span>';
echo '        <input class="form-control border-0 bg-white small search" type="search" data-column="all" placeholder="Search payments..." />';
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '<div id="main_results" class="mb-5">';
echo '  <div class="text-center py-5">';
echo '    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
echo '    <p class="mt-2 text-muted">Auditing pending transactions...</p>';
echo '  </div>';
echo '</div>';

echo '</div>'; // close container

?>

<script type="text/javascript">

$(function()
{
	main_refresh();
});

var main_delay=60000;
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
	var url = '/admin/payments_results?id=<?= $coin_id ?>';

	clearTimeout(main_timeout);
	lastSearch = $('input.search').val();
	$.get(url, '', main_ready).fail(main_error);
}

</script>

<style>
    .search:focus { box-shadow: none !important; }
</style>
