@if ($paginator->hasPages())
    <nav class="pagination-wrap" role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-list">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn disabled" aria-disabled="true">Prev</span>
            @else
                <a class="pagination-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Prev</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-btn active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="pagination-btn disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
