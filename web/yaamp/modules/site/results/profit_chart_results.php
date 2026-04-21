<?php

/* Per-algo profitability bar chart data for jqplot.
 * Returns JSON: [[label, mBTC/MH/day, estimated], ...]  sorted descending.
 * Prefers actual 24h block data; falls back to hashrate.price estimate.
 */

$t24 = time() - 24 * 3600;
$algos = yaamp_get_algos();
$data = [];

foreach ($algos as $algo) {
    $factor = yaamp_algo_mBTC_factor($algo);
    $mbtc_mhd = 0;
    $estimated = false;

    $hashrate = controller()->memcache->get_database_scalar("profit_chart_hash-$algo",
        "SELECT AVG(hashrate) FROM hashrate WHERE time > $t24 AND algo=:algo", array(':algo' => $algo));

    if ($hashrate > 0) {
        $earnings = controller()->memcache->get_database_scalar("profit_chart_earn-$algo",
            "SELECT SUM(amount * price) FROM blocks WHERE time > $t24 AND algo=:algo AND category NOT IN ('orphan','stake','generated')",
            array(':algo' => $algo));

        if ($earnings > 0) {
            $mbtc_mhd = ($earnings / $hashrate) * 1000000 * 1000 * $factor;
            $mbtc_mhd = round(take_yaamp_fee($mbtc_mhd, $algo), 4);
        }
    }

    // Fallback: use hashrate.price (exchange-rate estimate, already in mBTC/MH/day)
    // The API does $price/1000 to get BTC/MH/day, so $price itself is mBTC/MH/day
    if ($mbtc_mhd <= 0) {
        $price = controller()->memcache->get_database_scalar("profit_chart_price-$algo",
            "SELECT price FROM hashrate WHERE algo=:algo ORDER BY time DESC LIMIT 1", array(':algo' => $algo));
        if ($price > 0) {
            $mbtc_mhd = round(take_yaamp_fee($price, $algo), 4);
            $estimated = true;
        }
    }

    if ($mbtc_mhd <= 0) continue;

    $data[] = [strtoupper($algo), $mbtc_mhd, $estimated];
}

// Sort descending by profitability
usort($data, function($a, $b) { return $a[1] < $b[1] ? 1 : -1; });

header('Content-Type: application/json');
echo json_encode($data);
