<div class="container-fluid py-4">

    <!-- --- Header Card --- -->
    <div class="card shadow-lg border-0 mb-4 bg-dark text-white rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="card-body p-4 d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4 shadow-sm border border-primary border-opacity-25">
                <i class="fa fa-random fa-2x text-primary"></i>
            </div>
            <div class="flex-grow-1">
                <h2 class="mb-0 fw-bold">Multi-Algo Switching</h2>
                <p class="text-muted small mb-0 mt-1">Achieve maximum profitability by automatically switching to the most rewarding algorithm.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- --- How it Works --- -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-info-circle me-2 text-primary"></i>How to Configure</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <p class="text-muted small">Define a set of algorithms in your password parameter. The pool will automatically close your connection if a more profitable algorithm becomes available in your set.</p>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="small fw-bold text-muted text-uppercase mb-2">Example: Multi-Set</div>
                            <pre class="bg-dark text-success p-3 rounded-3 small font-monospace">-p x11,neoscrypt,lyra2</pre>
                        </div>
                        <div class="col-md-6">
                            <div class="small fw-bold text-muted text-uppercase mb-2">Example: Scrypt Set</div>
                            <pre class="bg-dark text-success p-3 rounded-3 small font-monospace">-p scrypt,scryptn</pre>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark small text-uppercase">Combining Parameters</h6>
                    <p class="text-muted small">You can combine switching with custom difficulty or worker names. <b>No spaces allowed.</b></p>
                    <div class="bg-light p-3 rounded-3 border mb-4">
                        <code class="text-primary fw-bold">-p d=64,scrypt,scryptn</code><br>
                        <code class="text-primary fw-bold">-p rig1,x11,x13,x15</code>
                    </div>

                    <h6 class="fw-bold text-dark mb-2">Windows Batch Example (ccminer)</h6>
                    <pre class="bg-dark text-info p-3 rounded-3 small font-monospace border border-secondary border-opacity-25" style="white-space: pre-wrap;">
:start
ccminer -r 0 -a x11 -o stratum+tcp://<?= YAAMP_STRATUM_URL ?>:3533 -u WALLET -p x11,x13,x15
ccminer -r 0 -a x13 -o stratum+tcp://<?= YAAMP_STRATUM_URL ?>:3633 -u WALLET -p x11,x13,x15
ccminer -r 0 -a x15 -o stratum+tcp://<?= YAAMP_STRATUM_URL ?>:3733 -u WALLET -p x11,x13,x15
goto start</pre>
                </div>
            </div>
        </div>

        <!-- --- Normalization Factors --- -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-dark text-white py-3 border-0">
                    <h5 class="mb-0 fw-bold small text-uppercase" style="letter-spacing: 1px;">Profitability Factors</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.65rem;">
                                <tr><th class="ps-4">Algorithm</th><th class="text-end pe-4">Base Factor</th></tr>
                            </thead>
                            <tbody class="small font-monospace">
                                <tr><td class="ps-4">Scrypt (Reference)</td><td class="text-end pe-4 fw-bold text-primary">1.0</td></tr>
                                <tr><td class="ps-4">X11</td><td class="text-end pe-4 fw-bold text-primary">5.5</td></tr>
                                <tr><td class="ps-4">Nist5</td><td class="text-end pe-4 fw-bold text-primary">6.0</td></tr>
                                <tr><td class="ps-4">Neoscrypt</td><td class="text-end pe-4 fw-bold text-primary">0.3</td></tr>
                                <tr><td class="ps-4">Quark</td><td class="text-end pe-4 fw-bold text-primary">6.0</td></tr>
                                <tr><td class="ps-4">Lyra2</td><td class="text-end pe-4 fw-bold text-primary">1.3</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 py-3">
                    <p class="small text-muted mb-0"><i class="fa fa-sliders-h me-2 text-primary"></i><b>Custom Factors:</b> Override defaults using <code>algo=value</code> in the password.</p>
                    <code class="small text-primary fw-bold mt-1 d-block">-p x11=5.1,lyra2=2</code>
                </div>
            </div>
        </div>
    </div>

</div>