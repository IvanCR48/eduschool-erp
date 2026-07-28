<?php
namespace SistemaAdmin\Services;

use SistemaAdmin\Contracts\DatabaseInterface;

/**
 * Servicio de Paginación para optimizar listados grandes
 */
class PaginationService extends BaseService
{
    private int $defaultPageSize = 20;
    private int $maxPageSize = 100;
    private int $defaultPage = 1;

    public function __construct(DatabaseInterface $database)
    {
        parent::__construct($database);
    }

    /**
     * Calcular información de paginación
     */
    public function calculatePagination(int $totalItems, int $currentPage = null, int $pageSize = null): array
    {
        $currentPage = $currentPage ?? $this->defaultPage;
        $pageSize = $pageSize ?? $this->defaultPageSize;
        
        // Validar parámetros
        $currentPage = max(1, $currentPage);
        $pageSize = min(max(1, $pageSize), $this->maxPageSize);
        
        $totalPages = ceil($totalItems / $pageSize);
        $currentPage = min($currentPage, max(1, $totalPages));
        
        $offset = ($currentPage - 1) * $pageSize;
        
        return [
            'current_page' => $currentPage,
            'page_size' => $pageSize,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'has_previous' => $currentPage > 1,
            'has_next' =>$currentPage< $totalPages,
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
            'start_item' => $totalItems > 0 ? $offset + 1 : 0,
            'end_item' => min($offset + $pageSize, $totalItems)
        ];
    }

    /**
     * Generar SQL LIMIT y OFFSET
     */
    public function getSqlLimit(int $currentPage = null, int $pageSize = null): string
    {
        $pagination = $this->calculatePagination(0, $currentPage, $pageSize);
        return "LIMIT {$pagination['page_size']} OFFSET {$pagination['offset']}";
    }

    /**
     * Obtener páginas para mostrar en navegación
     */
    public function getPageNumbers(int $totalPages, int $currentPage, int $maxVisible = 5): array
    {
        if ($totalPages <= $maxVisible) {
            return range(1, $totalPages);
        }

        $half = floor($maxVisible / 2);
        $start = max(1, $currentPage - $half);
        $end = min($totalPages, $start + $maxVisible - 1);

        // Ajustar si estamos cerca del final
        if ($end - $start + 1 < $maxVisible) {
            $start = max(1, $end - $maxVisible + 1);
        }

        return range($start, $end);
    }

    /**
     * Generar HTML de paginación
     */
    public function generatePaginationHtml(array $pagination, string $baseUrl, array $params = []): string
    {
        if ($pagination['total_pages'] <= 1) {
            return '';
        }

        $html = '<nav class="pagination-nav" aria-label="Paginación">';
        $html .= '<ul class="pagination">';

        // Botón anterior
        if ($pagination['has_previous']) {
            $prevUrl = $this->buildUrl($baseUrl, array_merge($params, ['page' => $pagination['previous_page']]));
            $html .= '<li class="page-item">';
            $html .= '<a href="' . htmlspecialchars($prevUrl) . '" class="page-link" aria-label="Página anterior">';
            $html .= '<i class="fas fa-chevron-left"></i>';
            $html .= '</a></li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link" aria-label="Página anterior">';
            $html .= '<i class="fas fa-chevron-left"></i>';
            $html .= '</span></li>';
        }

        // Números de página
        $pageNumbers = $this->getPageNumbers($pagination['total_pages'], $pagination['current_page']);
        
        foreach ($pageNumbers as $page) {
            if ($page == $pagination['current_page']) {
                $html .= '<li class="page-item active">';
                $html .= '<span class="page-link">' . $page . '</span>';
                $html .= '</li>';
            } else {
                $pageUrl = $this->buildUrl($baseUrl, array_merge($params, ['page' => $page]));
                $html .= '<li class="page-item">';
                $html .= '<a href="' . htmlspecialchars($pageUrl) . '" class="page-link">' . $page . '</a>';
                $html .= '</li>';
            }
        }

        // Botón siguiente
        if ($pagination['has_next']) {
            $nextUrl = $this->buildUrl($baseUrl, array_merge($params, ['page' => $pagination['next_page']]));
            $html .= '<li class="page-item">';
            $html .= '<a href="' . htmlspecialchars($nextUrl) . '" class="page-link" aria-label="Página siguiente">';
            $html .= '<i class="fas fa-chevron-right"></i>';
            $html .= '</a></li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link" aria-label="Página siguiente">';
            $html .= '<i class="fas fa-chevron-right"></i>';
            $html .= '</span></li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Construir URL con parámetros
     */
    private function buildUrl(string $baseUrl, array $params): string
    {
        $url = $baseUrl;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    /**
     * Obtener información de resumen
     */
    public function getSummaryText(array $pagination): string
    {
        if ($pagination['total_items'] == 0) {
            return 'No hay elementos para mostrar';
        }

        $start = $pagination['start_item'];
        $end = $pagination['end_item'];
        $total = $pagination['total_items'];

        return "Mostrando {$start} a {$end} de {$total} elementos";
    }

    /**
     * Validar parámetros de paginación desde request
     */
    public function validateRequestParams(array $request): array
    {
        $page = isset($request['page']) ? (int)$request['page'] : $this->defaultPage;
        $pageSize = isset($request['page_size']) ? (int)$request['page_size'] : $this->defaultPageSize;
        
        return [
            'page' => max(1, $page),
            'page_size' => min(max(1, $pageSize), $this->maxPageSize)
        ];
    }

    /**
     * Configurar tamaño de página por defecto
     */
    public function setDefaultPageSize(int $size): void
    {
        $this->defaultPageSize = max(1, min($size, $this->maxPageSize));
    }

    /**
     * Configurar página máxima
     */
    public function setMaxPageSize(int $size): void
    {
        $this->maxPageSize = max(1, $size);
    }
}
