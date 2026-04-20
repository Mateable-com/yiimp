<?php

if (!$coin) $this->goback();

$this->pageTitle = $coin->name." block explorer";

$remote = new WalletRPC($coin);

echo <<<END
<script type="text/javascript">
$(function() {
    $('#favicon').remove();
    $('head').append('<link href="{$coin->image}" id="favicon" rel="shortcut icon">');
});
</script>
END;

$tx = $remote->getrawtransaction($txhash, 1);
if(!$tx) return;

$valuetx = 0;
foreach($tx['vout'] as $vout)
    $valuetx += $vout['value'];

$actionUrl = $coin->visible ? '/explorer/'.$coin->symbol : '/explorer/search?id='.$coin->id;
$coinUrl = $this->createUrl('/explorer', array('id'=>$coin->id));

echo '<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><img width="20" src="'.htmlspecialchars($coin->image).'" class="rounded-circle me-2 shadow-sm">'.$coin->name.' <span class="text-muted small fw-normal">Transaction</span></h5>';
echo '    <form action="'.htmlspecialchars($actionUrl).'" method="POST" class="d-flex gap-2 align-items-center">';
echo '      <input type="text" name="height" class="form-control form-control-sm" placeholder="Height" style="width:90px;">';
echo '      <input type="text" name="txid" class="form-control form-control-sm font-monospace" placeholder="Transaction hash" style="width:300px;">';
echo '      <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Search</button>';
echo '    </form>';
echo '  </div>';
echo '  <div class="card-body p-4">';

echo '  <div class="mb-3">';
echo '    <div class="small text-muted text-uppercase fw-bold mb-1">Transaction Hash</div>';
echo '    <div class="font-monospace small text-break">'.htmlspecialchars($tx['txid']).'</div>';
echo '  </div>';
echo '  <div class="mb-4"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3">Total Value: '.$valuetx.' '.htmlspecialchars($coin->symbol).'</span></div>';

echo '  <div class="row g-4">';

// Inputs
echo '  <div class="col-md-6">';
echo '    <h6 class="fw-bold text-muted text-uppercase small mb-2"><i class="fa fa-arrow-right me-1 text-danger"></i>Inputs</h6>';
echo '    <div class="list-group list-group-flush rounded-3 border">';
foreach($tx['vin'] as $vin) {
    if(isset($vin['coinbase'])) {
        echo '<div class="list-group-item py-2 small"><span class="badge bg-success me-2">Coinbase</span>Generation reward</div>';
    } else {
        $addr = isset($vin['addr']) ? htmlspecialchars($vin['addr']) : 'Unknown';
        echo '<div class="list-group-item py-2 small font-monospace text-truncate">'.$addr.'</div>';
    }
}
echo '    </div></div>';

// Outputs
echo '  <div class="col-md-6">';
echo '    <h6 class="fw-bold text-muted text-uppercase small mb-2"><i class="fa fa-arrow-left me-1 text-success"></i>Outputs</h6>';
echo '    <div class="list-group list-group-flush rounded-3 border">';
foreach($tx['vout'] as $vout) {
    $value = $vout['value'];
    if(isset($vout['scriptPubKey']['addresses'][0])) {
        $addr = htmlspecialchars($vout['scriptPubKey']['addresses'][0]);
        echo '<div class="list-group-item py-2 small d-flex justify-content-between"><span class="font-monospace text-truncate me-2">'.$addr.'</span><span class="fw-bold text-nowrap">'.$value.'</span></div>';
    } else {
        echo '<div class="list-group-item py-2 small text-muted d-flex justify-content-between"><span>Script output</span><span class="fw-bold">'.$value.'</span></div>';
    }
}
echo '    </div></div>';

echo '  </div>'; // row
echo '  </div>'; // card-body
echo '</div>'; // card
