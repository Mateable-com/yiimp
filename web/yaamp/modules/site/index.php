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

// --- Global Stats for Header ---
$mining = getdbosql('db_mining');
$total_workers = getdbocount('db_workers');
$total_hashrate = 0;
foreach(yaamp_get_algos() as $a) {
    $total_hashrate += controller()->memcache->get_database_scalar("current_hashrate-$a", 
        "select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo' => $a));
}
?>

<div id='resume_update_button' class="alert alert-warning text-center shadow-sm mb-4 fw-bold animate-pulse" style='cursor: pointer; display: none;' onclick='auto_page_resume();'>
    <i class="fa fa-play me-2"></i>Live Data Paused - Click to Resume
</div>

<!-- --- PREMIUM HERO SECTION --- -->
<div class="row mb-5 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-lg overflow-hidden rounded-4 bg-dark text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
            <div class="card-body p-5">
                <div class="row align-items-center">
                    <div class="col-lg-7">
                        <div class="badge bg-primary px-3 py-2 rounded-pill mb-3 fw-bold text-uppercase small" style="letter-spacing: 2px;">Professional Grade Infrastructure</div>
                        <h1 class="display-4 fw-bold mb-3"><?=settings_get('site_name', YAAMP_SITE_NAME)?></h1>
                        <p class="lead opacity-75 mb-4 pe-lg-5">High-performance multi-algo mining with automated payouts, maximum transparency, and ultra-low fees. Built for professional miners who demand the best.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="javascript:void(0);" onclick="document.getElementById('mine-now').scrollIntoView({behavior: 'smooth'});" class="btn btn-primary btn-lg px-5 py-3 fw-bold rounded-pill shadow-lg hover-up">START MINING NOW</a>
                            <a href="/site/mining" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold rounded-pill shadow-sm">VIEW LIVE STATS</a>
                        </div>
                    </div>
                    <div class="col-lg-5 d-none d-lg-block">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 p-4 rounded-4 border border-white border-opacity-10 backdrop-blur">
                                    <div class="text-primary small fw-bold mb-1 text-uppercase">Total Power</div>
                                    <h3 class="mb-0 fw-bold"><?=Itoa2($total_hashrate)?>h/s</h3>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 p-4 rounded-4 border border-white border-opacity-10 backdrop-blur">
                                    <div class="text-success small fw-bold mb-1 text-uppercase">Online Rigs</div>
                                    <h3 class="mb-0 fw-bold"><?=number_format($total_workers)?></h3>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 p-4 rounded-4 border border-white border-opacity-10 backdrop-blur">
                                    <div class="text-warning small fw-bold mb-1 text-uppercase">BTC/USD</div>
                                    <h3 class="mb-0 fw-bold">$<?=number_format($mining->usdbtc, 0)?></h3>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 p-4 rounded-4 border border-white border-opacity-10 backdrop-blur">
                                    <div class="text-info small fw-bold mb-1 text-uppercase">Fee</div>
                                    <h3 class="mb-0 fw-bold">0.5%</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4" id="mine-now">
    <div class="col-lg-6">
        <!-- Miner Config Generator -->
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden h-100">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h4 class="mb-0 fw-bold text-dark"><i class="fa fa-terminal me-2 text-primary"></i>Configuration Generator</h4>
                <p class="text-muted small mt-1">Get your personalized connection string in seconds.</p>
            </div>
            <div class="card-body p-4 pt-3">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Stratum Region</label>
                        <select id="drop-stratum" class="form-select border-2 bg-light px-3 py-2 rounded-3" onchange="generate()">
                            <option value="">Auto-Select (Recommended)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Select Asset</label>
                        <select id="drop-coin" class="form-select border-2 bg-light px-3 py-2 rounded-3" onchange="updateAuxCoins(); generate()">
                            <?php
                            $list = getdbolist('db_coins', "enable and visible and auto_ready order by algo asc");
                            if (!$list) echo "<option disabled>No Coins Available</option>";
                            else {
                                $algoheading = "";
                                foreach ($list as $coin) {
                                    $symbol = $coin->getOfficialSymbol(); $algo = $coin->algo;
                                    $port_db = getdbosql('db_stratums', "algo=:algo and symbol=:symbol", [':algo' => $algo, ':symbol' => $symbol]);
                                    $port = $port_db ? $port_db->port : '0000';
                                    if ($algo != $algoheading) echo "<optgroup label='".strtoupper($algo)."'></optgroup>";
                                    $mc_param = (isset($coin->auto_exchange) && $coin->auto_exchange == 0) ? ",mc=$symbol" : "";
                                    echo "<option value='$symbol' data-port='$port' data-algo='-a $algo' data-symbol='$symbol' data-extra='-p c=$symbol$mc_param'>{$coin->name} ($symbol)</option>";
                                    $algoheading = $algo;
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted text-uppercase">Wallet Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-2 border-end-0 text-muted"><i class="fa fa-wallet"></i></span>
                            <input id="text-wallet" type="text" class="form-control border-2 border-start-0 bg-light px-2 py-2" placeholder="Paste your payout address" onkeyup="generate()" autocomplete="off">
                        </div>
                        <div id="wallet-error" class="text-danger small mt-1 fw-bold" style="display:none;"><i class="fa fa-exclamation-triangle me-1"></i> Invalid address length</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-muted text-uppercase">Worker Name</label>
                        <input id="text-rig-name" type="text" class="form-control border-2 bg-light px-3 py-2 rounded-3" placeholder="e.g. rig1" onkeyup="generate()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Type</label>
                        <select id="drop-solo" class="form-select border-2 bg-light px-3 py-2 rounded-3" onchange="generate()">
                            <option value="">Shared Pool</option>
                            <option value=",m=solo">Solo Mining</option>
                        </select>
                    </div>
                    <?php if (YAAMP_RENTAL): ?>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted text-uppercase">Renter BTC Payout <small class="text-muted">(Optional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-2 border-end-0 text-muted"><i class="fa fa-bitcoin"></i></span>
                            <input id="text-rent-address" type="text" class="form-control border-2 border-start-0 bg-light px-2 py-2" placeholder="BTC Address for Renter Bonuses" onkeyup="generate()" autocomplete="off">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- --- DYNAMIC MERGED MINING SECTION --- -->
                <div id="merged-mining-container" class="mb-4 d-none">
                    <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa fa-layer-group text-primary me-2"></i>
                                <span class="fw-bold small text-uppercase text-primary" style="letter-spacing: 1px;">Merged Mining (AuxPoW)</span>
                            </div>
                            <p class="text-muted small mb-3">Enable additional coins to mine simultaneously without losing hashrate on your primary coin.</p>
                            <div id="aux-coins-list" class="row g-2"></div>
                        </div>
                    </div>
                </div>

                <div class="position-relative mb-2">
                    <div id="len-warning" class="badge bg-warning text-dark position-absolute top-0 start-0 m-2 d-none shadow-sm" style="z-index: 10;"><i class="fa fa-exclamation-triangle me-1"></i> Warning: Password field might be too long!</div>
                    <pre class="bg-dark text-success p-4 rounded-4 shadow-sm mb-0 font-monospace" style="font-size: 0.9rem; border: 1px solid #334155;">
<span id="output" class="text-break opacity-75">-a algo -o stratum+tcp://<?=YAAMP_STRATUM_URL?>:port -u wallet.worker -p c=symbol</span></pre>
                    <button class="btn btn-sm btn-link text-success position-absolute top-0 end-0 m-2 text-decoration-none fw-bold" onclick="copyConfig()"><i class="fa fa-copy me-1"></i> COPY</button>
                </div>
                <div class="text-muted small text-center"><i class="fa fa-shield-alt me-1 opacity-50"></i> Payouts are automated every <?=$payout_freq?>.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div id='pool_current_results'></div>
        <div id='pool_history_results'></div>
    </div>
</div>

<?php
// Pre-fetch AuxPoW coins for JavaScript
$aux_list = getdbolist('db_coins', "enable and visible and auto_ready and auxpow=1 order by algo asc");
$aux_coins_js = [];
foreach ($aux_list as $coin) {
    $algo = $coin->algo;
    if (!isset($aux_coins_js[$algo])) $aux_coins_js[$algo] = [];
    $aux_coins_js[$algo][] = ['symbol' => $coin->getOfficialSymbol(), 'name' => $coin->name];
}
$aux_coins_json = json_encode($aux_coins_js);
?>

<script>
var aux_coins = <?php echo $aux_coins_json; ?>;

function updateAuxCoins() {
    var coinSelect = document.getElementById('drop-coin');
    var algo = coinSelect.options[coinSelect.selectedIndex].dataset.algo.replace('-a ', '');
    var container = document.getElementById('merged-mining-container');
    var list = document.getElementById('aux-coins-list');
    
    list.innerHTML = '';
    
    if (aux_coins[algo] && aux_coins[algo].length > 0) {
        container.classList.remove('d-none');
        aux_coins[algo].forEach(function(c) {
            var col = document.createElement('div');
            col.className = 'col-md-6';
            col.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check-${c.symbol}" onchange="generate()">
                    <label class="form-check-label small fw-bold" for="check-${c.symbol}">${c.name} (${c.symbol})</label>
                    <input type="text" id="wallet-${c.symbol}" class="form-control form-control-sm mt-1 d-none" placeholder="${c.symbol} Wallet Address" onkeyup="generate()">
                </div>`;
            list.appendChild(col);
            
            // Toggle visibility of the wallet input when checked
            document.getElementById('check-'+c.symbol).addEventListener('change', function() {
                var input = document.getElementById('wallet-'+c.symbol);
                if (this.checked) input.classList.remove('d-none');
                else { input.classList.add('d-none'); input.value = ''; }
            });
        });
    } else {
        container.classList.add('d-none');
    }
}

function copyConfig() {
    var text = document.getElementById('output').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Miner configuration copied to clipboard!');
    });
}

function page_refresh() {
    pool_current_refresh();
    pool_history_refresh();
}

function select_algo(algo) { window.location.href = '/site/algo?algo='+algo+'&r=/'; }
function pool_current_ready(data) { $('#pool_current_results').html(data); }
function pool_current_refresh() { $.get("/site/current_results", '', pool_current_ready); }
function pool_history_ready(data) { $('#pool_history_results').html(data); }
function pool_history_refresh() { $.get("/site/history_results", '', pool_history_ready); }

function getLastUpdated(){
    var stratum = document.getElementById('drop-stratum');
    var coin = document.getElementById('drop-coin');
    var solo = document.getElementById('drop-solo');
    var wallet = document.getElementById('text-wallet').value.trim();
    var rigName = document.getElementById('text-rig-name').value.trim();
    var rentAddrEl = document.getElementById('text-rent-address');
    var rentAddress = rentAddrEl ? rentAddrEl.value.trim() : '';
    
    var algo_param = coin.options[coin.selectedIndex].dataset.algo;
    var algo = algo_param.replace('-a ', '');
    var port = coin.options[coin.selectedIndex].dataset.port;
    var symbol = coin.options[coin.selectedIndex].dataset.symbol;
    var extra = coin.options[coin.selectedIndex].dataset.extra;

    var result = algo_param + ' -o stratum+tcp://' + stratum.value + '<?=YAAMP_STRATUM_URL?>:' + port + ' -u ';
    
    // Build primary credentials
    var userStr = wallet ? wallet : 'YOUR_WALLET';
    if (rigName) userStr += '.' + rigName;
    result += userStr;

    // Build password parameters (including Merged Mining)
    var passStr = extra + solo.value;
    if (rentAddress) passStr += ',r=' + rentAddress;
    if (aux_coins[algo]) {
        aux_coins[algo].forEach(function(c) {
            var check = document.getElementById('check-'+c.symbol);
            if (check && check.checked) {
                var w = document.getElementById('wallet-'+c.symbol).value.trim();
                if (w) passStr += ',m=' + c.symbol + ':' + w;
            }
        });
    }
    result += ' ' + passStr;
    
    return result;
}

function generate(){
    var wallet = document.getElementById('text-wallet').value.trim();
    var error = document.getElementById('wallet-error');
    if (wallet.length > 0 && wallet.length < 25) error.style.display = 'block';
    else error.style.display = 'none';

    var configStr = getLastUpdated();
    var passPart = configStr.split(' -p ')[1];
    var warning = document.getElementById('len-warning');
    var output = document.getElementById('output');

    if (passPart && passPart.length > 64) {
        warning.classList.remove('d-none');
        output.style.color = '#f97316'; // Orange
    } else {
        warning.classList.add('d-none');
        output.style.color = '#10b981'; // Green (default)
    }

    output.innerHTML = configStr;
}

$(function() {
    updateAuxCoins();
    generate();
    pool_current_refresh();
    pool_history_refresh();
});
</script>

<style>
    .text-gradient { background: linear-gradient(90deg, #3b82f6 0%, #2dd4bf 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .backdrop-blur { backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
    .hover-up { transition: all 0.3s ease; }
    .hover-up:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.2) !important; }
    #output { color: #10b981 !important; }
    .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
</style>
