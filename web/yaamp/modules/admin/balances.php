<?php

$exch = getparam('exch');
$this->pageTitle = "Balances" . ($exch ? " - $exch" : "") . " - Admin";

echo '<div class="container-fluid py-4">';

// --- Admin Navigation Bar ---
echo getAdminSideBarLinks();

echo '<div class="row g-4 mb-4 align-items-center">';
echo '  <div class="col-md-6">';
echo '    <h3 class="mb-0 fw-bold text-dark"><i class="fa fa-university me-2 text-primary"></i>Market Asset Balances</h3>';
echo '  </div>';
if ($exch) {
    echo '  <div class="col-md-6 text-md-end">';
    echo '    <span class="badge bg-primary px-3 py-2 fs-6 shadow-sm rounded-pill"><i class="fa fa-exchange-alt me-2"></i>'.htmlspecialchars($exch).'</span>';
    echo '    <a href="/admin/balances" class="btn btn-sm btn-outline-secondary rounded-pill px-3 ms-2">Clear Filter</a>';
    echo '  </div>';
}
echo '</div>';

echo '<div id="main_results" class="mb-4">';
echo '  <div class="text-center py-5">';
echo '    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
echo '    <p class="mt-2 text-muted">Fetching remote market balances...</p>';
echo '  </div>';
echo '</div>';

echo '<div class="card shadow-sm border-0 bg-light rounded-3">';
echo '  <div class="card-body py-3 text-muted small">';
echo '    <i class="fa fa-info-circle me-2 text-primary"></i>This table displays all tracked assets across integrated exchanges. High precision values are truncated for readability. Manual API triggers are available for status verification.';
echo '  </div>';
echo '</div>';

echo '</div>'; // close container

?>

<script type="text/javascript">

var main_delay=60000;
var main_timeout;

function main_ready(data)
{
	$('#main_results').html(data);
	main_timeout = setTimeout(main_refresh, main_delay);
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var url = '/admin/balances_results?exch=<?php echo urlencode($exch);?>';
	clearTimeout(main_timeout);
	$.get(url, '', main_ready).fail(main_error);
}

$(function() {
    main_refresh();
});

</script>
