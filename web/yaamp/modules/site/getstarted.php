<?php

$stratum_url = YAAMP_STRATUM_URL;
$site_name   = settings_get('site_name', YAAMP_SITE_NAME);

// Build algo list with active coins, ports, and aux coins
$algos = yaamp_get_algos();
$algo_data = array();
foreach ($algos as $algo) {
    $port = getAlgoPort($algo);
    if (!$port) continue;
    $coins = getdbolist('db_coins', "enable AND auto_ready AND algo=:algo AND NOT auxpow ORDER BY index_avg DESC", array(':algo' => $algo));
    if (empty($coins)) continue;
    $coin_names = array();
    $aux_coins = array();
    foreach ($coins as $c) {
        $coin_names[] = $c->symbol;
        // find aux coins for this primary coin's algo
        $auxlist = getdbolist('db_coins', "enable AND auto_ready AND auxpow AND algo=:algo", array(':algo' => $algo));
        foreach ($auxlist as $aux) {
            if (!in_array($aux->symbol, $aux_coins)) $aux_coins[] = $aux->symbol;
        }
    }
    $algo_data[] = array('algo' => $algo, 'port' => $port, 'coins' => $coin_names, 'aux' => $aux_coins);
}

// Build merged mining map: primary coin -> aux coins
$merge_map = array();
foreach ($algo_data as $row) {
    if (empty($row['aux'])) continue;
    foreach ($row['coins'] as $primary) {
        $merge_map[$primary] = array('aux' => $row['aux'], 'algo' => $row['algo'], 'port' => $row['port']);
    }
}

// Recommended miners per algo family
$miner_map = array(
    'scrypt'    => array(array('name'=>'CGMiner / BFGMiner', 'type'=>'ASIC'), array('name'=>'ccminer', 'type'=>'GPU')),
    'sha256'    => array(array('name'=>'CGMiner / BFGMiner', 'type'=>'ASIC')),
    'x11'       => array(array('name'=>'ccminer', 'type'=>'GPU'), array('name'=>'SGMiner', 'type'=>'GPU')),
    'ethash'    => array(array('name'=>'T-Rex / lolMiner', 'type'=>'GPU')),
    'kawpow'    => array(array('name'=>'T-Rex / kawpowminer', 'type'=>'GPU')),
    'equihash'  => array(array('name'=>'lolMiner / miniZ', 'type'=>'GPU')),
    'blake2s'   => array(array('name'=>'ccminer', 'type'=>'GPU')),
    'default'   => array(array('name'=>'ccminer', 'type'=>'GPU')),
);

$this->pageTitle = 'Get Started - '.$site_name;
?>

<div class="container py-5">

<!-- Hero -->
<div class="card border-0 bg-dark text-white rounded-4 shadow mb-5 overflow-hidden">
  <div class="card-body p-5">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h1 class="fw-bold mb-2"><i class="fa fa-plug me-3 text-primary"></i>Get Started Mining</h1>
        <p class="lead text-white-50 mb-0">Connect your miner to <strong class="text-white"><?=htmlspecialchars($site_name)?></strong> in minutes. No registration required — just point your miner at the pool and start earning.</p>
      </div>
      <div class="col-lg-4 text-end d-none d-lg-block">
        <i class="fa fa-microchip fa-5x text-primary opacity-25"></i>
      </div>
    </div>
  </div>
</div>

<!-- Steps -->
<div class="row g-4 mb-5">
  <?php
  $steps = array(
    array('num'=>'1', 'icon'=>'fa-wallet', 'color'=>'primary', 'title'=>'Get a Wallet Address',
      'body'=>'You need a cryptocurrency wallet address to receive payouts. Use any wallet that supports the coin you want to mine. <strong>Your wallet address IS your username</strong> — no account registration needed.'),
    array('num'=>'2', 'icon'=>'fa-microchip', 'color'=>'success', 'title'=>'Choose Your Miner Software',
      'body'=>'Download mining software compatible with your hardware (GPU or ASIC) and the algorithm you want to mine. See the algorithm table below for recommendations.'),
    array('num'=>'3', 'icon'=>'fa-plug', 'color'=>'info', 'title'=>'Configure &amp; Connect',
      'body'=>'Point your miner at the pool stratum address with your wallet address as the username. Use any string as the password, or use <code>c=SYMBOL</code> to specify your payout coin.'),
    array('num'=>'4', 'icon'=>'fa-chart-line', 'color'=>'warning', 'title'=>'Monitor Your Earnings',
      'body'=>'Enter your wallet address in the search box above to see your hashrate, workers, earnings, and payout history in real time.'),
  );
  foreach ($steps as $s): ?>
  <div class="col-md-6 col-lg-3">
    <div class="card border-0 shadow-sm h-100 rounded-4">
      <div class="card-body p-4">
        <div class="d-flex align-items-center mb-3">
          <div class="rounded-circle bg-<?=$s['color']?> bg-opacity-10 text-<?=$s['color']?> fw-bold d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;font-size:1.2rem;"><?=$s['num']?></div>
          <i class="fa <?=$s['icon']?> fa-lg text-<?=$s['color']?>"></i>
        </div>
        <h5 class="fw-bold"><?=$s['title']?></h5>
        <p class="text-muted small mb-0"><?=$s['body']?></p>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Stratum Connection Format -->
<div class="card border-0 shadow-sm rounded-4 mb-5">
  <div class="card-header bg-dark text-white py-3 rounded-top-4">
    <h5 class="mb-0 fw-bold"><i class="fa fa-terminal me-2 text-primary"></i>Stratum Connection Format</h5>
  </div>
  <div class="card-body p-4">
    <div class="row g-4">
      <div class="col-md-6">
        <h6 class="fw-bold text-uppercase text-muted small mb-3">URL</h6>
        <div class="bg-dark text-success rounded-3 p-3 font-monospace small">
          stratum+tcp://<?=htmlspecialchars($stratum_url)?>:<strong class="text-warning">[PORT]</strong>
        </div>
        <div class="mt-2 text-muted small">Replace <code>[PORT]</code> with the port for your chosen algorithm from the table below.</div>
      </div>
      <div class="col-md-6">
        <h6 class="fw-bold text-uppercase text-muted small mb-3">Username &amp; Password</h6>
        <div class="bg-dark text-success rounded-3 p-3 font-monospace small">
          <div><span class="text-warning">user:</span> YOUR_WALLET_ADDRESS</div>
          <div><span class="text-warning">pass:</span> c=SYMBOL (or just <code>x</code>)</div>
        </div>
        <div class="mt-2 text-muted small">Example password: <code>c=DGB</code> to receive payouts in DigiByte.</div>
      </div>
    </div>

    <!-- cgminer / ccminer examples -->
    <div class="mt-4">
      <h6 class="fw-bold text-uppercase text-muted small mb-3">Example Commands</h6>
      <div class="row g-3">
        <div class="col-12">
          <div class="bg-dark rounded-3 p-3 mb-3">
            <div class="text-info small fw-bold mb-1">Simple — single coin payout</div>
            <code class="text-success small" style="word-break:break-all;">cgminer -o stratum+tcp://<?=htmlspecialchars($stratum_url)?>:3433 -u YOUR_DGB_ADDRESS -p c=DGB</code>
          </div>
          <div class="bg-dark rounded-3 p-3">
            <div class="text-info small fw-bold mb-1">With merged mining (earn DGB + DOGE + PEP simultaneously)</div>
            <code class="text-success small" style="word-break:break-all;">cgminer -o stratum+tcp://<?=htmlspecialchars($stratum_url)?>:3433 -u YOUR_DGB_ADDRESS -p c=DGB,mc=DGB,m=DOGE:YOUR_DOGE_ADDRESS,m=PEP:YOUR_PEP_ADDRESS</code>
          </div>
          <div class="mt-2 text-muted small">
            <strong>Password format:</strong> <code>c=PAYOUT_COIN,mc=MAIN_COIN,m=AUX_COIN:AUX_WALLET,m=AUX_COIN2:AUX_WALLET2</code><br>
            Replace each address with your own wallet for that coin. Omit <code>m=</code> entries for coins you don't want.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Algo Table -->
<div class="card border-0 shadow-sm rounded-4 mb-5">
  <div class="card-header bg-dark text-white py-3 rounded-top-4 d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold"><i class="fa fa-table me-2 text-info"></i>Available Algorithms &amp; Ports</h5>
    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><?=count($algo_data)?> active algos</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-muted text-uppercase" style="font-size:0.65rem;letter-spacing:1px;">
          <tr>
            <th class="ps-4">Algorithm</th>
            <th class="text-center">Port</th>
            <th>Active Coins</th>
            <th class="pe-4">Recommended Miner</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($algo_data as $row):
            $miners = isset($miner_map[$row['algo']]) ? $miner_map[$row['algo']] : $miner_map['default'];
            $miner_str = implode(', ', array_map(function($m){ return $m['name'].' <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size:0.6rem;">'.$m['type'].'</span>'; }, $miners));
        ?>
          <tr>
            <td class="ps-4 fw-bold text-primary"><?=htmlspecialchars(strtoupper($row['algo']))?></td>
            <td class="text-center">
              <span class="badge bg-dark font-monospace fs-6"><?=htmlspecialchars($row['port'])?></span>
            </td>
            <td>
              <?php foreach ($row['coins'] as $sym): ?>
              <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-25 me-1"><?=htmlspecialchars($sym)?></span>
              <?php endforeach; ?>
            </td>
            <td class="pe-4 small"><?=$miner_str?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (!empty($merge_map)): ?>
<!-- Merged Mining -->
<div class="card border-0 shadow-sm rounded-4 mb-5">
  <div class="card-header bg-dark text-white py-3 rounded-top-4 d-flex justify-content-between align-items-center">
    <h5 class="mb-0 fw-bold"><i class="fa fa-layer-group me-2 text-info"></i>Merged Mining — Earn Multiple Coins at Once</h5>
    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Free bonus earnings</span>
  </div>
  <div class="card-body p-4">
    <p class="text-muted mb-4">When you mine the coins below, you <strong>automatically</strong> earn the merged coins at the same time — no extra hashrate, no extra configuration needed. Your miner does one job and gets paid in multiple coins.</p>
    <div class="row g-4">
      <?php foreach ($merge_map as $primary => $data): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 bg-light rounded-3 h-100">
          <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
              <?php $pc = getdbosql('db_coins', "symbol=:s AND enable", array(':s'=>$primary)); ?>
              <?php if ($pc): ?>
              <img src="<?=htmlspecialchars($pc->image)?>" width="32" class="rounded-circle shadow-sm me-2">
              <?php endif; ?>
              <div>
                <div class="fw-bold fs-6"><?=htmlspecialchars($primary)?></div>
                <div class="text-muted small"><?=htmlspecialchars(strtoupper($data['algo']))?> &bull; port <?=htmlspecialchars($data['port'])?></div>
              </div>
            </div>
            <div class="small text-muted fw-bold text-uppercase mb-2">Also earns:</div>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($data['aux'] as $aux_sym):
                $ac = getdbosql('db_coins', "symbol=:s AND enable", array(':s'=>$aux_sym)); ?>
              <div class="d-flex align-items-center bg-white rounded-pill px-3 py-1 shadow-sm border">
                <?php if ($ac): ?><img src="<?=htmlspecialchars($ac->image)?>" width="16" class="rounded-circle me-1"><?php endif; ?>
                <span class="fw-bold small"><?=htmlspecialchars($aux_sym)?></span>
                <span class="badge bg-info text-dark ms-1" style="font-size:0.55rem;">AUX</span>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="mt-3 bg-dark rounded-2 p-2">
              <code class="text-success" style="font-size:0.7rem;">-p c=<?=htmlspecialchars($primary)?>,mc=<?=htmlspecialchars($primary)?><?php foreach($data['aux'] as $a) echo ',m='.htmlspecialchars($a).':YOUR_'.htmlspecialchars($a).'_ADDR'; ?></code>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Tips -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-header bg-dark text-white py-3 rounded-top-4">
    <h5 class="mb-0 fw-bold"><i class="fa fa-lightbulb me-2 text-warning"></i>Tips &amp; Notes</h5>
  </div>
  <div class="card-body p-4">
    <div class="row g-4">
      <div class="col-md-6">
        <h6 class="fw-bold"><i class="fa fa-coins me-2 text-warning"></i>Payout Coin</h6>
        <p class="text-muted small">Use <code>c=SYMBOL</code> in your password to set which coin you receive payouts in. Example: <code>c=DGB</code> for DigiByte. Without it, the pool auto-detects from your wallet address.</p>
      </div>
      <div class="col-md-6">
        <h6 class="fw-bold"><i class="fa fa-layer-group me-2 text-info"></i>Merged Mining</h6>
        <p class="text-muted small">Some coins support merged mining — you earn multiple coins simultaneously at no extra cost. DOGE and PEP are automatically merged mined alongside DGB on scrypt.</p>
      </div>
      <div class="col-md-6">
        <h6 class="fw-bold"><i class="fa fa-tachometer-alt me-2 text-success"></i>Difficulty</h6>
        <p class="text-muted small">The pool uses variable difficulty — it automatically adjusts to your miner's hashrate. You don't need to set it manually.</p>
      </div>
      <div class="col-md-6">
        <h6 class="fw-bold"><i class="fa fa-money-bill-wave me-2 text-primary"></i>Minimum Payout</h6>
        <p class="text-muted small">Each coin has a minimum payout threshold. You can customize yours from your wallet dashboard. Payouts run automatically when your balance exceeds the threshold.</p>
      </div>
    </div>
  </div>
</div>

</div>
