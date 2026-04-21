<?php

$default_fee      = (float) settings_get('fees_mining', YAAMP_FEES_MINING);
$default_fee_solo = (float) settings_get('fees_solo', YAAMP_FEES_SOLO);
$algos = yaamp_get_algo_list();

global $configFixedPoolFees, $configFixedPoolFeesSolo;
?>

<div class="container py-4" style="max-width: 900px;">

    <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-3 px-4">
            <h4 class="mb-0 fw-bold"><i class="fa fa-percentage me-2 text-warning"></i>Pool Fee Schedule</h4>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-4">All fees are deducted from block rewards before distribution. There are no hidden charges — what you see below is what the pool takes.</p>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 bg-success bg-opacity-10 rounded-3 h-100">
                        <div class="card-body text-center p-3">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Default Shared Mining</div>
                            <div class="display-6 fw-bold text-success"><?=number_format($default_fee, 1)?>%</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-info bg-opacity-10 rounded-3 h-100">
                        <div class="card-body text-center p-3">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Default Solo Mining</div>
                            <div class="display-6 fw-bold text-info"><?=number_format($default_fee_solo, 1)?>%</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-warning bg-opacity-10 rounded-3 h-100">
                        <div class="card-body text-center p-3">
                            <div class="text-muted small fw-bold text-uppercase mb-1">Min Payout</div>
                            <div class="display-6 fw-bold text-warning"><?=YAAMP_PAYMENTS_MINI?></div>
                            <div class="text-muted small">per coin</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">
                        <tr>
                            <th class="ps-4">Algorithm</th>
                            <th class="text-center">Shared Fee</th>
                            <th class="text-center">Solo Fee</th>
                            <th class="text-end pe-4">Note</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($algos as $a):
                        $algo = $a['name'];
                        $fee      = yaamp_fee($algo);
                        $fee_solo = yaamp_fee_solo($algo);
                        $custom   = isset($configFixedPoolFees[$algo]) || isset($configFixedPoolFeesSolo[$algo]);
                        $algo_color = getAlgoColors($algo);
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold" style="border-left: 4px solid <?=htmlspecialchars($algo_color)?>">
                            <?=htmlspecialchars(strtoupper($algo))?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">
                                <?=number_format($fee, 1)?>%
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3">
                                <?=number_format($fee_solo, 1)?>%
                            </span>
                        </td>
                        <td class="text-end pe-4 text-muted small">
                            <?= $custom ? '<span class="badge bg-warning text-dark">Custom</span>' : '<span class="text-muted">Default</span>' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="fa fa-info-circle me-2 text-primary"></i>How Fees Work</h5>
            <ul class="text-muted small lh-lg mb-0">
                <li><strong>Shared mining</strong> — fee is deducted from each block reward before splitting between miners proportionally to their contributed hashrate.</li>
                <li><strong>Solo mining</strong> — fee is deducted from your block reward when you find a block alone. Add <code>m=solo</code> to your password field.</li>
                <li><strong>Payouts</strong> — processed automatically every <?=round(YAAMP_PAYMENTS_FREQ/3600)?> hours to your wallet address.</li>
                <li><strong>Transaction fees</strong> — network transaction fees are covered by the pool and not charged to miners.</li>
            </ul>
        </div>
    </div>

</div>
