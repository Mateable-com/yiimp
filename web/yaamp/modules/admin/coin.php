<?php

$id = getiparam('id');
$coin = getdbo('db_coins', $id);
if (!$coin) {
	$this->goback();
}

$this->pageTitle = 'Wallet - '.$coin->symbol;

// force a refresh after 10mn to prevent memory leaks in chrome
app()->clientScript->registerMetaTag('600', null, 'refresh');

if (!empty($coin->algo) && $coin->algo != 'PoS')
	user()->setState('yaamp-algo', $coin->algo);

$remote = new WalletRPC($coin);
$info = $remote->getinfo();

$sellamount = $coin->balance;

echo '<div class="container-fluid py-4">';

// --- Admin Navigation Bar ---
echo getAdminSideBarLinks();

echo '<div class="card shadow-lg border-0 rounded-4 overflow-hidden mb-4">';
echo '  <div class="card-header bg-dark text-white p-4 d-flex align-items-center border-0">';
echo '    <img src="'.$coin->image.'" width="48" class="me-4 shadow-sm rounded-circle p-1 bg-white">';
echo '    <div class="flex-grow-1">';
echo '      <h3 class="mb-0 fw-bold">'.$coin->name.' <span class="text-primary">('.$coin->symbol.')</span></h3>';
echo '      <div class="d-flex gap-3 small text-muted mt-1">';
echo '        <span><i class="fa fa-microchip me-1"></i>Algo: <b>'.$coin->algo.'</b></span>';
echo '        <span><i class="fa fa-plug me-1"></i>RPC: <b>'.$coin->rpchost.':'.$coin->rpcport.'</b></span>';
echo '        <span><i class="fa fa-code-branch me-1"></i>Version: <b>'.formatWalletVersion($coin).'</b></span>';
echo '      </div>';
echo '    </div>';
echo '    <div class="ms-auto">';
echo '      <a href="/admin/coinwallets" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa fa-arrow-left me-1"></i> Back to Wallets</a>';
echo '    </div>';
echo '  </div>';

echo '  <div class="card-body bg-light border-bottom py-3 px-4">';
echo      getAdminWalletLinks($coin, $info, 'wallet');
echo '  </div>';

echo '  <div class="card-body p-4">';
echo '    <div class="row g-4">';
echo '      <div class="col-lg-8" id="main_results">';
echo '        <div class="text-center py-5">';
echo '          <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
echo '          <p class="mt-2 text-muted">Fetching wallet records...</p>';
echo '        </div>';
echo '      </div>';

echo '      <div class="col-lg-4">';
echo '        <div class="card shadow-sm border-0 rounded-3 mb-4">';
echo '          <div class="card-header bg-white fw-bold small text-uppercase py-3">Management Actions</div>';
echo '          <div class="card-body">';
echo '            <div class="d-grid gap-2">';
echo '              <a href="/admin/checkblocks?id='.$coin->id.'" class="btn btn-outline-primary text-start fw-bold"><i class="fa fa-sync-alt me-2"></i>Update Blocks</a>';
echo '              <a href="/admin/payuserscoin?id='.$coin->id.'" class="btn btn-outline-success text-start fw-bold"><i class="fa fa-money-bill-wave me-2"></i>Trigger Payments</a>';
echo '              <a href="/admin/clearearnings?id='.$coin->id.'" class="btn btn-outline-warning text-start fw-bold"><i class="fa fa-eraser me-2"></i>Clear Earnings</a>';
echo '              <hr class="my-2">';
echo '              <a href="/admin/deleteearnings?id='.$coin->id.'" class="btn btn-outline-danger text-start fw-bold" onclick="return confirm(\'ARE YOU SURE? THIS IS PERMANENT.\')"><i class="fa fa-trash-alt me-2"></i>PURGE EARNINGS</a>';
echo '            </div>';
echo '          </div>';
echo '        </div>';

if($info) {
    echo '        <div class="card shadow-sm border-0 rounded-3">';
    echo '          <div class="card-header bg-white fw-bold small text-uppercase py-3">Daemon Information</div>';
    echo '          <div class="card-body p-0">';
    echo '            <div class="table-responsive">';
    echo '              <table class="table table-sm table-borderless mb-0 small">';
    echo '                <tbody>';
    echo '                  <tr class="border-bottom"><td>Balance</td><td class="text-end fw-bold text-primary">'.bitcoinvaluetoa($info['balance']).'</td></tr>';
    echo '                  <tr class="border-bottom"><td>Blocks</td><td class="text-end fw-bold">'.number_format($info['blocks']).'</td></tr>';
    echo '                  <tr class="border-bottom"><td>Difficulty</td><td class="text-end fw-bold">'.Itoa2($info['difficulty'], 4).'</td></tr>';
    echo '                  <tr class="border-bottom"><td>Connections</td><td class="text-end fw-bold">'.$info['connections'].'</td></tr>';
    echo '                </tbody>';
    echo '              </table>';
    echo '            </div>';
    echo '          </div>';
    echo '        </div>';
}

echo '      </div>'; // col-4
echo '    </div>'; // row
echo '  </div>'; // card-body
echo '</div>'; // card

echo '</div>'; // container

?>

<script type="text/javascript">

var main_delay=30000;
var main_timeout;

function main_refresh()
{
	var url = "/admin/coin_results?id=<?=$id?>&rows=500&since=<?=(time() - (7*24*3600))?>";

	clearTimeout(main_timeout);
	$.get(url, '', main_ready).fail(main_error);
}

function main_ready(data)
{
	$('#main_results').html(data);
	$(window).trigger('resize');
	main_timeout = setTimeout(main_refresh, main_delay);
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

$(function() {
    main_refresh();
});

</script>

<style>
    .btn-group .btn { border-radius: 20px !important; margin-right: 4px; }
    .table-responsive { max-height: 800px; }
</style>
