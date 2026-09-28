<?php
/**
 * Reusable pagination helper.
 * Usage:
 *   require_once '../includes/pagination.php';
 *   $page = getCurrentPage();
 *   $total = $pdo->query($countSql)->fetchColumn();
 *   $pager = new Paginator($total, $page, 10);
 *   // use $pager->limit() / $pager->offset() in your LIMIT/OFFSET
 *   echo $pager->render(); // Bootstrap-style nav, preserves other GET params
 */

function getCurrentPage() {
    $page = (int)($_GET['page'] ?? 1);
    return $page < 1 ? 1 : $page;
}

class Paginator {
    public $total;
    public $page;
    public $perPage;
    public $totalPages;

    public function __construct($total, $page, $perPage = 10) {
        $this->total = (int)$total;
        $this->perPage = max(1, (int)$perPage);
        $this->totalPages = max(1, (int)ceil($this->total / $this->perPage));
        $this->page = min(max(1, (int)$page), $this->totalPages);
    }

    public function limit() { return $this->perPage; }
    public function offset() { return ($this->page - 1) * $this->perPage; }
    public function rangeStart() { return $this->total == 0 ? 0 : $this->offset() + 1; }
    public function rangeEnd() { return min($this->offset() + $this->perPage, $this->total); }

    public function render() {
        $params = $_GET;
        $buildUrl = function($p) use ($params) {
            $params['page'] = $p;
            return '?' . http_build_query($params);
        };
        ob_start();
        ?>
        <div class="pagination-wrapper d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
            <div class="text-muted small">
                <?php if ($this->total > 0): ?>
                    Showing <?= $this->rangeStart() ?>&ndash;<?= $this->rangeEnd() ?> of <?= $this->total ?>
                <?php else: ?>
                    No records found
                <?php endif; ?>
            </div>
            <?php if ($this->totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $this->page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $this->page <= 1 ? '#' : $buildUrl($this->page - 1) ?>" aria-label="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php foreach ($this->pageNumbers() as $p): ?>
                        <?php if ($p === '...'): ?>
                            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                        <?php else: ?>
                            <li class="page-item <?= $p == $this->page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $buildUrl($p) ?>"><?= $p ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li class="page-item <?= $this->page >= $this->totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $this->page >= $this->totalPages ? '#' : $buildUrl($this->page + 1) ?>" aria-label="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function pageNumbers() {
        $pages = [];
        $total = $this->totalPages;
        $current = $this->page;
        for ($i = 1; $i <= $total; $i++) {
            if ($i == 1 || $i == $total || ($i >= $current - 2 && $i <= $current + 2)) {
                $pages[] = $i;
            }
        }
        $result = [];
        $prev = null;
        foreach ($pages as $p) {
            if ($prev !== null && $p - $prev > 1) $result[] = '...';
            $result[] = $p;
            $prev = $p;
        }
        return $result;
    }
}