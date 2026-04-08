<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 border-0 text-center">
                    <h5 class="mb-0 fw-bold"><i class="fa fa-code me-2 text-primary"></i>Internal Debug Evaluator</h5>
                </div>
                <div class="card-body p-5">
                    <form action="/site/eval" method="get">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Evaluation Parameter</label>
                            <input type="text" name="param" class="form-control form-control-lg border-2 bg-light px-4 font-monospace" value="<?=$param?>" placeholder="Enter command or parameter...">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill shadow-sm">EXECUTE EVALUATION</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light py-3 text-center small text-muted">
                    <i class="fa fa-shield-alt me-1"></i> Authorized Administrative Access Only
                </div>
            </div>
        </div>
    </div>
</div>
