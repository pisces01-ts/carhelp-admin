<?php
function getPaginationData($conn, $table, $where = '', $perPage = 20) {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    
    $countSql = "SELECT COUNT(*) as total FROM {$table}";
    if ($where) $countSql .= " WHERE {$where}";
    
    $countResult = $conn->query($countSql);
    $totalRows = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $perPage);
    
    $offset = ($page - 1) * $perPage;
    
    return [
        'current_page' => $page,
        'per_page' => $perPage,
        'total_rows' => $totalRows,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'limit' => "LIMIT {$offset}, {$perPage}"
    ];
}

function renderPagination($pagination, $baseUrl = '') {
    if ($pagination['total_pages'] <= 1) return '';
    
    $currentPage = $pagination['current_page'];
    $totalPages = $pagination['total_pages'];
    
    if (empty($baseUrl)) {
        $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
        $params = $_GET;
        unset($params['page']);
        if (!empty($params)) {
            $baseUrl .= '?' . http_build_query($params) . '&';
        } else {
            $baseUrl .= '?';
        }
    }
    
    $html = '<nav class="d-flex justify-content-between align-items-center p-3 border-top">';
    $html .= '<div class="text-muted small">แสดง ' . number_format($pagination['total_rows']) . ' รายการ</div>';
    $html .= '<ul class="pagination pagination-sm mb-0">';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . ($currentPage - 1) . '">&laquo;</a></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . 'page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . ($currentPage + 1) . '">&raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}
