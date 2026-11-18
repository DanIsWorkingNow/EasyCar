@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">
        <p class="hidden text-sm text-subtle sm:block">
            Showing <span class="font-medium text-white">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-white">{{ $paginator->lastItem() }}</span> of <span class="font-medium text-white">{{ $paginator->total() }}</span>
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-quiet pointer-events-none opacity-40" aria-disabled="true"><x-icon name="chevron-left" class="size-4" /> Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-sm btn-outline"><x-icon name="chevron-left" class="size-4" /> Prev</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-subtle">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="hidden min-w-9 rounded-md bg-white px-2.5 py-1.5 text-center text-xs font-bold text-black sm:inline-block">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="hidden min-w-9 rounded-md px-2.5 py-1.5 text-center text-xs font-semibold text-mute hover:bg-white/5 hover:text-white sm:inline-block">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-sm btn-outline">Next <x-icon name="chevron-right" class="size-4" /></a>
            @else
                <span class="btn btn-sm btn-quiet pointer-events-none opacity-40" aria-disabled="true">Next <x-icon name="chevron-right" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
