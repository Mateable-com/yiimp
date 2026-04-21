<?php

$coin = getdbo('db_coins', getiparam('id'));
if (!$coin) $this->goback();

$PoS = ($coin->algo == 'PoS');
$DCR = ($coin->rpcencoding == 'DCR' || $coin->getOfficialSymbol() == 'DCR');
$DGB = ($coin->rpcencoding == 'DGB' || $coin->getOfficialSymbol() == 'DGB');
$ETH = ($coin->rpcencoding == 'GETH');

$remote = new WalletRPC($coin);

$reserved1 = dboscalar("SELECT SUM(balance) FROM accounts WHERE coinid={$coin->id}");
$balance_db = altcoinvaluetoa($coin->balance);

$owed = dboscalar("SELECT SUM(E.amount) AS owed FROM earnings E LEFT JOIN blocks B ON E.blockid = B.id WHERE E.status!=2 AND E.coinid={$coin->id}");
$owed_btc = bitcoinvaluetoa($owed * $coin->price);
$owed_alt = altcoinvaluetoa($owed);

$symbol = !empty($coin->symbol2) ? $coin->symbol2 : $coin->symbol;

echo '<div class="container-fluid py-4">';

// --- Coin Hero Header ---
echo '<div class="card shadow-sm border-0 mb-4 bg-dark text-white overflow-hidden rounded-3">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="me-4 shadow-sm bg-white rounded-circle p-2" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">';
echo '      <img src="'.$coin->image.'" style="max-width: 60px; max-height: 60px;">';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h2 class="mb-1 fw-bold">'.$coin->name.' <span class="text-primary fs-4">('.$coin->symbol.')</span></h2>';
echo '      <div class="d-flex flex-wrap gap-2">';
echo '        <span class="badge bg-primary"><i class="fa fa-microchip me-1 text-white-50"></i>'.$coin->algo.'</span>';
echo '        '.($coin->enable ? '<span class="badge bg-success">ACTIVE</span>' : '<span class="badge bg-danger">DISABLED</span>');
echo '        '.($coin->auto_ready ? '<span class="badge bg-info text-dark">AUTO-READY</span>' : '<span class="badge bg-warning text-dark">MANUAL</span>');
if($coin->auxpow) echo '        <span class="badge bg-purple" style="background-color: #6f42c1;">AUXPOW</span>';
echo '      </div>';
echo '    </div>';
echo '    <div class="text-end d-none d-md-block border-start border-secondary border-opacity-25 ps-4 ms-4">';
echo '      <div class="small text-muted mb-1 text-uppercase fw-bold" style="letter-spacing: 1px;">Current Price</div>';
echo '      <h3 class="mb-0 fw-bold text-success">'.bitcoinvaluetoa($coin->price).' <span class="fs-6 text-muted">BTC</span></h3>';
echo '    </div>';
echo '  </div>';
echo '</div>';

// --- Wallet Errors / Sync Status ---
if(!empty($coin->errors)) {
    echo '<div class="alert alert-danger border-0 shadow-sm mb-4 mx-2 fw-bold"><i class="fa fa-exclamation-triangle me-2"></i>WALLET ERROR: '.htmlspecialchars($coin->errors).'</div>';
}

if($coin->block_height < $coin->target_height) {
    $pct = $coin->target_height > 0 ? round($coin->block_height * 100 / $coin->target_height, 2) : 0;
    echo '<div class="card shadow-sm border-0 mb-4 mx-2 bg-light"><div class="card-body py-2">';
    echo '  <div class="d-flex justify-content-between mb-1 small fw-bold text-muted"><span><i class="fa fa-sync fa-spin me-1"></i> Blockchain Syncing...</span><span>'.$pct.'%</span></div>';
    echo '  <div class="progress" style="height: 8px;"><div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: '.$pct.'%"></div></div>';
    echo '</div></div>';
}

// --- Financial Quick-Stats ---
echo '<div class="row g-3 mb-4">';
$cards = [
    ['label' => 'Pool Balance', 'val' => $balance_db, 'sub' => bitcoinvaluetoa($coin->balance * $coin->price).' BTC', 'color' => 'primary', 'icon' => 'university'],
    ['label' => 'Wallet Owned', 'val' => bitcoinvaluetoa($coin->available), 'sub' => $symbol, 'color' => 'success', 'icon' => 'wallet'],
    ['label' => 'Owed to Miners', 'val' => $owed_alt, 'sub' => $owed_btc.' BTC', 'color' => 'danger', 'icon' => 'users-clock', 'link' => "/admin/earning?id=".$coin->id],
    ['label' => 'Total Cleared', 'val' => altcoinvaluetoa($reserved1), 'sub' => $symbol, 'color' => 'info', 'icon' => 'check-double', 'link' => "/admin/payments?id=".$coin->id],
];
foreach ($cards as $c) {
    echo '<div class="col-md-6 col-lg-3"><div class="card shadow-sm h-100 border-0 rounded-3"><div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-center mb-2"><span class="text-muted small fw-bold text-uppercase" style="font-size: 0.65rem;">'.$c['label'].'</span><i class="fa fa-'.$c['icon'].' text-'.$c['color'].' opacity-25"></i></div>';
    echo '<h4 class="mb-1 fw-bold">'.(isset($c['link']) ? CHtml::link($c['val'], $c['link'], ['class'=>'text-decoration-none text-dark']) : $c['val']).'</h4>';
    echo '<div class="small text-muted">'.$c['sub'].'</div></div></div></div>';
}
echo '</div>';

// --- Main Row: Markets & Technical ---
echo '<div class="row g-4 mb-4">';

// Left: Markets
echo '<div class="col-lg-8">';
echo '  <div class="card shadow-sm border-0 rounded-3 h-100">';
echo '    <div class="card-header bg-white py-3 d-flex align-items-center border-0">';
echo '      <h5 class="mb-0 fw-bold"><i class="fa fa-chart-bar me-2 text-primary"></i>Market Links & Bookmarks</h5>';
echo '      <div class="ms-auto"><a href="/admin/bookmarkAdd?id='.$coin->id.'" class="btn btn-xs btn-outline-success rounded-pill fw-bold px-3">Add Bookmark</a></div>';
echo '    </div>';
echo '    <div class="card-body p-0 table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small"><thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem;"><tr>';
echo '        <th class="ps-4">Market</th><th>Bid</th><th>Ask</th><th>Address</th><th class="text-end">Balance</th><th class="text-end pe-4">Actions</th></tr></thead><tbody>';

$markets = getdbolist('db_markets', "coinid={$coin->id} AND NOT deleted ORDER BY disabled, priority DESC, price DESC");
foreach ($markets as $m) {
    $rowClass = $m->disabled ? 'opacity-50 grayscale bg-light' : '';
    echo '<tr class="'.$rowClass.'"><td class="ps-4 fw-bold">'.CHtml::link($m->name, getMarketUrl($coin, $m->name), ['target'=>'_blank','class'=>'text-decoration-none']).'</td>';
    echo '<td class="text-success fw-bold">'.bitcoinvaluetoa($m->price).'</td><td class="text-muted">'.bitcoinvaluetoa($m->price2).'</td>';
    echo '<td><span class="text-muted" title="'.$m->deposit_address.'">'.(empty($m->deposit_address)?'-':substr($m->deposit_address,0,12).'...').'</span></td>';
    echo '<td class="text-end">'.($m->balance > 0 ? bitcoinvaluetoa($m->balance) : '-').'</td>';
    echo '<td class="text-end pe-4"><div class="btn-group">';
    if (!empty($m->deposit_address)) {
        $name = CJavaScript::encode($m->name); $addr = CJavaScript::encode($m->deposit_address);
        echo CHtml::link("Send", "javascript:;", ['onclick' => "return showSellAmountDialog($name, $addr, {$m->id});", 'class'=>'btn btn-xs btn-primary text-white py-0 px-2']);
    }
    echo '<a href="/market/update?id='.$m->id.'" class="btn btn-xs btn-outline-secondary py-0 px-2"><i class="fa fa-edit"></i></a>';
    echo '</div></td></tr>';
}
echo '      </tbody></table></div></div></div>';

// Right: Technical Info
echo '<div class="col-lg-4">';
echo '  <div class="card shadow-sm border-0 rounded-3 h-100">';
echo '    <div class="card-header bg-primary text-white py-3 border-0"><h5 class="mb-0 fw-bold"><i class="fa fa-cog me-2"></i>Technical Info</h5></div>';
echo '    <div class="card-body p-0"><ul class="list-group list-group-flush">';
$info = $remote->getinfo();
if ($info) {
    $rows = [['label'=>'RPC Host', 'val'=>$coin->rpchost], ['label'=>'RPC Port', 'val'=>$coin->rpcport], ['label'=>'Difficulty', 'val'=>round_difficulty($coin->difficulty)], ['label'=>'Blocks', 'val'=>number_format($coin->block_height)], ['label'=>'Connections', 'val'=>arraySafeVal($info,'connections',0)]];
    foreach($rows as $r) {
        echo '<li class="list-group-item d-flex justify-content-between align-items-center py-2 px-4">';
        echo '<span class="text-muted small fw-bold text-uppercase">'.$r['label'].'</span><b class="text-dark">'.$r['val'].'</b></li>';
    }
} else { echo '<li class="list-group-item text-danger py-4 text-center">WALLET OFFLINE: '.$remote->error.'</li>'; }
echo '    </ul></div>';
echo '    <div class="card-footer bg-light border-0 p-3"><a href="/admin/coinupdate?id='.$coin->id.'" class="btn btn-sm btn-primary w-100 fw-bold shadow-sm">Edit Coin Configuration</a></div>';
echo '  </div></div></div>';

// --- Transactions Row ---
echo '<div class="card shadow-sm border-0 rounded-3">';
echo '  <div class="card-header bg-dark text-white py-3"><h5 class="mb-0 fw-bold"><i class="fa fa-list-ul me-2 text-info"></i>Recent Transactions</h5></div>';
echo '  <div class="card-body p-0 table-responsive">';
echo '    <table class="table table-hover table-sm mb-0 small"><thead class="table-light text-muted"><tr>';
echo '      <th class="ps-4">Time</th><th>Category</th><th>Amount</th><th>Confirmations</th><th>Address</th><th class="pe-4">Explorer</th></tr></thead><tbody>';

$maxrows = (int) arraySafeVal($_GET, 'rows', 50);
$account = ($coin->symbol == "BTC" || $DCR || $DGB) ? '*' : '';
$txs = $remote->listtransactions($account, $maxrows);

if (!empty($txs) && is_array($txs)) {
    krsort($txs);
    foreach($txs as $tx) {
        $category = arraySafeVal($tx, 'category');
        if ($category == 'spent') continue;
        $badge = ($category == 'receive') ? 'bg-success' : (($category == 'immature') ? 'bg-warning text-dark' : 'bg-secondary');
        echo '<tr><td class="ps-4 fw-bold">'.datetoa2($tx['time']).'</td>';
        echo '<td><span class="badge '.$badge.' text-uppercase" style="font-size: 0.6rem;">'.$category.'</span></td>';
        echo '<td class="fw-bold">'.arraySafeVal($tx, 'amount', 0).'</td>';
        echo '<td>'.arraySafeVal($tx, 'confirmations', 0).'</td>';
        echo '<td>'.(isset($tx['address']) ? substr($tx['address'],0,15).'...' : '-').'</td>';
        echo '<td class="pe-4">'.(isset($tx['txid']) ? $coin->createExplorerLink(substr($tx['txid'],0,8), ['txid'=>$tx['txid']], ['target'=>'_blank']) : '-').'</td></tr>';
    }
} else { echo '<tr><td colspan="6" class="py-4 text-center text-muted">No recent transactions found or wallet unreachable.</td></tr>'; }

echo '</tbody></table></div></div></div>';
?>