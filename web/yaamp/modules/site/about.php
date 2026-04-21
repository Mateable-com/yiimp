<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-dark text-white fw-bold py-3">
                <i class="fa fa-info-circle me-2"></i>About <?=settings_get('site_name', YAAMP_SITE_NAME)?>
            </div>
            <div class="card-body p-4">
                <h2 class="h4 mb-4 border-bottom pb-2 text-primary">Welcome to <?=settings_get('site_name', YAAMP_SITE_NAME)?></h2>

                <div class="text-secondary lh-lg">
                    <p><?=settings_get('site_name', YAAMP_SITE_NAME)?> is a high-performance multi-algorithm cryptocurrency mining pool. We support a wide range of proof-of-work algorithms with automated payouts, no registration required — just point your miner at our stratum and start earning.</p>

                    <h5 class="text-dark mt-4 mb-3">Key Features</h5>
                    <ul class="mb-4">
                        <li class="mb-2"><strong>Anonymous mining</strong> — no account needed, just use your wallet address</li>
                        <li class="mb-2"><strong>Multi-algorithm</strong> — Scrypt, X11, SHA-256, Equihash, and many more</li>
                        <li class="mb-2"><strong>Shared &amp; Solo modes</strong> — mine in the pool or go solo with <code>m=solo</code></li>
                        <li class="mb-2"><strong>Merged mining</strong> — earn multiple coins simultaneously with AuxPoW support</li>
                        <li class="mb-2"><strong>Automated payouts</strong> — paid directly to your wallet every <?=round(YAAMP_PAYMENTS_FREQ/3600)?> hours</li>
                        <li class="mb-2"><strong>Low fees</strong> — just <?=YAAMP_FEES_MINING?>% for shared mining</li>
                        <li class="mb-2"><strong>Real-time stats</strong> — live hashrate, worker monitoring, and earnings tracking</li>
                    </ul>

                    <h5 class="text-dark mt-4 mb-3">How It Works</h5>
                    <ol class="mb-4">
                        <li class="mb-2">Choose a coin or algorithm from the <a href="/site/mining">Pool Stats</a> page</li>
                        <li class="mb-2">Use the <a href="/#mine-now">Configuration Generator</a> on the home page to get your connection string</li>
                        <li class="mb-2">Point your miner at our stratum — your wallet address is your username</li>
                        <li class="mb-2">Track your earnings and workers at <code><?=YAAMP_SITE_URL?>/?address=YOUR_WALLET</code></li>
                    </ol>

                    <h5 class="text-dark mt-4 mb-3">Payout Policy</h5>
                    <p>Minimum payout is <strong><?=YAAMP_PAYMENTS_MINI?></strong> (in coin units) per coin. Payouts are processed automatically every <?=round(YAAMP_PAYMENTS_FREQ/3600)?> hours. On Sunday evenings the minimum is reduced to help clear small balances. You can set a custom minimum threshold from your wallet page.</p>
                </div>

                <div class="mt-5 pt-3 border-top text-muted small">
                    <p class="mb-0">Questions? Reach us at <a href="mailto:<?=YAAMP_ADMIN_EMAIL?>"><?=YAAMP_ADMIN_EMAIL?></a></p>
                    <p class="fw-bold text-dark mb-0">— The <?=settings_get('site_name', YAAMP_SITE_NAME)?> Team</p>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-warning mb-5">
            <div class="card-header bg-warning text-dark fw-bold">
                <i class="fa fa-headset me-2"></i>Contact & Support
            </div>
            <div class="card-body text-center p-4">
                <p>Have questions or need help? Join our community!</p>
                <a href="https://discord.gg/DrsrWQh3qC" class="btn btn-primary px-5 py-2 shadow-sm">
                    <i class="fab fa-discord me-2"></i>Join our Discord
                </a>
            </div>
        </div>
    </div>
</div>
