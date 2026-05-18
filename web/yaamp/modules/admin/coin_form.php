<?php

$this->pageTitle = ($coin->id ? 'Edit ' . $coin->name : 'Create New Coin') . ' - Admin';

echo '<div class="container-fluid py-4">';

// --- Breadcrumbs ---
echo '<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb bg-light p-3 rounded-3 shadow-sm border-0 small fw-bold">
    <li class="breadcrumb-item"><a href="/admin" class="text-decoration-none text-muted">Admin</a></li>
    <li class="breadcrumb-item"><a href="/admin/coinwallets" class="text-decoration-none text-muted">Wallets</a></li>
    <li class="breadcrumb-item active text-primary" aria-current="page">'.($coin->id ? 'Edit '.$coin->symbol : 'Create New Coin').'</li>
  </ol>
</nav>';

echo '<div class="card shadow-lg border-0 rounded-3 overflow-hidden mb-5">
        <div class="card-header bg-dark text-white p-4 d-flex align-items-center border-0">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4">
                <i class="fa fa-coins fa-2x text-primary"></i>
            </div>
            <div>
                <h3 class="mb-0 fw-bold">'.($coin->id ? 'Edit Coin: '.$coin->name : 'Register New Coin').'</h3>
                <p class="text-muted small mb-0 mt-1">Full configuration for wallet, network rules, and exchange integration.</p>
            </div>
            <div class="ms-auto">
                <a href="/admin/coinwallets" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa fa-arrow-left me-1"></i> Cancel</a>
            </div>
        </div>
        <div class="card-body p-0">';

echo CHtml::beginForm('', 'post', array('id'=>'coin-form'));
if($coin->hasErrors()) {
    echo '<div class="alert alert-danger border-0 shadow-sm mx-4 mt-4 mb-0"><i class="fa fa-exclamation-circle me-2"></i><b>Validation Errors:</b><br/>'.CHtml::errorSummary($coin).'</div>';
}

echo <<<EOT
<div class="p-4">
    <ul class="nav nav-pills mb-4 bg-light p-2 rounded-pill shadow-sm" id="coinTabs" role="tablist" style="width: fit-content;">
        <li class="nav-item"><button class="nav-link active rounded-pill fw-bold px-3" data-bs-toggle="pill" data-bs-target="#t-gen" type="button">1. General</button></li>
        <li class="nav-item mx-1"><button class="nav-link rounded-pill fw-bold px-3" data-bs-toggle="pill" data-bs-target="#t-net" type="button">2. Network</button></li>
        <li class="nav-item mx-1"><button class="nav-link rounded-pill fw-bold px-3" data-bs-toggle="pill" data-bs-target="#t-rpc" type="button">3. RPC/Daemon</button></li>
        <li class="nav-item mx-1"><button class="nav-link rounded-pill fw-bold px-3" data-bs-toggle="pill" data-bs-target="#t-exch" type="button">4. Exchange</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill fw-bold px-3" data-bs-toggle="pill" data-bs-target="#t-links" type="button">5. Social Links</button></li>
    </ul>

    <div class="tab-content" id="coinTabsContent">

<script>
const algoPorts = {
    'sha256': { rpc: 8332, strat: 3333 },
    'scrypt': { rpc: 9332, strat: 3433 },
    'x11': { rpc: 9998, strat: 3533 },
    'neoscrypt': { rpc: 4733, strat: 4233 },
    'lyra2v2': { rpc: 14233, strat: 4533 },
    'yescrypt': { rpc: 11333, strat: 6233 },
    'equihash': { rpc: 8232, strat: 2142 }
};

$(function() {
    $('#db_coins_algo').change(function() {
        const algo = $(this).val();
        if (algoPorts[algo]) {
            if ($('#db_coins_rpcport').val() == '') $('#db_coins_rpcport').val(algoPorts[algo].rpc);
            if ($('#db_coins_dedicatedport').val() == '') $('#db_coins_dedicatedport').val(algoPorts[algo].strat);
        }
    });

    // Auto-suggest image when symbol is typed
    $('#db_coins_symbol').on('blur', function() {
        var sym = $(this).val().toUpperCase();
        if (!sym || $('#db_coins_image').val()) return;
        var url = '/images/coin-' + sym + '.png';
        var img = new Image();
        img.onload = function() {
            $('#db_coins_image').val(url);
            $('#coin-img-preview').attr('src', url);
            $('#coin-img-picker').val(url);
        };
        img.src = url;
    });
});
</script>
EOT;

// --- TAB 1: GENERAL ---
echo '<div class="tab-pane fade show active" id="t-gen" role="tabpanel"><div class="row g-4">';
echo '  <div class="col-md-6">';
echo '    <div class="mb-3"><label class="form-label fw-bold small text-uppercase">Full Name</label>'.CHtml::activeTextField($coin, 'name', array('class'=>'form-control border-2')).'<div class="form-text small">Display name of the coin.</div></div>';
echo '    <div class="row"><div class="col-6"><label class="form-label fw-bold small text-uppercase">Symbol</label>'.CHtml::activeTextField($coin, 'symbol', array('class'=>'form-control border-2')).'</div>';
echo '    <div class="col-6"><label class="form-label fw-bold small text-uppercase">Symbol Alias</label>'.CHtml::activeTextField($coin, 'symbol2', array('class'=>'form-control border-2')).'<div class="form-text small">Internal alias if different.</div></div></div>';
echo '    <div class="mt-3"><label class="form-label fw-bold small text-uppercase">Algorithm</label>';
$ListAlgos = []; $db_algos = getdbolist('db_algos'); foreach ($db_algos as $a) $ListAlgos[$a->name] = $a->name;
echo CHtml::activeDropDownList($coin, 'algo', $ListAlgos, array('class'=>'form-select border-2')).'<div class="form-text small">Must be lower-case.</div></div>';
echo '  </div>';
echo '  <div class="col-md-6">';
// Image picker
$imgDir = YAAMP_HTDOCS.'/images/';
$imgFiles = glob($imgDir.'coin-*.png');
$imgOptions = array('' => '-- Custom URL --');
foreach($imgFiles as $f) {
    $base = basename($f);
    $imgOptions['/images/'.$base] = preg_replace('/^coin-(.+)\.png$/', '$1', $base);
}
asort($imgOptions);
$currentImg = $coin->image;
echo '    <div class="mb-3">';
echo '      <label class="form-label fw-bold small text-uppercase">Coin Icon</label>';
echo '      <div class="d-flex align-items-center gap-2 mb-2">';
echo '        <img id="coin-img-preview" src="'.htmlspecialchars($currentImg).'" width="32" height="32" class="rounded-circle border shadow-sm" onerror="this.src=\'/images/btc.png\'">';
echo '        <span class="text-muted small">Preview</span>';
echo '      </div>';
echo '      <select id="coin-img-picker" class="form-select border-2 mb-2">';
foreach($imgOptions as $val => $label) {
    $sel = ($val === $currentImg) ? ' selected' : '';
    echo '<option value="'.htmlspecialchars($val).'"'.$sel.'>'.htmlspecialchars($label).'</option>';
}
echo '      </select>';
echo '      '.CHtml::activeTextField($coin, 'image', array('class'=>'form-control border-2', 'placeholder'=>'/images/coin-BTC.png', 'id'=>'db_coins_image'));
echo '      <div class="form-text small">Select from list or type a custom URL.</div>';
echo '    </div>';
echo '    <script>
document.getElementById("coin-img-picker").addEventListener("change", function() {
    var val = this.value;
    if(val) {
        document.getElementById("db_coins_image").value = val;
        document.getElementById("coin-img-preview").src = val;
    }
});
document.getElementById("db_coins_image").addEventListener("input", function() {
    document.getElementById("coin-img-preview").src = this.value;
    document.getElementById("coin-img-picker").value = this.value || "";
});
</script>';
echo '    <div class="row"><div class="col-6"><label class="form-label fw-bold small text-uppercase">Payout Min</label>'.CHtml::activeTextField($coin, 'payout_min', array('class'=>'form-control border-2')).'</div>';
echo '    <div class="col-6"><label class="form-label fw-bold small text-uppercase">Payout Max</label>'.CHtml::activeTextField($coin, 'payout_max', array('class'=>'form-control border-2')).'</div></div>';
echo '    <div class="mt-3"><label class="form-label fw-bold small text-uppercase">Maturity Blocks</label>'.CHtml::activeTextField($coin, 'mature_blocks', array('class'=>'form-control border-2')).'<div class="form-text small">Required confirmations for rewards.</div></div>';
echo '  </div></div></div>';

// --- TAB 2: NETWORK ---
echo '<div class="tab-pane fade" id="t-net" role="tabpanel"><div class="row g-4">';
echo '  <div class="col-md-4"><label class="form-label fw-bold small text-uppercase">Current Block Height</label>'.CHtml::activeTextField($coin, 'block_height', array('class'=>'form-control border-2 bg-light', 'readonly'=>'readonly')).'</div>';
echo '  <div class="col-md-4"><label class="form-label fw-bold small text-uppercase">Target Height (Sync)</label>'.CHtml::activeTextField($coin, 'target_height', array('class'=>'form-control border-2')).'<div class="form-text small">Known network height.</div></div>';
echo '  <div class="col-md-4"><label class="form-label fw-bold small text-uppercase">PoW End Height</label>'.CHtml::activeTextField($coin, 'powend_height', array('class'=>'form-control border-2')).'<div class="form-text small">Height when PoW mining stops.</div></div>';
echo '  <div class="col-md-4"><label class="form-label fw-bold small text-uppercase">Avg Block Time (sec)</label>'.CHtml::activeTextField($coin, 'block_time', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-4"><label class="form-label fw-bold small text-uppercase">PoW Limit Bits</label>'.CHtml::activeTextField($coin, 'powlimit_bits', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-4"><label class="form-label fw-bold small text-uppercase">Max Miners</label>'.CHtml::activeTextField($coin, 'max_miners', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-12 d-flex align-items-center gap-4 bg-light p-3 rounded-3">';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Mining Enabled</label>'.CHtml::activeCheckBox($coin, 'enable', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Visible to Public</label>'.CHtml::activeCheckBox($coin, 'visible', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold text-purple">AuxPoW Support</label>'.CHtml::activeCheckBox($coin, 'auxpow', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold text-info">Multi-Algo Coin</label>'.CHtml::activeCheckBox($coin, 'multialgos', array('class'=>'form-check-input')).'<div class="form-text small">Show algo column in explorer.</div></div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Disable Explorer</label>'.CHtml::activeCheckBox($coin, 'no_explorer', array('class'=>'form-check-input')).'</div>';
echo '  </div>';
echo '  <div class="col-12"><label class="form-label fw-bold small text-uppercase">Specifications / Technical Notes</label>'.CHtml::activeTextArea($coin, 'specifications', array('class'=>'form-control border-2', 'rows'=>3)).'</div>';
echo '</div></div>';

// --- TAB 3: RPC / DAEMON ---
echo '<div class="tab-pane fade" id="t-rpc" role="tabpanel"><div class="row g-4">';
echo '  <div class="col-md-4"><label class="form-label fw-bold small text-uppercase">RPC Host (IP)</label>'.CHtml::activeTextField($coin, 'rpchost', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-2"><label class="form-label fw-bold small text-uppercase">RPC Port</label>'.CHtml::activeTextField($coin, 'rpcport', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-3"><label class="form-label fw-bold small text-uppercase">RPC User</label>'.CHtml::activeTextField($coin, 'rpcuser', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-3"><label class="form-label fw-bold small text-uppercase">RPC Password</label>'.CHtml::activeTextField($coin, 'rpcpasswd', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-4"><label class="form-label fw-bold small text-uppercase">RPC Encoding</label>'.CHtml::activeDropDownList($coin, 'rpcencoding', array('POW'=>'POW','POS'=>'POS','AUX'=>'AUX','DCR'=>'DCR','ZEC'=>'ZEC','GETH'=>'GETH'), array('class'=>'form-select border-2')).'</div>';
echo '  <div class="col-md-2"><label class="form-label fw-bold small text-uppercase">Dedicated Port</label>'.CHtml::activeTextField($coin, 'dedicatedport', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch">'.CHtml::activeCheckBox($coin, 'rpccurl', array('class'=>'form-check-input')).'<label class="form-check-label fw-bold small">Use Curl for RPC</label></div></div>';
echo '  <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch">'.CHtml::activeCheckBox($coin, 'rpcssl', array('class'=>'form-check-input')).'<label class="form-check-label fw-bold small">Use SSL for RPC</label></div></div>';
echo '  <div class="col-md-6"><label class="form-label fw-bold small text-uppercase">Master Wallet Address (Pool)</label>'.CHtml::activeTextField($coin, 'master_wallet', array('class'=>'form-control border-2 fw-bold font-monospace')).'</div>';
echo '  <div class="col-md-6"><label class="form-label fw-bold small text-uppercase">Z-Address (Privacy/ZCash)</label>'.CHtml::activeTextField($coin, 'wallet_zaddress', array('class'=>'form-control border-2 font-monospace')).'</div>';
echo '  <div class="col-md-12 d-flex gap-4 bg-light p-3 rounded-3">';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold text-success">Auto-Start Stratum</label>'.CHtml::activeCheckBox($coin, 'auto_ready', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Has GetInfo RPC</label>'.CHtml::activeCheckBox($coin, 'hasgetinfo', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Has SubmitBlock RPC</label>'.CHtml::activeCheckBox($coin, 'hassubmitblock', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Has Masternodes</label>'.CHtml::activeCheckBox($coin, 'hasmasternodes', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Use SegWit</label>'.CHtml::activeCheckBox($coin, 'usesegwit', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">MWEB (LTC MimbleWimble)</label>'.CHtml::activeCheckBox($coin, 'usemweb', array('class'=>'form-check-input')).'</div>';
echo '  </div>';
echo '</div></div>';

// --- TAB 4: EXCHANGE ---
echo '<div class="tab-pane fade" id="t-exch" role="tabpanel"><div class="row g-4">';
echo '  <div class="col-md-6"><label class="form-label fw-bold small text-uppercase">Selected Market</label>'.CHtml::activeTextField($coin, 'market', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-6"><label class="form-label fw-bold small text-uppercase">Manual Price (BTC)</label>'.CHtml::activeTextField($coin, 'price', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-6"><label class="form-label fw-bold small text-uppercase">Deposit Address (Withdraws)</label>'.CHtml::activeTextField($coin, 'deposit_address', array('class'=>'form-control border-2 font-monospace')).'</div>';
echo '  <div class="col-md-3"><label class="form-label fw-bold small text-uppercase">Sell Threshold</label>'.CHtml::activeTextField($coin, 'sellthreshold', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-md-3"><label class="form-label fw-bold small text-uppercase">Reward Multiplier</label>'.CHtml::activeTextField($coin, 'reward_mul', array('class'=>'form-control border-2')).'</div>';
echo '  <div class="col-12 d-flex gap-4 bg-light p-3 rounded-3">';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Auto-Exchange Enabled</label>'.CHtml::activeCheckBox($coin, 'auto_exchange', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold text-danger">Disable Selling (Hold)</label>'.CHtml::activeCheckBox($coin, 'dontsell', array('class'=>'form-check-input')).'</div>';
echo '    <div class="form-check form-switch"><label class="form-check-label fw-bold">Always Sell on Bid</label>'.CHtml::activeCheckBox($coin, 'sellonbid', array('class'=>'form-check-input')).'</div>';
echo '  </div>';
echo '</div></div>';

// --- TAB 5: SOCIAL LINKS ---
echo '<div class="tab-pane fade" id="t-links" role="tabpanel"><div class="row g-3">';
$links = ['bitcointalk'=>'BitcoinTalk','github'=>'GitHub','site'=>'Website','discord'=>'Discord','twitter'=>'Twitter','facebook'=>'Facebook','exchange'=>'Market Link','explorer'=>'Explorer'];
foreach($links as $k=>$v) {
    echo '<div class="col-md-6"><label class="form-label fw-bold small text-uppercase">'.$v.'</label>'.CHtml::activeTextField($coin, 'link_'.$k, array('class'=>'form-control border-2')).'</div>';
}
echo '</div></div>';

echo '</div></div>'; // close tab-content and p-4

echo '<div class="card-footer bg-light p-4 text-end border-0">
        <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm rounded-pill"><i class="fa fa-save me-2"></i> Save Coin Data</button>
      </div>';

echo CHtml::endForm();

// --- RESTORED CONFIG SECTION (BOTTOM) - ALWAYS SHOW ---
$port = getAlgoPort($coin->algo);
$dedport = $coin->dedicatedport;
$active_port = $dedport ? $dedport : ($port ? $port : 'PORT');
$coin_id = $coin->id ? $coin->id : '[ID]';

echo '<div class="p-4 border-top bg-white">';
echo '  <div class="row">';

// Sample .conf
echo '    <div class="col-md-6 mb-4">';
echo '      <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="fa fa-file-invoice me-2"></i>Sample Wallet Config (.conf)</h6>';
echo '      <div class="position-relative">';
echo '        <pre class="bg-dark text-success p-3 rounded-3 small shadow-sm mb-0" style="min-height: 250px;">';
echo "rpcuser=".($coin->rpcuser ? : 'yiimprpc')."\n";
echo "rpcpassword=".($coin->rpcpasswd ? : 'random_password')."\n";
echo "rpcport=".($coin->rpcport ? : 'rpc_port')."\n";
echo "rpcthreads=8\n";
echo "rpcallowip=127.0.0.1\n";
echo "# onlynet=ipv4\n";
echo "maxconnections=12\n";
echo "daemon=1\n";
echo "gen=0\n\n";
echo "alertnotify=echo %s | mail -s \"{$coin->name} alert!\" ".YAAMP_ADMIN_EMAIL."\n";
echo "blocknotify=/var/stratum/blocknotify ".YAAMP_STRATUM_URL.":{$active_port} {$coin_id} %s\n";
echo '        </pre>';
echo '      </div>';
echo '    </div>';

// Miner command line
echo '    <div class="col-md-6">';
echo '      <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="fa fa-terminal me-2"></i>Miner Command Line</h6>';
echo '      <pre class="bg-dark text-info p-3 rounded-3 small shadow-sm mb-0" style="white-space: pre-wrap; word-break: break-all;">';
echo "-a ".($coin->algo ? : 'algo')." -o stratum+tcp://".YAAMP_STRATUM_URL.":{$active_port} -u ".($coin->master_wallet ? : 'YOUR_WALLET_ADDRESS')." -p c=".($coin->symbol ? : 'SYMBOL');
echo '      </pre>';
echo '      <div class="alert alert-info mt-3 border-0 small"><i class="fa fa-info-circle me-2"></i>Use these settings to connect your wallet and miners to the pool. '.(!$coin->id ? '<b>Note:</b> ID will be generated after saving.' : '').'</div>';
echo '    </div>';

echo '  </div>'; // close row
echo '</div>'; // close p-4

echo '</div></div></div>';

echo '<style>
    .nav-pills .nav-link.active { background-color: #0d6efd; color: #fff; }
    .nav-pills .nav-link { color: #6c757d; }
    .form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); }
    .text-purple { color: #6f42c1 !important; }
    .breadcrumb-item + .breadcrumb-item::before { content: "›"; font-size: 1.2rem; vertical-align: middle; }
    pre { border: 1px solid #333; font-family: "SFMono-Regular", Consolas, monospace !important; }
</style>';
?>