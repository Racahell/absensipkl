@if ($paginator->hasPages())
    <nav class="pagination-wrap" role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-list">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn disabled" aria-disabled="true">Prev</span>
            @else
                <a class="pagination-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Prev</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="pagination-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="pagination-btn disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
