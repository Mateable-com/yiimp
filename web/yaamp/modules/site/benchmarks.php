<div class="container-fluid py-4">

    <!-- --- Header Card --- -->
    <div class="card shadow-lg border-0 mb-4 bg-dark text-white rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="card-body p-4 d-flex align-items-center">
            <div class="bg-info bg-opacity-10 p-3 rounded-circle me-4 shadow-sm border border-info border-opacity-25">
                <i class="fa fa-stopwatch fa-2x text-info"></i>
            </div>
            <div class="flex-grow-1">
                <h2 class="mb-0 fw-bold">Mining Benchmarks</h2>
                <p class="text-muted small mb-0 mt-1">Global performance database for various hardware and mining clients.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- --- How to Benchmark --- -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-info-circle me-2 text-primary"></i>How to contribute data</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <p class="text-muted">Currently, <?=YAAMP_SITE_NAME?> supports hashrate sharing from <b>ccminer (1.7.6+)</b>. To submit your device stats, simply add <code>stats</code> to your password parameter:</p>
                    
                    <div class="position-relative mb-4">
                        <pre class="bg-dark text-success p-3 rounded-3 small shadow-sm font-monospace border border-secondary border-opacity-25" style="white-space: pre-wrap; word-break: break-all;">
-o stratum+tcp://<?= YAAMP_STRATUM_URL ?>:&lt;PORT&gt; -a &lt;algo&gt; -u &lt;address&gt; -p stats</pre>
                    </div>

                    <div class="list-group list-group-flush small">
                        <div class="list-group-item px-0 border-0 bg-transparent text-muted mb-2">
                            <i class="fa fa-check text-success me-2"></i> The stratum will automatically request stats every 50 shares (max 4 times).
                        </div>
                        <div class="list-group-item px-0 border-0 bg-transparent text-muted mb-2">
                            <i class="fa fa-check text-success me-2"></i> Can be combined with custom difficulty: <code>-p stats,d=64</code>.
                        </div>
                        <div class="list-group-item px-0 border-0 bg-transparent text-muted">
                            <i class="fa fa-check text-success me-2"></i> Use the generic address <b>'benchmark'</b> to test without earning rewards.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- --- Hardware Notes & Downloads --- -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-microchip me-2 text-warning"></i>Hardware Notes</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="alert alert-warning border-0 shadow-sm small py-2">
                        <i class="fa fa-exclamation-triangle me-2"></i>Only the <b>first device stats</b> will be submitted on multi-GPU systems.
                    </div>
                    <p class="small text-muted">To monitor a specific card, use the <code>--device</code> parameter (e.g. <code>-d 1</code>).</p>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-download me-2 text-success"></i>Compatible Software</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="list-group list-group-flush small">
                        <a href="https://github.com/tpruvot/ccminer/releases" target="_blank" class="list-group-item list-group-item-action border-0 px-0 py-2 d-flex justify-content-between align-items-center">
                            <span><i class="fab fa-github me-2"></i>ccminer (tpruvot)</span>
                            <i class="fa fa-external-link-alt opacity-25"></i>
                        </a>
                        <a href="https://github.com/KlausT/ccminer/releases" target="_blank" class="list-group-item list-group-item-action border-0 px-0 py-2 d-flex justify-content-between align-items-center">
                            <span><i class="fab fa-github me-2"></i>ccminer (KlausT)</span>
                            <i class="fa fa-external-link-alt opacity-25"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>