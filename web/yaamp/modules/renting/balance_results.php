<?php

$algo = user()->getState('yaamp-algo');

if(!YAAMP_RENTAL) return;

$renter = getrenterparam(user()->getState('yaamp-deposit'));
if(!$renter) return;

$balance = bitcoinvaluetoa($renter->balance);
$unconfirmed = bitcoinvaluetoa($renter->unconfirmed);
$spent = bitcoinvaluetoa($renter->spent);

$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>YAAMP_RENTER_COIN));
$coin_symbol = $coin ? $coin->symbol : 'BTC';

echo "<div class='card shadow-sm border-0 rounded-4 overflow-hidden mb-4'>";
echo "<div class='card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center'>";
echo "<h5 class='mb-0 fw-bold text-dark'><i class='fa fa-wallet me-2 text-success'></i>Wallet Status</h5>";
echo "<div class='d-flex gap-2'>";
echo "<button class='btn btn-sm btn-outline-primary rounded-pill px-3' onclick='javascript:main_renter_tx()'>Transactions</button>";
echo "<button class='btn btn-sm btn-outline-secondary rounded-pill px-3' onclick='javascript:window.location.href=\"/renting/settings\"'><i class='fa fa-cog'></i></button>";
echo "</div>";
echo "</div>";

echo "<div class='card-body p-4'>";
echo "<div class='row g-4'>";

echo "<div class='col-sm-4'>";
echo "<div class='small text-muted text-uppercase fw-bold mb-1'>Available</div>";
echo "<div class='h4 mb-0 fw-bold text-dark font-monospace'>$balance <small class='text-muted fs-6'>$coin_symbol</small></div>";
echo "</div>";

echo "<div class='col-sm-4'>";
echo "<div class='small text-muted text-uppercase fw-bold mb-1'>Unconfirmed</div>";
echo "<div class='h4 mb-0 fw-bold text-warning font-monospace'>$unconfirmed <small class='text-muted fs-6'>$coin_symbol</small></div>";
echo "</div>";

echo "<div class='col-sm-4'>";
echo "<div class='small text-muted text-uppercase fw-bold mb-1 d-flex justify-content-between'>";
echo "<span>Total Spent</span>";
echo "<a href='javascript:reset_spent()' class='text-decoration-none text-danger' title='Reset spent counter'><i class='fa fa-undo-alt fs-xs'></i></a>";
echo "</div>";
echo "<div class='h4 mb-0 fw-bold text-muted font-monospace'>$spent <small class='text-muted fs-6'>$coin_symbol</small></div>";
echo "</div>";

echo "</div>";
echo "</div>";

echo "<div class='card-footer bg-light border-0 py-3 d-flex justify-content-center gap-3'>";
echo "<button class='btn btn-primary px-4 fw-bold rounded-pill shadow-sm' onclick='javascript:order_new()'><i class='fa fa-plus-circle me-1'></i> Create New Job</button>";
echo "<button class='btn btn-outline-success px-4 fw-bold rounded-pill' onclick='javascript:yaamp_withdraw()'><i class='fa fa-external-link-alt me-1'></i> Withdraw Funds</button>";
echo "</div>";

echo "</div>";
