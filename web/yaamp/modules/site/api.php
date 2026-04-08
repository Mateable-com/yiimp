<div class="container-fluid py-4">

    <!-- --- Developer Hub Header --- -->
    <div class="card shadow-lg border-0 mb-5 bg-dark text-white rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="card-body p-5 d-flex align-items-center">
            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-4 shadow-sm border border-success border-opacity-25">
                <i class="fa fa-code fa-2x text-success"></i>
            </div>
            <div class="flex-grow-1">
                <h2 class="mb-0 fw-bold">Developer Portal</h2>
                <p class="text-muted small mb-0 mt-1">Simple REST API for wallet status, pool metrics, and automated rental management.</p>
            </div>
            <div class="ms-auto d-none d-md-block">
                <div class="badge bg-success px-3 py-2 rounded-pill fw-bold">API v1.0 ACTIVE</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- --- Endpoints Column --- -->
        <div class="col-lg-8">
            
            <!-- Endpoint: Wallet Status -->
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-wallet me-2 text-primary"></i>Wallet & Miner Status</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <p class="small text-muted">Retrieve unsold balances, total paid, and active worker details for any address.</p>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary mb-2">GET</span>
                        <code class="d-block p-3 bg-light rounded-3 border fw-bold text-primary mb-3">http://<?=YAAMP_API_URL?>/api/wallet?address=WALLET_ADDRESS</code>
                    </div>

                    <div class="bg-dark p-3 rounded-3 shadow-sm border border-secondary border-opacity-25">
                        <div class="text-muted small text-uppercase fw-bold mb-2 opacity-50">Example JSON Response:</div>
                        <pre class="text-success mb-0 small">
{
  "unsold": 0.00050362,
  "balance": 0.00000000,
  "unpaid": 0.00050362,
  "paid24h": 0.00000000,
  "total": 0.00050362
}</pre>
                    </div>
                </div>
            </div>

            <!-- Endpoint: Pool Status -->
            <div class="card shadow-sm border-0 mb-4 rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-chart-bar me-2 text-info"></i>Pool Performance</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <p class="small text-muted">Get real-time hashrate, worker counts, and estimates for all active algorithms.</p>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary mb-2">GET</span>
                        <code class="d-block p-3 bg-light rounded-3 border fw-bold text-primary mb-3">http://<?=YAAMP_API_URL?>/api/status</code>
                    </div>

                    <div class="bg-dark p-3 rounded-3 shadow-sm border border-secondary border-opacity-25">
                        <div class="text-muted small text-uppercase fw-bold mb-2 opacity-50">Response Object:</div>
                        <pre class="text-info mb-0 small">
"x11": {
  "name": "x11",
  "port": 3533,
  "coins": 10,
  "hashrate": 269473938,
  "workers": 5,
  "estimate_current": "0.00053653",
  "actual_last24h": "0.00035620"
}</pre>
                    </div>
                </div>
            </div>

            <?php if (YAAMP_RENTAL) : ?>
            <!-- Endpoint: Rental Control -->
            <div class="card shadow-sm border-0 mb-4 rounded-4 border-start border-4 border-warning">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-server me-2 text-warning"></i>Rental Management</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <p class="small text-muted">Programmatic control over your hashing power rental jobs.</p>
                    <div class="list-group list-group-flush small">
                        <div class="list-group-item px-0 border-0 mb-2">
                            <span class="badge bg-primary me-2">GET</span> <code>/api/rental_price?key=API_KEY&jobid=xx&price=xx</code>
                        </div>
                        <div class="list-group-item px-0 border-0 mb-2">
                            <span class="badge bg-primary me-2">GET</span> <code>/api/rental_start?key=API_KEY&jobid=xx</code>
                        </div>
                        <div class="list-group-item px-0 border-0">
                            <span class="badge bg-primary me-2">GET</span> <code>/api/rental_stop?key=API_KEY&jobid=xx</code>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- --- Sidebar Info --- -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 bg-primary bg-opacity-10 text-primary mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-uppercase small mb-3">API Guidelines</h6>
                    <ul class="small mb-0 ps-3">
                        <li class="mb-2">Rate limit: 1 request every 5 seconds.</li>
                        <li class="mb-2">Data format: Application/JSON.</li>
                        <li class="mb-2">Encoding: UTF-8.</li>
                        <li>Case sensitivity: Values are case-sensitive.</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4 text-center">
                    <i class="fa fa-question-circle fa-2x text-muted opacity-25 mb-3"></i>
                    <h6 class="fw-bold">Need Help?</h6>
                    <p class="text-muted small">For advanced API support or private endpoints, contact our technical team.</p>
                    <a href="mailto:<?=YAAMP_ADMIN_EMAIL?>" class="btn btn-outline-primary btn-sm rounded-pill px-4">Contact Admin</a>
                </div>
            </div>
        </div>
    </div>

</div>