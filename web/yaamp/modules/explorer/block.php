<?php

if (!$coin) return;

$this->pageTitle = $coin->name." block explorer";

$txid = getparam('txid');
$q = getparam('q');
if (!empty($q) && ctype_xdigit($q)) $txid = $q;
elseif (empty($txid)) $txid = 'txid not set';

$txid_js = json_encode($txid);

echo <<<END
<script type="text/javascript">
function toggleRaw(el) {
    $(el).parents('tr').next('tr.raw').toggle();
}
$(function() {
    $('#favicon').remove();
    $('head').append('<link href="{$coin->image}" id="favicon" rel="shortcut icon">');
    $('span.txid').bind('click', function(el) { toggleRaw(el.target); });
    $('span.txid:contains({$txid_js})').css('color','#dc3545');
});
</script>
<style>
.json-block { font-size: 11px; white-space: pre; font-family: monospace; padding: 12px; overflow-x: auto; background: #1e293b; color: #94a3b8; border-radius: 8px; }
.json-block s { text-decoration: none; color: #60a5fa; }
.json-block s.key { color: #e2e8f0; }
.json-block s.addr { color: #c084fc; }
.json-block u { text-decoration: none; color: #34d399; }
.json-block i { font-style: normal; color: #fbbf24; }
.json-block b { font-style: normal; color: #f87171; }
span.txid { cursor: pointer; }
span.txid:hover { text-decoration: underline; }
</style>
END;

//////////////////////////////////////////////////////////////////////////////////////

function colorizeJson($json)
{
    $json = str_replace('"', '&quot;', $json);
    $res = preg_match_all("# &quot;([^&]+)&quot;([,\s])#", $json, $matches);
    if ($res) foreach($matches[1] as $n=>$m) {
        $sfx = $matches[2][$n];
        $class = '';
        if (strlen($m) == 64 && ctype_xdigit($m)) $class = 'hash';
        if (strlen($m) == 34 && ctype_alnum($m)) $class = 'addr';
        if (strlen($m) == 35 && ctype_alnum($m)) $class = 'addr';
        if ($class == '' && strlen($m) < 64 && ctype_xdigit($m)) $class = 'hexa';
        $json = str_replace(' &quot;'.$m."&quot;".$sfx, ' "<s class="'.$class.'">'.$m.'</s>"'.$sfx, $json);
    }
    $res = preg_match_all("#&quot;([^&]+)&quot;:#", $json, $matches);
    if ($res) foreach($matches[1] as $n=>$m) {
        $json = str_replace('&quot;'.$m."&quot;", '"<s class="key">'.$m.'</s>"', $json);
    }
    $res = preg_match_all("#: ([0-9]{10})([,\s])#", $json, $matches);
    if ($res) foreach($matches[1] as $n=>$m) {
        $ts = intval($m);
        if ($ts > 1400000000 && $ts < 2000000000) {
            $sfx = $matches[2][$n];
            $date = date("<u>Y-m-d H:i:s</u>", $ts);
            $json = str_replace(' '.$m.$sfx, ' "'.$date.'"'.$sfx, $json);
        }
    }
    $res = preg_match_all("#: ([e\-\.0-9]+)([,\s])#", $json, $matches);
    if ($res) foreach($matches[1] as $n=>$m) {
        $sfx = $matches[2][$n];
        $json = str_replace(' '.$m.$sfx, ' <i>'.$m.'</i>'.$sfx, $json);
    }
    $json = preg_replace('#\[\s+\]#', '[]', $json);
    $json = str_replace('[', '<b>[</b>', $json);
    $json = str_replace(']', '<b>]</b>', $json);
    $json = str_replace('{', '<b>{</b>', $json);
    $json = str_replace('}', '<b>}</b>', $json);
    return $json;
}

function simplifyscript($script)
{
    $script = preg_replace("/[0-9a-f]+ OP_DROP ?/","", $script);
    $script = preg_replace("/OP_NOP ?/","", $script);
    return trim($script);
}

///////////////////////////////////////////////////////////////////////////////////////////////

$remote = new WalletRPC($coin);
$block = $remote->getblock($hash);
if(!$block) return;

$d = date('Y-m-d H:i:s', $block['time']);
$confirms = isset($block['confirmations']) ? $block['confirmations'] : '';
$txcount = count($block['tx']);
$version = dechex($block['version']);
$nonce = $block['nonce'];

// Determine block type
$flags = strtolower(arraySafeVal($block, 'flags', ''));
$block_type = '';
if (isset($block['auxpow'])) $block_type = 'Aux';
else if (strpos($flags, 'proof-of-stake') !== false || strpos($flags, 'stake') !== false || isset($block['mint'])) $block_type = 'PoS';
else if ($nonce === 0 || $nonce === '0' || $nonce === '00000000') $block_type = 'PoS';
else if ($nonce > 0) $block_type = 'PoW';
if (!$block_type && $coin->symbol == 'ZEC') $block_type = 'PoW';

$typeBadge = '';
if ($block_type == 'PoW') $typeBadge = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-2">PoW</span>';
else if ($block_type == 'PoS') $typeBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-2">PoS</span>';
else if ($block_type == 'Aux') $typeBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 ms-2">Aux</span>';

$actionUrl = $coin->visible ? '/explorer/'.htmlspecialchars($coin->symbol) : '/explorer/search?id='.intval($coin->id);

// Pager links
$prevLink = isset($block['previousblockhash']) ? $coin->createExplorerLink('<i class="fa fa-chevron-left me-1"></i>Prev', array('hash'=>$block['previousblockhash']), ['class'=>'btn btn-sm btn-outline-secondary']) : '';
$nextLink = isset($block['nextblockhash'])     ? $coin->createExplorerLink('Next<i class="fa fa-chevron-right ms-1"></i>', array('hash'=>$block['nextblockhash']), ['class'=>'btn btn-sm btn-outline-secondary']) : '';
$listLink = $coin->createExplorerLink('<i class="fa fa-list me-1"></i>All Blocks', [], ['class'=>'btn btn-sm btn-outline-primary']);

?>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-dark text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold">
                <img width="20" src="<?= htmlspecialchars($coin->image) ?>" class="rounded-circle me-2 shadow-sm">
                <?= htmlspecialchars($coin->name) ?> <span class="text-muted small fw-normal">Block #<?= number_format($block['height']) ?></span>
                <?= $typeBadge ?>
            </h5>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form action="<?= $actionUrl ?>" method="POST" class="d-flex gap-2 align-items-center">
                <?php echo csrf_field(); ?>
                <input type="text" name="height" class="form-control form-control-sm" placeholder="Height" style="width:85px;">
                <input type="text" name="txid" class="form-control form-control-sm font-monospace" placeholder="Transaction / block hash" style="width:260px;">
                <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Search</button>
            </form>
            <div class="d-flex gap-1"><?= $prevLink ?><?= $listLink ?><?= $nextLink ?></div>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <table class="table table-sm table-borderless small mb-0">
                    <tr><td class="text-muted fw-bold" style="width:140px;">Coin</td><td><?= $coin->createExplorerLink(htmlspecialchars($coin->name)) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Height</td><td class="fw-bold"><?= number_format($block['height']) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Time</td><td><?= $d ?> <span class="text-muted">(<?= $block['time'] ?>)</span></td></tr>
                    <tr><td class="text-muted fw-bold">Difficulty</td><td><?= $block['difficulty'] ?></td></tr>
                    <tr><td class="text-muted fw-bold">Bits</td><td class="font-monospace"><?= htmlspecialchars($block['bits']) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Nonce</td><td class="font-monospace"><?= htmlspecialchars($nonce) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Version</td><td class="font-monospace"><?= htmlspecialchars($version) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Size</td><td><?= number_format($block['size']) ?> bytes</td></tr>
                    <?php if(isset($block['flags'])): ?><tr><td class="text-muted fw-bold">Flags</td><td class="font-monospace"><?= htmlspecialchars($block['flags']) ?></td></tr><?php endif; ?>
                    <tr><td class="text-muted fw-bold">Confirmations</td><td><?= number_format($confirms) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Transactions</td><td><?= $txcount ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless small mb-0">
                    <?php if(isset($block['previousblockhash'])): ?>
                    <?php if($coin->algo == 'x16r'): ?>
                    <tr><td class="text-muted fw-bold" style="width:140px;">Hash order</td><td class="font-monospace"><?= htmlspecialchars(substr($block['previousblockhash'], -16)) ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="text-muted fw-bold">Previous Hash</td><td class="font-monospace small text-break"><?= $coin->createExplorerLink(htmlspecialchars(substr($block['previousblockhash'],0,24)).'…', array('hash'=>$block['previousblockhash'])) ?></td></tr>
                    <?php endif; ?>
                    <?php if(isset($block['nextblockhash'])): ?>
                    <tr><td class="text-muted fw-bold">Next Hash</td><td class="font-monospace small text-break"><?= $coin->createExplorerLink(htmlspecialchars(substr($block['nextblockhash'],0,24)).'…', array('hash'=>$block['nextblockhash'])) ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="text-muted fw-bold">Blockhash</td><td class="font-monospace small text-break"><span class="txid"><?= htmlspecialchars($hash) ?></span></td></tr>
                    <tr><td class="text-muted fw-bold">Merkle Root</td><td class="font-monospace small text-break"><?= htmlspecialchars($block['merkleroot']) ?></td></tr>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-2">
            <button class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" onclick="$('tr.raw').toggle()">Toggle Raw JSON</button>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-muted text-uppercase small"><i class="fa fa-exchange-alt me-2 text-primary"></i>Transactions (<?= $txcount ?>)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                    <tr>
                        <th class="ps-4" style="width:40px;">#</th>
                        <th>Transaction Hash</th>
                        <th class="text-end">Size</th>
                        <th class="text-end">Value</th>
                        <th>From</th>
                        <th>To (amount)</th>
                    </tr>
                </thead>
                <tbody>
<?php
$ntx = 0;
foreach($block['tx'] as $txhash)
{
    $ntx++;
    // Some nodes return full tx objects in getblock (verbosity=2) instead of txid strings
    if(is_array($txhash) && isset($txhash['txid'])) {
        $tx = $txhash;
        $txhash = $tx['txid'];
    } else {
        // Pass block hash as 3rd param — Bitcoin Core 0.17+ allows getrawtransaction
        // without txindex when the containing block hash is provided.
        $tx = $remote->getrawtransaction($txhash, 1, $hash);
        if (!$tx) $tx = $remote->getrawtransaction($txhash, 1);
        if(!$tx && ($ntx == 1 || $txid == $txhash)) {
            $tx = $remote->gettransaction($txhash);
            if ($tx && isset($tx['hex'])) {
                $hex = $tx['hex'];
                $tx = $remote->decoderawtransaction($hex);
                $tx['hex'] = $hex;
            } else { continue; }
        }
    }
    if(!$tx || empty($tx['vout'])) {
        $fallback_id = is_string($txhash) ? $txhash : (is_array($txhash) && isset($txhash['txid']) ? $txhash['txid'] : 'unknown');
        echo '<tr class="ssrow"><td class="ps-4 text-muted">'.$ntx.'</td>';
        echo '<td class="font-monospace small">'.htmlspecialchars($fallback_id).'</td>';
        echo '<td colspan="4" class="text-muted small"><i>Transaction details unavailable — check wallet txindex setting</i></td></tr>';
        continue;
    }

    $valuetx = 0;
    foreach($tx['vout'] as $vout) {
        if (isset($vout['value'])) $valuetx += $vout['value'];
    }

    $size = isset($tx['hex']) ? (strlen($tx['hex'])/2) : '?';
    $segwit = false;
    $is_coinbase = false;
    $total_input = 0;
    $from_html = '';
    foreach($tx['vin'] as $vin) {
        if(isset($vin['coinbase'])) { $from_html = '<span class="badge bg-warning text-dark">Generation</span>'; $is_coinbase = true; }
        if(isset($vin['txinwitness'])) $segwit = true;
    }
    if($segwit) $from_html .= ' <img src="/images/ui/segwit.png" height="10" title="SegWit" style="vertical-align:middle;">';
    if(!$is_coinbase) {
        $vin0 = $tx['vin'][0];
        if(isset($vin0['addr'])) {
            $from_html = '<span class="font-monospace" style="font-size:0.75em;">'.htmlspecialchars($vin0['addr']).'</span>';
        } else if(isset($vin0['txid'])) {
            $prev_tx = $remote->getrawtransaction($vin0['txid'], 1);
            $prev_vout_idx = (int) $vin0['vout'];
            if($prev_tx && isset($prev_tx['vout'][$prev_vout_idx])) {
                $prev_out = $prev_tx['vout'][$prev_vout_idx];
                $prev_spk = $prev_out['scriptPubKey'];
                // Accumulate input value so we can show net reward for PoS
                $total_input += (float) $prev_out['value'];
                $prev_addr = arraySafeVal($prev_spk, 'address',
                             arraySafeVal($prev_spk, 'addresses', [null])[0] ?? null);
                if($prev_addr)
                    $from_html = '<span class="font-monospace" style="font-size:0.75em;">'.htmlspecialchars($prev_addr).'</span>';
                else
                    $from_html = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">PoS Stake</span>';
            }
        }
        if(empty($from_html))
            $from_html = '<span class="text-muted small">—</span>';
    }
    // Only show net reward for PoS coinstake (2nd tx in a PoS block)
    // For all other txs show total output value
    $is_coinstake = ($block_type == 'PoS' && $ntx == 2 && $total_input > 0);
    $display_value = $is_coinstake ? round($valuetx - $total_input, 8) : $valuetx;

    $nvout = count($tx['vout']);
    $to_html = '';
    if ($nvout > 500) {
        $to_html = '<span class="text-muted">Too many addresses ('.$nvout.')</span>';
    } else {
        foreach($tx['vout'] as $vout) {
            $value = $vout['value'];
            if ($value == 0) continue;
            $spk = $vout['scriptPubKey'];
            // Try address (singular, newer RPC), then addresses array, then pubkey (P2PK)
            $addr = arraySafeVal($spk, 'address', null)
                 ?? (isset($spk['addresses'][0]) ? $spk['addresses'][0] : null)
                 ?? (isset($spk['asm']) && strpos($spk['asm'], 'OP_CHECKSIG') !== false ? substr($spk['asm'], 0, strpos($spk['asm'], ' ')) : null);
            if($addr && strlen($addr) > 10)
                $to_html .= '<span class="font-monospace" style="font-size:0.75em;">'.htmlspecialchars($addr).'</span> <b class="text-success">'.$value.'</b><br>';
            else
                $to_html .= '<span class="text-muted small">('.htmlspecialchars($value).')</span><br>';
        }
    }
    unset($tx['hex']);
    $json_encoded = json_encode($tx, JSON_PRETTY_PRINT);
    $json_row = ($nvout > 500 || $json_encoded === false) ? 'truncated' : colorizeJson($json_encoded);
    ?>
                    <tr class="<?= ($txhash == $txid ? 'table-danger' : 'ssrow') ?>">
                        <td class="ps-4 text-muted"><?= $ntx ?></td>
                        <td><span class="txid font-monospace small"><?= htmlspecialchars($txhash) ?></span></td>
                        <td class="text-end"><?= $size ?></td>
                        <td class="text-end fw-bold"><?= $display_value ?></td>
                        <td><?= $from_html ?></td>
                        <td><?= $to_html ?></td>
                    </tr>
                    <tr class="raw" style="display:none;">
                        <td colspan="6"><div class="json-block"><?= $json_row ?></div></td>
                    </tr>
<?php
    if ($ntx > 100) {
        echo '<tr><td colspan="6" class="text-center text-muted py-3">Too many transactions to display…</td></tr>';
        break;
    }
}
?>
<?php if ($coin->rpcencoding == 'DCR' && isset($block['stx'])): ?>
                    <tr><th colspan="6" class="table-dark ps-4 py-2 small text-uppercase text-muted">Stake Transactions</th></tr>
<?php
    $ntx = 0;
    foreach($block['stx'] as $txhash) {
        $ntx++;
        $stx = $remote->getrawtransaction($txhash, 1);
        if(!$stx) continue;
        $valuetx = 0;
        foreach($stx['vout'] as $vout) $valuetx += $vout['value'];
        $size = isset($stx['hex']) ? (strlen($stx['hex'])/2) : '?';
        $from_html = '';
        if(isset($stx['vout'][0]['scriptPubKey']) && arraySafeVal($stx['vout'][0]['scriptPubKey'],'type') == 'stakesubmission')
            $from_html = '<span class="badge bg-info text-dark">Ticket</span>';
        else foreach($stx['vin'] as $vin) {
            if (arraySafeVal($vin,'blockheight') > 0)
                $from_html .= $coin->createExplorerLink('#'.arraySafeVal($vin,'blockheight'), array('height'=>arraySafeVal($vin,'blockheight'))).' ';
        }
        $to_html = '';
        foreach($stx['vout'] as $vout) {
            if ($vout['value'] == 0) continue;
            if(isset($vout['scriptPubKey']['addresses'][0]))
                $to_html .= '<span class="font-monospace" style="font-size:0.75em;">'.htmlspecialchars($vout['scriptPubKey']['addresses'][0]).'</span> <b>'.$vout['value'].'</b><br>';
            else $to_html .= '('.$vout['value'].')<br>';
        }
        unset($stx['hex']);
        echo '<tr class="ssrow"><td class="ps-4 text-muted">'.$ntx.'</td>';
        echo '<td><span class="txid font-monospace small">'.htmlspecialchars($txhash).'</span></td>';
        echo '<td class="text-end">'.$size.'</td><td class="text-end fw-bold">'.$valuetx.'</td>';
        echo '<td>'.$from_html.'</td><td>'.$to_html.'</td></tr>';
        echo '<tr class="raw" style="display:none;"><td colspan="6"><div class="json-block">'.colorizeJson(json_encode($stx, 128)).'</div></td></tr>';
    }
?>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
