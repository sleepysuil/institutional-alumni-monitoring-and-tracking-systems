<?php
require_once '../includes/admin_header.php';
?>
<div class="page-header">
    <h1>Data Mining & Analytics</h1>
    <p class="text-muted">Advanced pattern discovery and predictive analytics</p>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <a href="clustering.php" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-project-diagram fa-3x mb-3" style="color: #8B5CF6;"></i>
                <h5>Clustering</h5>
                <p class="small text-muted">K-Means alumni segmentation</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="association.php" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-link fa-3x mb-3" style="color: #8B5CF6;"></i>
                <h5>Association</h5>
                <p class="small text-muted">Program-industry rules</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="prediction.php" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x mb-3" style="color: #8B5CF6;"></i>
                <h5>Prediction</h5>
                <p class="small text-muted">Employment outcome forecast</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="patterns.php" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-sitemap fa-3x mb-3" style="color: #8B5CF6;"></i>
                <h5>Patterns</h5>
                <p class="small text-muted">Career path discovery</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="anomalies.php" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x mb-3" style="color: #8B5CF6;"></i>
                <h5>Anomalies</h5>
                <p class="small text-muted">Outlier detection</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="trends.php" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-chart-bar fa-3x mb-3" style="color: #8B5CF6;"></i>
                <h5>Trends</h5>
                <p class="small text-muted">Employment rate trends</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="skills_gap.php" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-tools fa-3x mb-3" style="color: #8B5CF6;"></i>
                <h5>Skills Gap</h5>
                <p class="small text-muted">Curriculum improvement insights</p>
            </div>
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>