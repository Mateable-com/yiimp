<?php

$this->pageTitle = 'Network Connections - Admin';

echo '<div class="container-fluid py-4">';

// --- Admin Navigation Bar ---
echo getAdminSideBarLinks();

echo '<div class="row g-4 mb-4 align-items-center">';
echo '  <div class="col-md-6">';
echo '    <h3 class="mb-0 fw-bold text-dark"><i class="fa fa-network-wired me-2 text-primary"></i>Stratum Connections</h3>';
echo '  </div>';
echo '</div>';

echo '<div id="main_results" class="mb-5">';
echo '  <div class="text-center py-5">';
echo '    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
echo '    <p class="mt-2 text-muted">Polling active stratum nodes...</p>';
echo '  </div>';
echo '</div>';

echo '</div>'; // close container

?>

<script type="text/javascript">

var main_delay = 30000;

$(function()
{
	main_refresh();
});

function main_ready(data)
{
	$('#main_results').html(data);
	setTimeout(main_refresh, main_delay);
}

function main_error()
{
	setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var url = "/admin/connections_results";
	$.get(url, '', main_ready).fail(main_error);
}

</script>
