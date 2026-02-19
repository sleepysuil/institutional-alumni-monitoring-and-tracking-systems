<?php
require_once '../includes/admin_header.php';
?>
<div class="page-header">
    <h1>Anomaly Detection</h1>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <h5 class="card-title">High Priority</h5>
                <p class="display-4">0</p>
                <p>Require immediate attention</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Medium Priority</h5>
                <p class="display-4">0</p>
                <p>Monitor closely</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Low Priority</h5>
                <p class="display-4">0</p>
                <p>Positive outliers</p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">No anomalies detected at this time.</div>

<?php include '../includes/footer.php'; ?>