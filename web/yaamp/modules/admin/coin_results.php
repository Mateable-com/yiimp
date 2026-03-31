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
echo '<div class="card shadow-sm border-0 mb-4 bg-dark text-white overflow-hidden">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="me-4 shadow-sm bg-white rounded-circle p-2" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">';
echo '      <img src="'.$coin->image.'" style="max-width: 60px; max-height: 60px;">';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h2 class="mb-1 fw-bold">'.$coin->name.' <span class="text-primary fs-4">('.$coin->symbol.')</span></h2>';
echo '      <div class="d-flex flex-wrap gap-2">';
echo '        <span class="badge bg-primary"><i class="fa fa-microchip me-1"></i>'.$coin->algo.'</span>';
echo '        '.($coin->enable ? '<span class="badge bg-success">ACTIVE</span>' : '<span class="badge bg-danger">DISABLED</span>');
echo '        '.($coin->auto_exchange ? '<span class="badge bg-info text-dark">AUTO-EXCHANGE</span>' : '<span class="badge bg-warning text-dark">MINING ONLY</span>');
echo '        '.($coin->auxpow ? '<span class="badge bg-purple" style="background-color: #6f42c1;">AUXPOW</span>' : '');
echo '      </div>';
if (YAAMP_ALLOW_EXCHANGE) {
    $reserved2 = bitcoinvaluetoa(dboscalar("SELECT SUM(amount*price) FROM earnings WHERE status!=2 AND userid IN (SELECT id FROM accounts WHERE coinid={$coin->id})"));
    echo '<div class="mt-2 small text-info fw-bold">Earnings: '.$reserved2.' BTC</div>';
}
echo '    </div>';
echo '    <div class="text-end d-none d-md-block">';
echo '      <div class="small text-muted mb-1 text-uppercase">Current Price</div>';
echo '      <h3 class="mb-0 fw-bold text-success">'.bitcoinvaluetoa($coin->price).' <span class="fs-6 text-muted">BTC</span></h3>';
echo '    </div>';
echo '  </div>';
echo '</div>';

// --- Quick Financial Cards ---
echo '<div class="row g-3 mb-4">';
$cards = [
    ['label' => 'Balance (DB)', 'val' => $balance_db, 'sub' => bitcoinvaluetoa($coin->balance * $coin->price).' BTC', 'color' => 'primary', 'icon' => 'database'],
    ['label' => 'Owned (Wallet)', 'val' => bitcoinvaluetoa($coin->available), 'sub' => $symbol, 'color' => 'success', 'icon' => 'vault'],
    ['label' => 'Owed to Miners', 'val' => $owed_alt, 'sub' => $owed_btc.' BTC', 'color' => 'danger', 'icon' => 'user-clock', 'link' => "/admin/earning?id=".$coin->id],
    ['label' => 'Total Cleared', 'val' => altcoinvaluetoa($reserved1), 'sub' => $symbol, 'color' => 'info', 'icon' => 'check-double', 'link' => "/admin/payments?id=".$coin->id],
];
foreach ($cards as $c) {
    echo '<div class="col-md-6 col-lg-3"><div class="card shadow-sm h-100 border-start border-'.$c['color'].' border-4"><div class="card-body">';
    echo '<div class="d-flex justify-content-between align-items-center mb-2"><span class="text-muted small fw-bold text-uppercase">'.$c['label'].'</span><i class="fa fa-'.$c['icon'].' text-'.$c['color'].' opacity-50"></i></div>';
    echo '<h4 class="mb-1 fw-bold">'.(isset($c['link']) ? CHtml::link($c['val'], $c['link'], ['class'=>'text-decoration-none text-dark']) : $c['val']).'</h4>';
    echo '<div class="small text-muted">'.$c['sub'].'</div></div></div></div>';
}
echo '</div>';

// --- Market & Technical Row ---
echo '<div class="row g-4 mb-4"><div class="col-lg-12"><div class="card shadow-sm"><div class="card-header bg-white py-3 d-flex align-items-center">';
echo '<h5 class="mb-0 fw-bold me-auto"><i class="fa fa-university me-2 text-primary"></i>Market Management</h5>';
echo '<a href="/admin/bookmarkAdd?id='.$coin->id.'" class="btn btn-sm btn-outline-success"><i class="fa fa-plus me-1"></i>Add Bookmark</a></div>';
echo '<div class="card-body p-0 table-responsive"><table class="table table-hover align-middle mb-0 small"><thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;"><tr>';
echo '<th>Market/Label</th><th>Bid</th><th>Ask</th><th>Address</th><th>Balance</th><th>Locked</th><th>Sent</th><th>Traded</th><th>Message</th><th>Actions</th></tr></thead><tbody>';

$markets = getdbolist('db_markets', "coinid={$coin->id} AND NOT deleted ORDER BY disabled, priority DESC, price DESC");
$bestmarket = getBestMarket($coin);
foreach ($markets as $m) {
    $rowClass = $m->disabled ? 'opacity-50 grayscale' : ($bestmarket && $m->id == $bestmarket->id ? 'table-success' : '');
    $late = $m->lastsent > $m->lasttraded && $m->lasttraded ? '<span class="badge bg-danger">LATE</span>' : '';
    echo '<tr class="'.$rowClass.'"><td class="fw-bold"><a href="'.getMarketUrl($coin, $m->name).'" target="_blank" class="text-decoration-none">'.$m->name.'</a></td>';
    echo '<td class="text-success fw-bold">'.bitcoinvaluetoa($m->price).'</td><td class="text-muted">'.bitcoinvaluetoa($m->price2).'</td><td>';
    if (!empty($m->deposit_address)) {
        $name = CJavaScript::encode($m->name); $addr = CJavaScript::encode($m->deposit_address);
        echo CHtml::link(YAAMP_ALLOW_EXCHANGE ? "sell" : "send", "javascript:;", ['onclick' => "return showSellAmountDialog($name, $addr, {$m->id});", 'class'=>'btn btn-xs btn-primary py-0 px-1 me-1']);
        echo '<span class="text-muted" title="'.$m->deposit_address.'">'.substr($m->deposit_address,0,10).'...</span>';
    }
    echo '</td><td>'.($m->balance > 0 ? bitcoinvaluetoa($m->balance) : '-').'</td><td>'.($m->ontrade > 0 ? bitcoinvaluetoa($m->ontrade) : '-').'</td>';
    echo '<td>'.(empty($m->lastsent) ? "" : datetoa2($m->lastsent).' ago').'</td><td>'.(empty($m->lasttraded) ? "" : datetoa2($m->lasttraded).' ago').'</td>';
    echo '<td>'.$late.' <span class="small text-muted">'.$m->message.'</span></td>';
    echo '<td><div class="btn-group"><a href="/market/update?id='.$m->id.'" class="btn btn-sm btn-outline-secondary py-0 px-1"><i class="fa fa-edit"></i></a>';
    if ($m->disabled) echo '<a href="/market/enable?id='.$m->id.'&en=1" class="btn btn-sm btn-outline-success py-0 px-1"><i class="fa fa-play"></i></a>';
    else echo '<a href="/market/enable?id='.$m->id.'&en=0" class="btn btn-sm btn-outline-warning py-0 px-1"><i class="fa fa-pause"></i></a>';
    echo '<a href="/market/delete?id='.$m->id.'" class="btn btn-sm btn-outline-danger py-0 px-1"><i class="fa fa-trash"></i></a></div></td></tr>';
}
$bookmarks = getdbolist('db_bookmarks', "idcoin={$coin->id} ORDER BY lastused DESC");
foreach ($bookmarks as $b) {
    echo '<tr class="table-light opacity-75"><td class="fw-bold"><i class="fa fa-bookmark text-warning me-1"></i>'.$b->label.'</td><td colspan="2"></td><td>';
    if (!empty($b->address)) {
        $name = CJavaScript::encode($b->label); $addr = CJavaScript::encode($b->address);
        echo CHtml::link("send", "javascript:;", ['onclick' => "return showSellAmountDialog($name, $addr, 0, {$b->id});", 'class'=>'btn btn-xs btn-warning py-0 px-1 me-1']);
        echo '<span class="text-muted">'.substr($b->address,0,10).'...</span>';
    }
    echo '</td><td colspan="2"></td><td class="small">'.(empty($b->lastused) ? "" : datetoa2($b->lastused).' ago').'</td><td colspan="2"></td>';
    echo '<td><a href="/admin/bookmarkDel?id='.$b->id.'" class="btn btn-sm btn-outline-danger py-0 px-1"><i class="fa fa-times"></i></a></td></tr>';
}
echo '</tbody></table></div></div></div></div>';

// --- Technical & Transactions Row ---
echo '<div class="row g-4 mb-4"><div class="col-lg-8"><div class="card shadow-sm h-100"><div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="fa fa-list-ul me-2 text-primary"></i>Recent Transactions</h5></div>';
echo '<div class="card-body p-0 table-responsive"><table class="table table-hover table-sm mb-0 small"><thead class="table-light text-muted"><tr>';
echo '<th>Time</th><th>Category</th><th>Amount</th><th>Height</th><th>Diff</th><th>Confirm</th><th>Address</th><th>Tx</th></tr></thead><tbody>';

// --- START RESTORED TRANSACTION LOGIC ---
$list_since = arraySafeVal($_GET, 'since', time() - (7 * 24 * 3600));
$maxrows = (int) arraySafeVal($_GET, 'rows', 500);
$maxrows = max($maxrows, 250); $maxrows = min($maxrows, 2500);

$account = '';
if ($DCR || $DGB) $account = '*';
else if ($ETH) $account = $coin->master_wallet;
else if ($coin->symbol == "BTC") $account = '*';

$txs = $remote->listtransactions($account, $maxrows);
if (empty($txs)) { $account = '*'; $txs = $remote->listtransactions($account, $maxrows); }
if (empty($txs)) { if (!empty($remote->error)) echo "<tr><td colspan='8' class='text-danger ps-3'>RPC Error: {$remote->error}</td></tr>"; $txs = $remote->listtransactions($account, 200); }

$txs_array = array(); $lastday = '';
$info = $remote->getinfo();
if (!empty($txs)) {
    $tx = reset($txs);
    if (count($txs) == $maxrows && isset($tx['time'])) $lastday = strftime('%F', $tx['time']);
    foreach ($txs as $tx) { if (arraySafeVal($tx, 'time', $list_since + 1) > $list_since) $txs_array[] = $tx; }
    krsort($txs_array);
}

if ($DCR && !empty($info)) {
    $amountin_mul = $info['version'] >= 10500 ? 1.0 : 0.00000001;
    $prev_tx = array(); $lastday = '';
    foreach ($txs_array as $key => $tx) {
        $txs_array[$key]['time'] = min($tx['timereceived'], arraySafeVal($tx, 'blocktime', $tx['time']));
        $prev_txid = arraySafeVal($prev_tx, "txid"); $category = $tx['category'];
        if (arraySafeVal($tx, 'txtype') == 'ticket') {
            $txs_array[$key]['category'] = 'ticket';
            if ($category != 'receive' || $prev_txid === arraySafeVal($tx, "txid")) unset($txs_array[$key]);
            else $txs_array[$key]['amount'] = 0 - $tx['amount'];
            continue;
        }
        if ($category == 'send' && arraySafeVal($tx, 'generated')) { $txs_array[$key]['category'] = 'spent'; }
        else if ($category == 'send' && $tx['amount'] == -0) {
            if ($tx['vout'] > 0) $category = 'spent';
            else if (arraySafeVal($tx, "confirmations") >= 256) $category = 'receive';
            else $category = 'immature';
            if ($category == 'spent' && arraySafeVal($tx, 'txtype') == 'vote') $category = 'unlock';
            $txs_array[$key]['category'] = $category;
            if ($tx['vout'] == 0) { $t = $remote->getrawtransaction($tx['txid'], 1); if ($t && isset($t['vin'][0])) $txs_array[$key]['amount'] = $t['vin'][0]['amountin'] * $amountin_mul; }
            if ($category == 'unlock') { $t = $remote->getrawtransaction($tx['txid'], 1); if ($t && isset($t['vin'][1])) $txs_array[$key]['amount'] = $t['vin'][1]['amountin'] * $amountin_mul; }
        } else if ($category == 'send' && $prev_txid === arraySafeVal($tx, "txid")) { if ($prev_tx['amount'] == 0 - $tx['amount']) $txs_array[$key]['category'] = 'spent'; }
        else if ($category == 'receive') { $prev_tx = $tx; }
        if ($lastday == '' && count($txs) == $maxrows) $lastday = strftime('%F', $tx['time']);
    }
    if ($info['version'] < 1010200) ksort($txs_array);
}

$rows_displayed = 0;
foreach ($txs_array as $tx) {
    if (!isset($tx['amount'])) { if (!isset($tx['reward'])) continue; $tx['amount'] = $tx['reward']; }
    $category = arraySafeVal($tx, 'category');
    if ($category == 'spent') continue;
    $block = isset($tx['blockhash']) ? $remote->getblock($tx['blockhash']) : null;
    $badge = ($category == 'receive') ? 'bg-success' : (($category == 'immature') ? 'bg-warning text-dark' : (($category == 'generate') ? 'bg-info text-dark' : 'bg-secondary'));
    
    echo '<tr>';
    if (!isset($tx['time'])) { echo '<td colspan="8">' . json_encode($tx) . '</td>'; continue; }
    echo '<td class="fw-bold">'.datetoa2($tx['time']).'</td>';
    echo '<td><span class="badge '.$badge.'">'.strtoupper($category).'</span></td>';
    echo '<td class="fw-bold">'.$tx['amount'].'</td>';
    echo '<td>'.($block ? $block['height'] : '').'</td><td>'.($block ? round_difficulty($block['difficulty']) : '').'</td>';
    echo '<td>'.arraySafeVal($tx, 'confirmations').'</td>';
    echo '<td>'.(isset($tx['address']) ? (dboscalar("SELECT count(*) FROM accounts WHERE username='{$tx['address']}'") ? CHtml::link(substr($tx['address'],0,12).'...', '/?address='.$tx['address']) : substr($tx['address'],0,12).'...') : '-').'</td>';
    echo '<td>'.(isset($tx['txid']) ? $coin->createExplorerLink(substr($tx['txid'],0,7), ['txid'=>$tx['txid']], ['target'=>'_blank']) : '-').'</td></tr>';
    if (++$rows_displayed >= $maxrows) break;
}
echo '</tbody></table></div>';
$more_url = '/admin/coin?id='.$coin->id.'&since='.(time()-31*24*3600).'&rows='.($maxrows*2);
echo '<div class="card-footer bg-light py-2 text-center small">'.CHtml::link('Click here to show more transactions...', $more_url, ['class'=>'text-decoration-none']).'</div></div></div>';

// Right: Technical Info & Sums
echo '<div class="col-lg-4"><div class="card shadow-sm border-0 mb-4"><div class="card-header bg-primary text-white py-3 fw-bold"><i class="fa fa-cogs me-2"></i>Technical Info</div><div class="card-body p-0 small"><ul class="list-group list-group-flush">';
if ($info) {
    $zbalance = (!is_null($coin->wallet_zaddress) && trim($coin->wallet_zaddress) != '') ? $remote->z_getbalance(trim($coin->wallet_zaddress)) : null;
    $tech_rows = [['label'=>'Difficulty', 'val'=>round_difficulty($coin->difficulty)], ['label'=>'Height', 'val'=>number_format(arraySafeVal($info, 'blocks', 0))], ['label'=>'Connections', 'val'=>arraySafeVal($info, 'connections', 0), 'link'=>'/admin/coinpeers?id='.$coin->id], ['label'=>'Balance (Wallet)', 'val'=>altcoinvaluetoa($info['balance'])]];
    if ($zbalance) $tech_rows[] = ['label' => 'Z-Balance', 'val' => bitcoinvaluetoa($zbalance)];
    if (isset($info['stake'])) $tech_rows[] = ['label' => 'Staking', 'val' => $info['stake']];
    if ($DCR) {
        $balances = $remote->getbalance('*', 0); $stake = 0; if (isset($balances["balances"])) foreach ($balances["balances"] as $accb) $stake += arraySafeVal($accb, 'lockedbytickets', 0);
        $stakeinfo = $remote->getstakeinfo(); $tech_rows[] = ['label' => 'DCR Tickets', 'val' => $stake.' ('.arraySafeVal($stakeinfo,'live',0).')'];
        $tech_rows[] = ['label' => 'Ticket Price', 'val' => arraySafeVal($stakeinfo,'difficulty'), 'link' => "https://dcrstats.com/"];
    }
    foreach ($tech_rows as $r) {
        echo '<li class="list-group-item d-flex justify-content-between align-items-center py-2"><span class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">'.$r['label'].'</span>';
        echo '<b class="text-dark">'.(isset($r['link']) ? CHtml::link($r['val'], $r['link'], ['class'=>'text-decoration-none', 'target'=>(strpos($r['link'],'http')===0?'_blank':'')]) : $r['val']).'</b></li>';
    }
} else { echo '<li class="list-group-item text-danger py-4 text-center">RPC ERROR: '.$remote->error.'</li>'; }
echo '</ul></div><div class="card-footer bg-light py-2"><a href="/admin/coinupdate?id='.$coin->id.'" class="btn btn-sm btn-primary w-100 fw-bold">Edit Config</a></div></div>';

// Sums Table Card
echo '<div class="card shadow-sm"><div class="card-header bg-dark text-white py-2 fw-bold small">Daily Summary</div><div class="card-body p-0 table-responsive">';
echo '<table class="table table-sm mb-0 small"><thead class="table-light text-muted"><tr><th>Day</th><th>Category</th><th class="text-end">Sum</th><th class="text-end">BTC</th></tr></thead><tbody>';
$sums = [];
foreach ($txs_array as $tx) { if (!isset($tx['time'], $tx['amount']) || arraySafeVal($tx,'category') == 'spent') continue; $day = strftime('%F', $tx['time']); if ($day == $lastday) break; $key = $day.' '.$tx['category']; $sums[$key] = arraySafeVal($sums, $key) + $tx['amount']; }
foreach ($sums as $key => $amount) {
    $parts = explode(' ', $key);
    echo '<tr><td class="fw-bold">'.substr($parts[0], 5).'</td><td>'.$parts[1].'</td><td class="text-end fw-bold text-primary">'.round($amount,4).'</td><td class="text-end small text-muted">'.bitcoinvaluetoa($coin->price * $amount).'</td></tr>';
}
if (empty($sums)) echo '<tr><td colspan="4" class="text-center py-3 text-muted">No activity found</td></tr>';
echo '</tbody></table></div></div></div></div></div>';
?>
