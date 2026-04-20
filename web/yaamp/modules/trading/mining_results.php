<?php

$algo = user()->getState('yaamp-algo');
$total_rate = Itoa2(yaamp_pool_rate());

$list = getdbolist('db_coins', "enable and algo=:algo order by index_avg desc", array(':algo'=>$algo));
$count = count($list);
$worker = getdbocount('db_workers', "algo=:algo", array(':algo'=>$algo));
$services = getdbolist('db_services', "algo=:algo order by price desc", array(':algo'=>$algo));

////////////

$table = array(
    'scrypt'=>0,'sha256'=>1,'scryptn'=>2,'x11'=>3,'x13'=>4,
    'x15'=>6,'nist5'=>7,'neoscrypt'=>8,'lyra2'=>9,
);

$res = false;
if (isset($table[$algo]) && YAAMP_USE_NICEHASH_API)
    $res = fetch_url("https://api.nicehash.com/api?method=orders.get&algo={$table[$algo]}");

if(!$res) return;

$a = json_decode($res);
$niceorders = $a->result->orders;
$allorders = array();

$nicehash = getdbosql('db_nicehash', "algo=:algo and orderid!=0", array(':algo'=>$algo));
if($nicehash)
{
    $index = $nicehash->price*1000+1;
    $allorders[$index] = array(
        'speed'=>$nicehash->accepted, 'price'=>$nicehash->price,
        'workers'=>$nicehash->workers, 'btc'=>$nicehash->btc,
        'limit'=>$nicehash->speed, 'me'=>true
    );
}

foreach($niceorders as $order)
{
    if(!$order->alive || !$order->workers || !$order->type == 0) continue;
    $index = $order->price*1000;
    if(!isset($allorders[$index])) {
        $allorders[$index] = array('price'=>$order->price,'speed'=>0,'workers'=>0,'btc'=>0,'limit'=>0);
    }
    $allorders[$index]['speed'] += $order->accepted_speed;
    $allorders[$index]['workers'] += $order->workers;
    $allorders[$index]['limit'] += $order->limit_speed;
}

$total_nicehash = 0;
foreach($allorders as $i=>$order) $total_nicehash += $order['speed'];

function cmp($a, $b) { return $a['price'] < $b['price']; }
usort($allorders, 'cmp');

///////

echo '<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">';
echo '  <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">';
echo '    <h5 class="mb-0 fw-bold"><i class="fa fa-coins me-2 text-warning"></i>Mining '.$count.' coins at '.$total_rate.'h/s with '.$worker.' miners <span class="text-warning small text-uppercase">('.$algo.')</span></h5>';
echo '  </div>';
echo '  <div class="card-body p-0">';
echo '    <div class="table-responsive">';
echo '      <table class="table table-hover align-middle mb-0 small">';
echo '        <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">';
echo '          <tr>';
echo '            <th class="ps-4" style="width:40px;"></th>';
echo '            <th>Name</th>';
echo '            <th class="text-end">Reward</th>';
echo '            <th class="text-end">mBTC</th>';
echo '            <th class="text-end">Profit</th>';
echo '            <th class="text-end">TTF</th>';
echo '            <th class="text-end">Hashrate</th>';
echo '            <th class="text-end pe-4">mBTC/MH/d</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody>';

foreach($list as $coin)
{
    $name = htmlspecialchars(substr($coin->name, 0, 12));
    $difficulty = Itoa2($coin->difficulty, 3);
    $height = number_format($coin->block_height, 0, '.', ' ');
    $pool_ttf = $coin->pool_ttf ? sectoa2($coin->pool_ttf) : '';
    $reward = round($coin->reward, 3);
    $btcmhd = mbitcoinvaluetoa(yaamp_profitability($coin));

    $pool_hash = yaamp_coin_rate($coin->id);
    $pool_hash = $pool_hash ? Itoa2($pool_hash).'h/s' : '';

    show_orders($allorders, $services, $btcmhd);
    show_services($services, $btcmhd);

    $opacity = !$coin->auto_ready ? ' style="opacity:0.45;"' : '';

    echo '<tr'.$opacity.'>';
    echo '  <td class="ps-4"><img width="18" src="'.htmlspecialchars($coin->image).'" class="rounded-circle shadow-sm"></td>';
    echo '  <td><a href="/site/coin?id='.$coin->id.'" class="fw-bold text-decoration-none text-dark">'.$name.'</a></td>';
    echo '  <td class="text-end small">'.$reward.' <small class="text-muted">'.htmlspecialchars($coin->symbol).'</small></td>';
    echo '  <td class="text-end small">'.$difficulty.'</td>';
    if(!empty($coin->errors))
        echo '  <td class="text-end small text-danger fw-bold" title="'.htmlspecialchars($coin->errors).'">'.$height.'</td>';
    else
        echo '  <td class="text-end small">'.$height.'</td>';
    echo '  <td class="text-end small text-muted">'.$pool_ttf.'</td>';
    echo '  <td class="text-end small">'.$pool_hash.'</td>';
    echo '  <td class="text-end pe-4 fw-bold">'.$btcmhd.'</td>';
    echo '</tr>';
}

show_orders($allorders, $services);
show_services($services);

echo '        </tbody>';
echo '      </table></div></div>';

$version = 'NiceHash/1.0.0';
$target = yaamp_hashrate_constant($algo);
$interval = yaamp_hashrate_step();
$delay = time()-$interval;

$hashrate = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and
    workerid in (select id from workers where algo=:algo and version='$version')", array(':algo'=>$algo));

$count_nh = getdbocount('db_workers', "algo=:algo and version='$version'", array(':algo'=>$algo));
$percent = $total_nicehash && $hashrate ? round($hashrate * 100 / $total_nicehash / 1000000000, 2).'%' : '';

$hashrate_str = $hashrate ? Itoa2($hashrate).'h/s' : '-';
$total_nicehash_str = round($total_nicehash, 3);

echo '  <div class="card-footer bg-light py-2 px-4 small text-muted d-flex gap-4">';
echo '    <span>NiceHash total: <b class="text-dark">'.$total_nicehash_str.' Gh/s</b></span>';
echo '    <span>YiiMP NiceHash: <b class="text-dark">'.$hashrate_str.'</b></span>';
echo '    <span>'.$count_nh.' workers '.$percent.'</span>';
echo '  </div>';
echo '</div>';

//////////////////////////////////////////////////////////////////////////////////////////////////

function show_services(&$services, $btcmhd=0)
{
    if(!controller()->admin || !$services) return;
    foreach($services as $i=>$service)
    {
        if($service->price*1000 < $btcmhd) continue;
        $service_btcmhd = mbitcoinvaluetoa($service->price*1000);

        echo '<tr>';
        echo '  <td class="ps-4"><img width="18" src="/images/btc.png" class="rounded-circle shadow-sm"></td>';
        echo '  <td class="fw-bold">'.htmlspecialchars($service->name).'</td>';
        echo '  <td colspan="6" class="text-end pe-4 fw-bold">'.$service_btcmhd.'</td>';
        echo '</tr>';

        unset($services[$i]);
    }
}

function show_orders(&$allorders, &$services, $btcmhd=0)
{
    $algo = user()->getState('yaamp-algo');
    $price = controller()->memcache->get_database_scalar("current_price-$algo",
        "select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));

    foreach($allorders as $i=>$order)
    {
        if($order['price'] < $btcmhd) continue;
        if($order['workers'] <= 0 && !isset($order['me'])) continue;
        if($order['speed'] <= 0 && !isset($order['me'])) continue;

        $service_btcmhd = mbitcoinvaluetoa($order['price']);
        $hash = Itoa2($order['speed']*1000000000).'h/s';
        $limit = Itoa2($order['limit']*1000000000).'h/s';
        $btc = round($order['btc']*1000, 1);
        $profit = $price > $service_btcmhd ? round(($price-$service_btcmhd)/$service_btcmhd*100).'%' : '';

        show_services($services, $service_btcmhd);

        $bg = isset($order['me']) ? ' class="table-success"' : '';

        echo '<tr'.$bg.'>';
        echo '  <td class="ps-4"></td>';
        echo '  <td class="fw-bold">'.$hash.' <small class="text-muted">('.$order['workers'].' workers)</small></td>';
        echo '  <td class="text-end small">'.$limit.'</td>';
        echo '  <td class="text-end small">'.$btc.'</td>';
        echo '  <td class="text-end small fw-bold">'.$profit.'</td>';
        echo '  <td colspan="2"></td>';
        echo '  <td class="text-end pe-4 fw-bold">'.$service_btcmhd.'</td>';
        echo '</tr>';

        unset($allorders[$i]);
    }
}
