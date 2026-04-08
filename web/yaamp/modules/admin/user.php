<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

$symbol = getparam('symbol', 'all');
$this->pageTitle = 'Users - Admin';

echo '<div class="container-fluid py-4">';

// --- Admin Navigation Bar ---
echo getAdminSideBarLinks();

echo '<div class="row g-4 mb-4 align-items-center">';
echo '  <div class="col-md-6">';
echo '    <h3 class="mb-0 fw-bold text-dark"><i class="fa fa-users me-2 text-primary"></i>User Management</h3>';
echo '  </div>';

echo '  <div class="col-md-6">';
echo '    <div class="d-flex justify-content-md-end gap-2">';
echo '      <div class="input-group shadow-sm border-0 rounded-pill overflow-hidden" style="max-width: 250px;">';
echo '        <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-coins text-muted small"></i></span>';
echo '        <select id="coin_select" class="form-select border-0 bg-white small fw-bold">';
echo '          <option value="all">All Assets</option>';

$list = getdbolist('db_coins', "enable AND (id IN (SELECT DISTINCT coinid FROM accounts WHERE balance>0.0001) OR id IN (SELECT DISTINCT coinid from earnings)) ORDER BY symbol");
foreach($list as $coin) {
	$selected = ($coin->symbol == $symbol) ? 'selected' : '';
	echo '<option value="'.$coin->symbol.'" '.$selected.'>'.$coin->symbol.'</option>';
}
echo '        </select>';
echo '      </div>';

echo '      <div class="input-group shadow-sm border-0 rounded-pill overflow-hidden" style="max-width: 300px;">';
echo '        <span class="input-group-text bg-white border-0 ps-3"><i class="fa fa-search text-muted small"></i></span>';
echo '        <input class="form-control border-0 bg-white small search" type="search" data-column="all" placeholder="Search addresses..." />';
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</div>';

echo '<div id="main_results" class="mb-5">';
echo '  <div class="text-center py-5">';
echo '    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
echo '    <p class="mt-2 text-muted">Retrieving user data...</p>';
echo '  </div>';
echo '</div>';

echo '</div>'; // close container

?>

<script type="text/javascript">

$(function()
{
	$('#coin_select').change(function(event)
	{
		var symbol = $('#coin_select').val();
		clearTimeout(main_timeout);
		window.location.href = '/admin/user?symbol='+symbol;
	});

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
	var symbol = $('#coin_select').val();
	var url = "/admin/user_results?symbol="+symbol;

	clearTimeout(main_timeout);
	lastSearch = $('input.search').val();
	$.get(url, '', main_ready).fail(main_error);
}

</script>

<style>
    .search:focus, #coin_select:focus { box-shadow: none !important; }
</style>
