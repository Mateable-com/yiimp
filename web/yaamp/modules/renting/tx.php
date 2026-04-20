<?php

require dirname(__FILE__).'/../../ui/lib/pageheader.php';

$renter = getrenterparam(''.getparam('address'));
if(!$renter) return;

$this->pageTitle = "$renter->address | yiimp";

$btc = getdbosql('db_coins', "symbol='BTC'");
if(!$btc) return;

$remote = new WalletRPC($btc);
$ts = $remote->listtransactions(yaamp_renter_account($renter), 10);

$res_array = array();
foreach($ts as $val)
{
    $t = $val['time'];
    if($t < $renter->created) continue;
    $res_array[$t] = $val;
}

krsort($res_array);
$total = 0;

?>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-dark text-white py-3 px-4 border-0">
        <h5 class="mb-0 fw-bold"><i class="fa fa-exchange-alt me-2 text-warning"></i>Transactions</h5>
        <p class="small text-white-50 mb-0 mt-1 font-monospace"><?= htmlspecialchars($renter->address) ?></p>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">
                    <tr>
                        <th class="ps-4">Time</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Confirmations</th>
                        <th>Transaction ID</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($res_array as $transaction): ?>
                    <?php if($transaction['category'] != 'receive') continue; ?>
                    <?php
                        $d = datetoa2($transaction['time']);
                        $total += $transaction['amount'];
                    ?>
                    <tr>
                        <td class="ps-4 text-muted"><?= $d ?> ago</td>
                        <td class="text-end fw-bold text-success"><?= htmlspecialchars($transaction['amount']) ?> BTC</td>
                        <td class="text-center">
                            <?php if(isset($transaction['confirmations'])): ?>
                                <?php $conf = (int)$transaction['confirmations']; ?>
                                <span class="badge <?= $conf >= 6 ? 'bg-success' : 'bg-warning text-dark' ?> bg-opacity-10 border <?= $conf >= 6 ? 'border-success text-success' : 'border-warning text-warning' ?> border-opacity-25">
                                    <?= $conf ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="font-monospace small text-truncate" style="max-width: 420px;">
                            <?php if(isset($transaction['txid'])): ?>
                                <a href="https://blockchain.info/tx/<?= htmlspecialchars($transaction['txid']) ?>" target="_blank" class="text-decoration-none text-primary">
                                    <?= htmlspecialchars($transaction['txid']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-4">Total Received</td>
                        <td class="text-end text-success"><?= htmlspecialchars($total) ?> BTC</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
