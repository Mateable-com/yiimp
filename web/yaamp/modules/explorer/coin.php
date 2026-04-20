<?php

if (!$coin) $this->goback();

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");

$this->pageTitle = $coin->name." block explorer";

$start = (int) getiparam('start');

echo <<<END
<script type="text/javascript">
$(function() {
    $('#favicon').remove();
    $('head').append('<link href="{$coin->image}" id="favicon" rel="shortcut icon">');
});
</script>
END;

$multiAlgos = $coin->multialgos || versionToAlgo($coin, 0) !== false;

$actionUrl = $coin->visible ? '/explorer/'.$coin->symbol : '/explorer/search?id='.$coin->id;

echo '<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><img width="20" src="'.htmlspecialchars($coin->image).'" class="rounded-circle me-2 shadow-sm">'.$coin->name.' <span class="text-muted small">Block Explorer</span></h5>';
echo '    <form action="'.htmlspecialchars($actionUrl).'" method="POST" class="d-flex gap-2 align-items-center">';
echo '      <input type="text" name="height" class="form-control form-control-sm" placeholder="Height" style="width:90px;">';
echo '      <input type="text" name="txid" class="form-control form-control-sm font-monospace" placeholder="Transaction hash" style="width:300px;">';
echo '      <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">Search</button>';
echo '    </form>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4">Age</th>';
echo '            <th>Height</th>';
echo '            <th>Difficulty</th>';
echo '            <th>Type</th>';
if ($multiAlgos) echo '            <th>Algo</th>';
echo '            <th class="text-center">Tx</th>';
echo '            <th class="text-center">Conf</th>';
echo '            <th>Blockhash</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

$remote = new WalletRPC($coin);
if (!$start || $start > $coin->block_height)
    $start = $coin->block_height;

for($i = $start; $i > max(1, $start-21); $i--)
{
    $hash = $remote->getblockhash($i);
    if(!$hash) continue;

    $block = $remote->getblock($hash);
    if(!$block) continue;

    $d = datetoa2($block['time']);
    $confirms = isset($block['confirmations'])? $block['confirmations']: '';
    $tx = count($block['tx']);
    $diff = $block['difficulty'];
    $algo = versionToAlgo($coin, $block['version']);
    $type = '';
    $nonce = arraySafeVal($block, 'nonce', null);
    $flags = strtolower(arraySafeVal($block, 'flags', ''));

    if (isset($block['auxpow'])) {
        $type = 'Aux';
    } else if (strpos($flags, 'proof-of-stake') !== false || strpos($flags, 'stake') !== false || isset($block['mint'])) {
        // Wallet returned explicit stake flags
        $type = 'PoS';
    } else if ($nonce === 0 || $nonce === '0' || $nonce === '00000000') {
        // Nonce == 0 is the canonical PoS block indicator for all Peercoin-derived hybrid coins
        $type = 'PoS';
    } else if ($nonce !== null && $nonce > 0) {
        $type = 'PoW';
    }
    if ($type == '' && $coin->symbol == 'ZEC') $type = 'PoW';

    $typeBadge = '';
    if ($type == 'PoW') $typeBadge = '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">PoW</span>';
    else if ($type == 'PoS') $typeBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">PoS</span>';
    else if ($type == 'Aux') $typeBadge = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Aux</span>';

    echo '<tr>';
    echo '  <td class="ps-4 text-muted">'.$d.'</td>';
    echo '  <td>'.$coin->createExplorerLink('<span class="fw-bold">'.$i.'</span>', array('height'=>$i)).'</td>';
    echo '  <td class="font-monospace small">'.$diff.'</td>';
    echo '  <td>'.$typeBadge.'</td>';
    if ($multiAlgos) {
        // Don't show algo for PoS blocks — the type column already indicates it
        if ($type == 'PoS' || empty($algo))
            echo '  <td class="text-center text-muted small">—</td>';
        else
            echo '  <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 text-uppercase">'.htmlspecialchars($algo).'</span></td>';
    }
    echo '  <td class="text-center">'.$tx.'</td>';
    echo '  <td class="text-center">'.$confirms.'</td>';
    echo '  <td class="font-monospace small text-truncate" style="max-width: 320px;">'.$coin->createExplorerLink($hash, array('hash'=>$hash)).'</td>';
    echo '</tr>';
}

echo '        </tbody>';
echo '      </table></div></div>';

// Pager
$pager = '';
if ($start <= $coin->block_height - 20)
    $pager .= $coin->createExplorerLink('<i class="fa fa-chevron-left me-1"></i>Prev', array('start'=>min($coin->block_height,$start+20)), ['class'=>'btn btn-sm btn-outline-secondary']);
if ($start != $coin->block_height)
    $pager .= ' '.$coin->createExplorerLink('Latest', [], ['class'=>'btn btn-sm btn-outline-primary']);
if ($start > 20)
    $pager .= ' '.$coin->createExplorerLink('Next<i class="fa fa-chevron-right ms-1"></i>', array('start'=>max(1,$start-20)), ['class'=>'btn btn-sm btn-outline-secondary']);

if ($pager) {
    echo '  <div class="card-footer bg-light border-0 py-2 px-4 text-end">'.$pager.'</div>';
}
echo '</div>';

if ($start != $coin->block_height)
    return;

echo <<<end
<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
  <div class="card-header bg-white py-3 px-4 border-0">
    <h6 class="mb-0 fw-bold text-muted text-uppercase small"><i class="fa fa-chart-line me-2 text-primary"></i>Network Difficulty</h6>
  </div>
  <div class="card-body p-3">
    <div id='diff_graph' style='height: 240px;'></div>
  </div>
</div>

<script type="text/javascript">
var last_graph_update = 0;

function graph_refresh()
{
    var now = Date.now()/1000;
    if (now < last_graph_update + 900) return;
    last_graph_update = now;
    $.get("/explorer/graph?id={$coin->id}", '', diff_graph_data);
}

function diff_graph_data(data)
{
    var t = $.parseJSON(data);
    var plot1 = $.jqplot('diff_graph', t, {
        axes: {
            xaxis: { renderer: $.jqplot.DateAxisRenderer, tickOptions: { formatString: '%H:%M' } },
            yaxis: { min: 0.0, tickOptions: { labelPosition: 'top', formatString: '%.3f' } }
        },
        seriesDefaults: { markerOptions: { style: 'none' }, shadow: false },
        series: [
            { color: '#3b82f6', highlighter: { yvalues: 2, formatString: '<small>%s %.3f<br/>Block %u</small>' } },
            { showLine: false, markerOptions: { style: 'circle', size: 6, color: 'silver' }, animation: { show: true },
              highlighter: { yvalues: 3, formatString: '<small>%s %g<br/>User block %u</small>' } }
        ],
        grid: { borderWidth: 0, shadow: false, background: 'transparent' },
        highlighter: { show: true },
    });
}
</script>
end;

app()->clientScript->registerScript('graph',"
    graph_refresh();
", CClientScript::POS_READY);
