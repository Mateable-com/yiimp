<?php

if (!isset($coin) || !$coin) {
    echo '<div class="alert alert-danger">Coin object not found.</div>';
    return;
}

$this->pageTitle = 'Console - '.$coin->symbol;
$query = isset($query) ? $query : '';

try {
    $remote = new WalletRPC($coin);
    $info = $remote->getinfo();
} catch (Exception $e) {
    $info = false;
    $remote_error = $e->getMessage();
}

echo '<div class="container-fluid py-4">';

// --- Header Card ---
echo '<div class="card shadow-sm border-0 mb-4 bg-dark text-white rounded-3">';
echo '  <div class="card-body p-4 d-flex align-items-center">';
echo '    <div class="me-4 shadow-sm bg-white rounded-circle p-2" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">';
echo '      <img src="'.$coin->image.'" style="max-width: 48px; max-height: 48px;">';
echo '    </div>';
echo '    <div class="flex-grow-1">';
echo '      <h3 class="mb-0 fw-bold">'.$coin->name.' <span class="text-primary fs-5">RPC Console</span></h3>';
echo '      <div class="small text-muted"><i class="fa fa-plug me-1"></i> Connected to '.$coin->rpchost.':'.$coin->rpcport.'</div>';
echo '    </div>';
echo '    <div class="ms-auto">';
echo '      <a href="/admin/coin?id='.$coin->id.'" class="btn btn-outline-light btn-sm rounded-pill fw-bold px-3"><i class="fa fa-arrow-left me-1"></i> Back to Wallet</a>';
echo '    </div>';
echo '  </div>';
echo '</div>';

if (!$info) {
    $err = isset($remote_error) ? $remote_error : (isset($remote->error) ? $remote->error : 'Unknown RPC error');
	echo '<div class="alert alert-danger shadow-sm border-0"><i class="fa fa-exclamation-triangle me-2"></i><b>RPC Connection Error:</b> '.$err.'</div>';
    echo '</div>'; // close container
	return;
}

//////////////////////////////////////////////////////////////////////////////////////

function colorizeJson($json)
{
	$json = str_replace('"', '&quot;', $json);
	// strings
	$res = preg_match_all("# &quot;([^&]+)&quot;([,\s])#", $json, $matches);
	if ($res) foreach($matches[1] as $n=>$m) {
		$sfx = $matches[2][$n];
		$class = '';
		if (strlen($m) == 64 && ctype_xdigit($m)) $class = 'hash';
		if (strlen($m) == 34 && ctype_alnum($m)) $class = 'addr';
		if (strlen($m) == 35 && ctype_alnum($m)) $class = 'addr';
		if (strlen($m) > 160 && ctype_alnum($m)) $class = 'data';
		if ($class == '' && strlen($m) < 64 && ctype_xdigit($m)) $class = 'hexa';
		$json = str_replace(' &quot;'.$m."&quot;".$sfx, ' "<s class="'.$class.'">'.$m.'</s>"'.$sfx, $json);
	}
	// keys
	$res = preg_match_all("#&quot;([^&]+)&quot;:#", $json, $matches);
	if ($res) foreach($matches[1] as $n=>$m) {
		$json = str_replace('&quot;'.$m."&quot;", '"<s class="key text-info">'.$m.'</s>"', $json);
	}
	// numeric
	$res = preg_match_all("#: ([e\-\.0-9]+)([,\s])#", $json, $matches);
	if ($res) foreach($matches[1] as $n=>$m) {
		$sfx = $matches[2][$n];
		$json = str_replace(' '.$m.$sfx, ' <i class="text-warning">'.$m.'</i>'.$sfx, $json);
	}
	$json = preg_replace('#\[\s+\]#', '[]', $json);
	$json = str_replace('[', '<b class="text-danger">[</b>', $json);
	$json = str_replace(']', '<b class="text-danger">]</b>', $json);
	$json = str_replace('{', '<b class="text-primary">{</b>', $json);
	$json = str_replace('}', '<b class="text-primary">}</b>', $json);
	return $json;
}

$last_query = htmlentities(trim($query));

echo <<<end
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
            <div class="card-header bg-secondary bg-opacity-10 py-3 border-0">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 fw-bold me-auto text-dark small text-uppercase" style="letter-spacing: 1px;">Command Input</h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-dark px-3 fw-bold" onclick="quickCmd('getinfo')">getinfo</button>
                        <button type="button" class="btn btn-outline-dark px-3 fw-bold" onclick="quickCmd('getbalance')">getbalance</button>
                        <button type="button" class="btn btn-outline-dark px-3 fw-bold" onclick="quickCmd('listtransactions')">listtransactions</button>
                    </div>
                </div>
            </div>
            <div class="card-body bg-light p-4">
                <form id="console-form" action="/admin/coinconsole?id={$coin->id}" method="post" class="mb-0">
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-dark border-dark text-success"><i class="fa fa-terminal"></i></span>
                        <input class="form-control bg-dark text-success border-dark font-monospace" value="{$last_query}" type="text" name="query" id="console-input" placeholder="Enter RPC command (e.g. getinfo, listtransactions, sendtoaddress...)" autocomplete="off">
                        <button class="btn btn-success fw-bold px-4" type="submit">EXECUTE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-lg border-0 rounded-3 bg-black overflow-hidden">
            <div class="card-header border-0 py-2 d-flex align-items-center" style="background: #1a1a1a">
                <div class="d-flex gap-1 me-2">
                    <span class="rounded-circle bg-danger" style="width: 10px; height: 10px;"></span>
                    <span class="rounded-circle bg-warning" style="width: 10px; height: 10px;"></span>
                    <span class="rounded-circle bg-success" style="width: 10px; height: 10px;"></span>
                </div>
                <div class="text-muted small fw-bold font-monospace mx-auto">RPC TERMINAL OUTPUT</div>
            </div>
            <div class="card-body p-4 font-monospace terminal-container" style="min-height: 300px; color: #00ff00;">
end;

$result = '';
if (!empty($query)) {
	$result = $remote->execute($query);
	if ($result === false) { $result = $remote->error; }
	debuglog("{$coin->symbol} CONSOLE {$query}");
}

if (!empty($remote->error) && $remote->error != $result) {
	$err = $remote->error;
    echo '<div class="text-danger fw-bold mb-3 border-bottom border-danger border-opacity-25 pb-2"><i class="fa fa-exclamation-circle me-2"></i>RPC ERROR:</div>';
	echo '<pre class="text-danger mb-4">';
	echo is_string($err) ? htmlentities($err) : htmlentities(json_encode($err, 128));
	echo '</pre>';
}

echo '<div class="terminal-body" style="white-space: pre-wrap; word-break: break-all;">';
if ($result !== '') {
    echo is_string($result) ? htmlentities($result) : colorizeJson(htmlentities(json_encode($result, 128)));
} else {
    echo '<div class="text-muted opacity-50"><i>Waiting for command...</i></div>';
}
echo '</div></div></div></div></div>';

echo '<style>
    .font-monospace { font-family: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important; }
    .terminal-container { max-height: 600px; overflow-y: auto; background: #000; }
    .terminal-body s { text-decoration: none; }
    .terminal-body s.addr a { color: #00ff00; text-decoration: underline; opacity: 0.8; }
    .terminal-body s.addr a:hover { opacity: 1; }
    #console-input::placeholder { color: #004400; }
    #console-input:focus { box-shadow: none; border-color: #00ff00; }
</style>';

echo <<<end
<script type="text/javascript">
function quickCmd(cmd) {
    $('#console-input').val(cmd);
    $('#console-form').submit();
}

var lazyLinks;
function main_json_links() {
	if (lazyLinks) clearTimeout(lazyLinks);
	jQuery('s.addr').each(function(n) {
		var el = $(this);
		var addr = el[0].innerText;
		var link = '<a href="/?address='+addr+'" target="_blank">' + addr + '</a>';
		el.html(link);
	});
	jQuery('s.hash').each(function(n) {
		var el = $(this);
		var hash = el[0].innerText;
		var link = '<a href="/explorer/search?SYM={$coin->symbol}&q='+hash+'" target="_blank">' + hash + '</a>';
		el.html(link);
	});
}

$(function() {
    $('#console-input').focus();
    lazyLinks = setTimeout(main_json_links, 1000);
});
</script>
end;

echo '</div>'; // close main container
?>