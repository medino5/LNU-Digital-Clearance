@if(method_exists($paginator, 'hasPages') && $paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $windowStart = max(1, $currentPage - 2);
        $windowEnd = min($lastPage, $currentPage + 2);
    @endphp

    <nav class="pagination-wrapper office-pagination" aria-label="{{ $label ?? 'Pagination' }}">
        @if($paginator->onFirstPage())
            <span class="office-page-link is-disabled" aria-disabled="true">First</span>
            <span class="office-page-link is-disabled" aria-disabled="true">Previous</span>
        @else
            <a class="office-page-link" href="{{ $paginator->url(1) }}">First</a>
            <a class="office-page-link" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @endif

        @if($windowStart > 1)
            <a class="office-page-link" href="{{ $paginator->url(1) }}">1</a>
            @if($windowStart > 2)
                <span class="office-page-ellipsis">...</span>
            @endif
        @endif

        @for($page = $windowStart; $page <= $windowEnd; $page++)
            @if($page === $currentPage)
                <span class="office-page-link is-current" aria-current="page">{{ $page }}</span>
            @else
                <a class="office-page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if($windowEnd < $lastPage)
            @if($windowEnd < $lastPage - 1)
                <span class="office-page-ellipsis">...</span>
            @endif
            <a class="office-page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
        @endif

        @if($paginator->hasMorePages())
            <a class="office-page-link" href="{{ $paginator->nextPageUrl() }}">Next</a>
            <a class="office-page-link" href="{{ $paginator->url($lastPage) }}">Last</a>
        @else
            <span class="office-page-link is-disabled" aria-disabled="true">Next</span>
            <span class="office-page-link is-disabled" aria-disabled="true">Last</span>
        @endif
    </nav>
@endif
