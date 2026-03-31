<?php
$algo = user()->getState('yaamp-algo');

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.cursor.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$height = '240px';

$min_payout = floatval(YAAMP_PAYMENTS_MINI);
$min_sunday = $min_payout / 10;

$payout_freq = (YAAMP_PAYMENTS_FREQ / 3600) . " hours";
?>

<div id='resume_update_button' class="alert alert-warning text-center shadow-sm mb-4" style='cursor: pointer; display: none;'
    onclick='auto_page_resume();'>
    <i class="fa fa-play me-2"></i><b>Auto refresh is paused - Click to resume</b>
</div>

<div class="row">
    <div class="col-lg-6">
        <!-- Welcome Section -->
        <div class="card mb-4 shadow-sm border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="fa fa-info-circle me-2"></i><?=YAAMP_SITE_URL?>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item border-0 px-0">Welcome to <?=YAAMP_SITE_URL?>!</li>
                    <li class="list-group-item border-0 px-0">No registration is required, we do payouts in the currency you mine. Use your wallet address as the username.</li>
                    <li class="list-group-item border-0 px-0">Payouts are made automatically every <?= $payout_freq ?> for all balances above <b><?= $min_payout ?></b> (<?= $min_sunday ?> on Sunday).</li>
                    <li class="list-group-item border-0 px-0">Blocks are distributed proportionally among valid submitted shares.</li>
                </ul>
            </div>
        </div>

        <!-- Miner Config Generator -->
        <div class="card mb-4 shadow-sm border-info">
            <div class="card-header bg-info text-dark fw-bold">
                <i class="fa fa-tools me-2"></i>How to mine with <?=YAAMP_SITE_URL?>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Stratum Location</label>
                        <select id="drop-stratum" class="form-select form-select-sm" onchange="generate()">
                            <option value="">Main Stratum</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Choose Coin</label>
                        <select id="drop-coin" class="form-select form-select-sm" onchange="updateAuxCoins(); generate()">
                            <?php
                            $list = getdbolist('db_coins', "enable and visible and auto_ready order by algo asc");
                            if (!$list) {
                                echo "<option disabled>No Coins Available</option>";
                            } else {
                                $algoheading = "";
                                foreach ($list as $coin) {
                                    $name = substr($coin->name, 0, 18);
                                    $symbol = $coin->getOfficialSymbol();
                                    $algo = $coin->algo;
                                    $auto_exchange = isset($coin->auto_exchange) ? $coin->auto_exchange : 1;
                                    $port_db = getdbosql('db_stratums', "algo=:algo and symbol=:symbol", [':algo' => $algo, ':symbol' => $symbol]);
                                    $port = $port_db ? $port_db->port : '0000';
                                    if ($algo != $algoheading) {
                                        echo "<optgroup label='$algo'></optgroup>";
                                    }
                                    $mc_param = ($auto_exchange == 0) ? ",mc=$symbol" : "";
                                    echo "<option value='$symbol' data-port='$port' data-algo='-a $algo' data-symbol='$symbol' data-extra='-p c=$symbol$mc_param'>$name ($symbol)</option>";
                                    $algoheading = $algo;
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Your Wallet Address</label>
                        <div class="input-group input-group-sm">
                            <input id="text-wallet" type="text" class="form-control" placeholder="Paste your wallet address here" onkeyup="generate()" autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('text-wallet').value=''; generate();"><i class="fa fa-times text-danger"></i></button>
                        </div>
                        <div id="wallet-error" class="text-danger small mt-1" style="display:none;"><i class="fa fa-exclamation-triangle me-1"></i> Address seems too short!</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Rig Name</label>
                        <input id="text-rig-name" type="text" class="form-control form-control-sm" placeholder="worker1" onkeyup="generate()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Type</label>
                        <select id="drop-solo" class="form-select form-select-sm" onchange="generate()">
                            <option value="">Shared</option>
                            <option value=",m=solo">Solo</option>
                        </select>
                    </div>
                </div>

                <div id="aux-coins-container" class="mb-3 small p-2 bg-light rounded border" style="display:none;"></div>

                <div class="bg-dark text-success p-3 rounded shadow-inner mb-2" style="font-family: 'Courier New', Courier, monospace; font-size: 0.85rem;">
                    <span id="output" class="text-break">-a  -o stratum+tcp://<?=YAAMP_STRATUM_URL?>:0000 -u . -p c=</span>
                </div>
                <div class="text-muted small">
                    <i class="fa fa-info-circle me-1"></i> Copy this to your miner command line or config file.
                </div>
            </div>
        </div>

        <!-- Links Section -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold">
                <i class="fa fa-link me-2"></i>Useful Links
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush small">
                    <a href="/site/api" class="list-group-item list-group-item-action"><i class="fa fa-code me-2"></i>API Reference</a>
                    <a href="/site/diff" class="list-group-item list-group-item-action"><i class="fa fa-balance-scale me-2"></i>Difficulty Explorer</a>
                    <?php if (YIIMP_PUBLIC_BENCHMARK): ?>
                    <a href="/site/benchmarks" class="list-group-item list-group-item-action"><i class="fa fa-stopwatch me-2"></i>Benchmarks</a>
                    <?php endif; ?>
                    <?php if (YAAMP_ALLOW_EXCHANGE): ?>
                    <a href="/site/multialgo" class="list-group-item list-group-item-action"><i class="fa fa-random me-2"></i>Algo Switching</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Support Section -->
        <div class="card mb-4 shadow-sm border-warning">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="fa fa-headset me-2"></i>Support
            </div>
            <div class="card-body text-center">
                <a href="https://discord.gg/DrsrWQh3qC" class="btn btn-lg btn-outline-primary w-100 py-3 shadow-sm border-2">
                    <i class="fab fa-discord fa-2x align-middle me-2"></i> Join our Discord
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div id='pool_current_results'></div>
        <div id='pool_history_results'></div>
        <div id='pool_coins_info'></div>
    </div>
</div>

<?php
$aux_list = getdbolist('db_coins', "enable and visible and auto_ready and auxpow=1 order by algo asc");
$aux_coins_js = [];
foreach ($aux_list as $coin) {
    $algo = $coin->algo;
    if (!isset($aux_coins_js[$algo])) {
        $aux_coins_js[$algo] = [];
    }
    $aux_coins_js[$algo][] = [
        'symbol' => $coin->getOfficialSymbol(),
        'name' => $coin->name
    ];
}
$aux_coins_json = json_encode($aux_coins_js);
?>

<script>
var aux_coins = <?php echo $aux_coins_json; ?>;

function updateAuxCoins() {
    var coin = document.getElementById('drop-coin');
    var algo = coin.options[coin.selectedIndex].dataset.algo.replace('-a ', '');
    var container = document.getElementById('aux-coins-container');
    container.innerHTML = '';
    container.style.display = 'none';

    if (aux_coins[algo]) {
        container.style.display = 'block';
        var html = '<div class="fw-bold mb-2">Merged Mining (Optional):</div>';
        aux_coins[algo].forEach(function(c) {
            html += '<div class="form-check mb-1">';
            html += '  <input class="form-check-input" type="checkbox" id="check-'+c.symbol+'" onchange="toggleAuxInput(\''+c.symbol+'\')">';
            html += '  <label class="form-check-label me-2" for="check-'+c.symbol+'">' + c.name + ' (' + c.symbol + ')</label>';
            html += '  <input type="text" id="wallet-'+c.symbol+'" class="form-control form-control-sm d-inline-block mt-1" style="display:none !important; width: 300px;" placeholder="'+c.symbol+' Wallet" onkeyup="generate()">';
            html += '</div>';
        });
        container.innerHTML = html;
    }
}

function toggleAuxInput(symbol) {
    var check = document.getElementById('check-' + symbol);
    var input = document.getElementById('wallet-' + symbol);
    input.style.setProperty('display', check.checked ? 'inline-block' : 'none', 'important');
    if (!check.checked) input.value = '';
    generate();
}

function page_refresh() {
    pool_current_refresh();
    pool_history_refresh();
	pool_coins_info_refresh();
}

function select_algo(algo) {
    window.location.href = '/site/algo?algo='+algo+'&r=/';
}

function pool_current_ready(data) { $('#pool_current_results').html(data); }
function pool_current_refresh() { $.get("/site/current_results", '', pool_current_ready); }

function pool_history_ready(data) { $('#pool_history_results').html(data); }
function pool_history_refresh() { $.get("/site/history_results", '', pool_history_ready); }

function pool_coins_info_ready(data) { $('#pool_coins_info').html(data); }
function pool_coins_info_refresh() { $.get("/site/coins_info", '', pool_coins_info_ready); }

function getLastUpdated(){
    var stratum = document.getElementById('drop-stratum');
    var coin = document.getElementById('drop-coin');
    var solo = document.getElementById('drop-solo');
    var wallet = document.getElementById('text-wallet').value.trim();
    var rigName = document.getElementById('text-rig-name').value.trim();
    
    var algo_param = coin.options[coin.selectedIndex].dataset.algo;
    var algo = algo_param.replace('-a ', '');
    var port = coin.options[coin.selectedIndex].dataset.port;
    var symbol = coin.options[coin.selectedIndex].dataset.symbol;
    var extra = coin.options[coin.selectedIndex].dataset.extra;

    var result = algo_param + ' -o stratum+tcp://' + stratum.value + '<?=YAAMP_STRATUM_URL?>:' + port + ' -u ';
    result += wallet ? wallet : 'WALLET_ADDRESS';
    result += rigName ? '.' + rigName : '.WORKER_NAME';
    result += ' ' + extra + solo.value;

    if (typeof aux_coins !== 'undefined' && aux_coins[algo]) {
        aux_coins[algo].forEach(function(c) {
            var check = document.getElementById('check-'+c.symbol);
            if (check && check.checked) {
                var w = document.getElementById('wallet-'+c.symbol).value.trim();
                if (w) result += ',m=' + c.symbol + ':' + w;
            }
        });
    }
    return result;
}

function generate(){
    var wallet = document.getElementById('text-wallet').value.trim();
    var error = document.getElementById('wallet-error');
    if (wallet.length > 0 && wallet.length < 25) {
        error.style.display = 'block';
    } else {
        error.style.display = 'none';
    }
    document.getElementById('output').innerHTML = getLastUpdated();
}

updateAuxCoins();
generate();
</script>
