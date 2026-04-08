<div class="container-fluid py-4">

    <!-- --- Header Card --- -->
    <div class="card shadow-lg border-0 mb-4 bg-dark text-white rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="card-body p-4 d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4 shadow-sm border border-primary border-opacity-25">
                <i class="fa fa-balance-scale fa-2x text-primary"></i>
            </div>
            <div class="flex-grow-1">
                <h2 class="mb-0 fw-bold">Stratum Difficulty Guide</h2>
                <p class="text-muted small mb-0 mt-1">Understanding automatic and custom difficulty settings for optimal mining efficiency.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- --- Automatic Difficulty --- -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-robot me-2 text-primary"></i>Automatic Adjustment</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <p class="text-muted">By default, <?=YAAMP_SITE_NAME?> will adjust the difficulty of your miner automatically over time until you have from <b>5 to 15 submits per minute</b>.</p>
                    <div class="alert alert-info border-0 shadow-sm small py-2 mb-0">
                        <i class="fa fa-info-circle me-2"></i>This is the recommended setting as it provides a perfect trade-off between bandwidth usage and share accuracy.
                    </div>
                </div>
            </div>
        </div>

        <!-- --- Custom Difficulty --- -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-sliders-h me-2 text-success"></i>Fixed Custom Difficulty</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <p class="text-muted">You can set a fixed difficulty using the <code>d=</code> password parameter in your miner command line.</p>
                    <div class="position-relative">
                        <pre class="bg-dark text-success p-3 rounded-3 small shadow-sm font-monospace border border-secondary border-opacity-25" style="white-space: pre-wrap; word-break: break-all;">
-o stratum+tcp://<?= YAAMP_STRATUM_URL ?>:3433 -u WALLET -p d=64</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- --- Accepted Values Grid --- -->
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 border-0">
                    <h5 class="mb-0 fw-bold small text-uppercase" style="letter-spacing: 1px;">Accepted Value Ranges per Algorithm</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="ps-4">Algorithm Group</th>
                                    <th class="text-end pe-4">Difficulty Range</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td class="ps-4 fw-bold">Scrypt, Scrypt-N, Neoscrypt</td><td class="text-end pe-4 font-monospace fw-bold text-primary">2.0 to 65536.0</td></tr>
                                <tr><td class="ps-4 fw-bold">X11, X13, X14, X15</td><td class="text-end pe-4 font-monospace fw-bold text-primary">0.002 to 0.512</td></tr>
                                <tr><td class="ps-4 fw-bold">Lyra2RE, Lyra2v2, Lyra2v3</td><td class="text-end pe-4 font-monospace fw-bold text-primary">0.01 to 2048.0</td></tr>
                                <tr><td class="ps-4 fw-bold">SHA-256, Blake2s</td><td class="text-end pe-4 font-monospace fw-bold text-primary">16.0 to 1048576.0</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 py-3">
                    <div class="small text-muted"><i class="fa fa-exclamation-triangle me-2 text-warning"></i><b>Note:</b> If you set a difficulty higher than the minimum required by any active coin in the pool, the system will automatically force it down to match the lowest required difficulty.</div>
                </div>
            </div>
        </div>
    </div>

</div>