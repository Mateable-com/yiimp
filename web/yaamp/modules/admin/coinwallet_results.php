<?php

function valuetocell($amount) {
	$html = $amount ? bitcoinvaluetoa($amount) : '-';
	$html = preg_replace('/([0]+)$/', '<span class="eov text-muted opacity-50">${1}</span>', $html);
	return $html;
}

$server = getparam('server');
if(!empty($server)) {
	$coins = getdbolist('db_coins', "rpchost=:server ORDER BY algo, index_avg DESC",
		array(':server'=>$server));
}
else
	$coins = getdbolist('db_coins', "1 ORDER BY algo, index_avg DESC");

$mining = getdbosql('db_mining');
$algos_shown = [];

echo '<form id="bulk-delete-form" method="post" action="/admin/coinbulkdelete">';
echo '<div class="card shadow-sm border-0">';
echo '  <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-wallet me-2 text-primary"></i>Wallet Management</h5>';
echo '    <div class="d-flex align-items-center gap-2">';
echo '      <span class="badge bg-primary">Total: '.count($coins).'</span>';
echo '      <button type="button" id="bulk-delete-btn" class="btn btn-danger btn-sm rounded-pill px-3 d-none" onclick="bulkDelete()"><i class="fa fa-trash me-1"></i>Delete Selected (<span id="selected-count">0</span>)</button>';
echo '    </div>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small" id="maintable">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">';
echo '          <tr>';
echo '            <th class="ps-3"><input type="checkbox" id="select-all" class="form-check-input" title="Select All"></th>';
echo '            <th class="ps-2">Coin</th>';
echo '            <th>Status</th>';
echo '            <th>Server/RPC</th>';
echo '            <th class="text-end">Diff / Height</th>';
echo '            <th class="text-end">Profit / Net</th>';
echo '            <th class="text-end">Balance / Available</th>';
echo '            <th class="text-end">Value (BTC/USD)</th>';
echo '            <th class="text-end pe-4">Actions</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

foreach($coins as $coin)
{
	$algo_color = getAlgoColors($coin->algo);
    
    $is_first_algo = !in_array($coin->algo, $algos_shown);
    $algos_shown[] = $coin->algo;
    
    $status_badge = '';
    $port_status = '';
    $ctl_btn = '';

    if ($is_first_algo) {
        // Multi-Server Logic: Check DB first, then local screen
        $t = time() - 120;
        $stratum = getdbosql('db_stratums', "algo=:algo AND time > $t ORDER BY started DESC", array(':algo'=>$coin->algo));
        $is_online = (bool) $stratum;

        if (!$is_online) {
            $check_cmd = "sudo -u yiimpadmin ".YIIMP_STRATUM_CTRL_DIR."/stratum_ctl.sh ".escapeshellarg($coin->algo)." status";
            $check_res = trim((string) shell_exec($check_cmd));
            $is_online = ($check_res == "ONLINE");
        }

        $db_algo = getdbosql('db_algos', "name=:algo", array(':algo'=>$coin->algo));
        $port = $db_algo ? $db_algo->port : '-';
        $port_status = '<span class="text-danger"><i class="fa fa-lock me-1"></i></span>';
        
        if ($port != '-') {
            if ($is_online) {
                $port_status = '<span class="text-success" title="Stratum Active!"><i class="fa fa-unlock me-1"></i></span>';
            } else {
                $check_cmd = "sudo -u yiimpadmin ".YIIMP_STRATUM_CTRL_DIR."/stratum_ctl.sh ".escapeshellarg($coin->algo)." port-check ".escapeshellarg($port);
                $check_res = trim((string) shell_exec($check_cmd));
                
                if ($check_res == "OPEN") {
                    $port_status = '<span class="text-success" title="Port listening!"><i class="fa fa-unlock me-1"></i></span>';
                } else {
                    // Only show the unlock link if the port is NOT listening (Red Lock)
                    $port_status = CHtml::link('<i class="fa fa-lock me-1"></i>', "/admin/unlockStratum?algo={$coin->algo}&port=$port", [
                        'class' => 'text-danger',
                        'title' => 'Port closed! Click to open via firewall...',
                        'onclick' => "return confirm('Are you sure you want to open port $port for {$coin->algo} in the firewall?')"
                    ]);
                }
            }
        }

        if ($is_online) {
            $status_badge = '<span class="badge bg-success" title="Stratum Online"><i class="fa fa-plug"></i></span>';
            $ctl_btn = '<a href="/admin/stopStratum?algo='.$coin->algo.'" class="btn btn-xs btn-outline-danger py-0 px-2" title="Stop Stratum"><i class="fa fa-stop"></i></a>';
        } else {
            $status_badge = '<span class="badge bg-danger" title="Stratum Offline"><i class="fa fa-plug"></i></span>';
            $ctl_btn = '<a href="/admin/startStratum?algo='.$coin->algo.'" class="btn btn-xs btn-outline-success py-0 px-2" title="Start Stratum"><i class="fa fa-play"></i></a>';
        }
    }
    
    // Status Logic
    $st_icon = $coin->enable ? 'check-circle text-success' : 'times-circle text-danger';
    $st_title = $coin->enable ? 'Enabled' : 'Disabled';
    
	echo '<tr>';

    // Checkbox column
    echo '<td class="ps-3"><input type="checkbox" name="coin_ids[]" value="'.$coin->id.'" class="form-check-input coin-checkbox"></td>';

    // Column 1: Coin Info
	echo '<td class="ps-2">';
    echo '  <div class="d-flex align-items-center">';
    echo '    <img src="'.$coin->image.'" width="32" class="me-3 shadow-sm rounded-circle p-1 bg-white">';
    echo '    <div>';
    echo '      <div class="fw-bold fs-6">'.CHtml::link($coin->name.' ('.$coin->symbol.')', '/admin/coin?id='.$coin->id, ['class'=>'text-decoration-none text-dark']).'</div>';
    echo '      <div class="text-muted" style="font-size: 0.65rem;"><span class="badge" style="background-color:'.$algo_color.'; color: #000;">'.$coin->algo.'</span> '.formatWalletVersion($coin).'</div>';
    echo '    </div>';
    echo '  </div>';
    echo '</td>';

    // Column 2: Status Badges
	echo '<td>';
    echo '  <div class="d-flex gap-1">';
    echo $status_badge;
    echo $port_status;
    if($coin->auto_ready) echo '<span class="badge bg-success" title="Auto Ready">A</span>';
    if($coin->visible) echo '<span class="badge bg-info text-dark" title="Visible">V</span>';
    else echo '<span class="badge bg-secondary text-white" title="Hidden">H</span>';
    if($coin->auxpow) echo '<span class="badge bg-purple text-white" style="background-color:#6f42c1" title="AuxPoW">X</span>';
    echo '  </div>';
    
	if($coin->block_height < $coin->target_height) {
		$percent = round($coin->block_height*100/$coin->target_height, 1);
		echo '<div class="progress mt-1" style="height: 4px; width: 40px;"><div class="progress-bar bg-warning" role="progressbar" style="width: '.$percent.'%"></div></div>';
	}
	echo "</td>";

    // Column 3: Server/RPC
	echo '<td>';
    echo '  <div class="fw-bold">'.CHtml::encode($coin->rpchost).'<span class="text-muted fw-normal">:'.CHtml::encode($coin->rpcport).'</span></div>';
    echo '  <div class="small text-muted"><i class="fa fa-plug me-1"></i>Conns: '.($coin->connections?:'0').' ('.CHtml::encode($coin->rpcencoding).')</div>';
    echo '</td>';

    // Column 4: Diff / Height
	$difficulty = Itoa2($coin->difficulty, 3);
	if ($coin->difficulty > 1e20) $difficulty = 'N/A';
    $err_class = !empty($coin->errors) ? 'text-danger' : '';
    
	echo '<td class="text-end '.$err_class.'">';
    echo '  <div class="fw-bold">'.$difficulty.'</div>';
    echo '  <div class="text-muted" style="font-size: 0.75rem;">'.$coin->block_height.'</div>';
    echo '</td>';

    // Column 5: Profitability
	$btcmhd = mbitcoinvaluetoa(yaamp_profitability($coin));
	$h = $coin->block_height-100;
	$ss1 = dboscalar("SELECT count(*) FROM blocks WHERE coin_id={$coin->id} AND height>=$h AND category!='orphan'");
    $ss2 = dboscalar("SELECT count(*) FROM blocks WHERE coin_id={$coin->id} AND height>=$h AND category='orphan'");

	echo '<td class="text-end">';
    echo '  <div class="fw-bold text-primary">'.$btcmhd.' <small class="text-muted">mBTC</small></div>';
    echo '  <div class="small">Pool: <span class="text-success">'.$ss1.'%</span> <span class="text-danger">'.$ss2.'%</span></div>';
    echo '</td>';

    // Column 6: Balance
	$available = $coin->available;
	$avail_class = $available < 0 ? 'text-danger fw-bold' : 'text-success';
	echo '<td class="text-end">';
    echo '  <div class="fw-bold">'.valuetocell($coin->balance).'</div>';
    echo '  <div class="'.$avail_class.'" style="font-size: 0.75rem;">'.valuetocell($available).' <small>avail</small></div>';
    echo '</td>';

    // Column 7: Value
	$btc = bitcoinvaluetoa($coin->balance * $coin->price);
	$fiat = '$'.number_format($coin->balance * $coin->price * $mining->usdbtc, 2);
	echo '<td class="text-end">';
    echo '  <div class="fw-bold">'.$btc.'</div>';
    echo '  <div class="text-info" style="font-size: 0.75rem;">'.$fiat.'</div>';
    echo '</td>';

    // Column 8: Actions
	echo '<td class="text-end pe-4">';
    echo '  <div class="btn-group">';
    echo '    '.$ctl_btn;
    echo '    <a href="/admin/coin?id='.$coin->id.'" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Details"><i class="fa fa-eye"></i></a>';
    echo '    <a href="/admin/coinupdate?id='.$coin->id.'" class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit"><i class="fa fa-edit"></i></a>';
    echo '    <a href="/admin/coinconsole?id='.$coin->id.'" class="btn btn-sm btn-outline-dark py-0 px-2" title="Console"><i class="fa fa-terminal"></i></a>';
    echo '    <a href="/admin/coindelete?id='.$coin->id.'" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Coin" onclick="return confirm(\'Delete '.$coin->symbol.' and all its data? This cannot be undone.\')"><i class="fa fa-trash"></i></a>';
    echo '  </div>';
	echo '</td>';

	echo "</tr>";
}

echo '        </tbody>';
echo '      </table>';
echo '    </div>';
echo '  </div>';
echo '</div>';
echo '</form>';

?>
<script>
document.getElementById('select-all').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.coin-checkbox');
    checkboxes.forEach(function(cb) { cb.checked = this.checked; }, this);
    updateBulkBtn();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('coin-checkbox')) updateBulkBtn();
});

function updateBulkBtn() {
    var checked = document.querySelectorAll('.coin-checkbox:checked').length;
    var btn = document.getElementById('bulk-delete-btn');
    document.getElementById('selected-count').textContent = checked;
    btn.classList.toggle('d-none', checked === 0);
}

function bulkDelete() {
    var count = document.querySelectorAll('.coin-checkbox:checked').length;
    if (confirm('Delete ' + count + ' coin(s) and all their data? This cannot be undone.')) {
        document.getElementById('bulk-delete-form').submit();
    }
}
</script>