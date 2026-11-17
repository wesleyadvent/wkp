<nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-3 text-sm">
    <x-per-page-selector :paginator="$paginator" />

    <div class="flex flex-wrap items-center gap-3">
        <p class="text-gray-600">
            @if ($paginator->firstItem())
                Menampilkan <span class="font-medium">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span> dari <span class="font-medium">{{ $paginator->total() }}</span> data
            @else
                Tidak ada data
            @endif
        </p>

        @if ($paginator->hasPages())
            <span class="inline-flex rounded-md shadow-sm">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center rounded-l-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-400">Sebelumnya</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center rounded-l-md border border-gray-300 bg-white px-3 py-2 text-gray-700 hover:bg-gray-50">Sebelumnya</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex items-center border-y border-gray-300 bg-white px-3 py-2 text-gray-500">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page === $paginator->currentPage())
                                <span aria-current="page" class="inline-flex items-center border-y border-gray-300 bg-amber-50 px-3 py-2 font-semibold text-amber-700">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center border-y border-gray-300 bg-white px-3 py-2 text-gray-700 hover:bg-gray-50">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center rounded-r-md border border-gray-300 bg-white px-3 py-2 text-gray-700 hover:bg-gray-50">Berikutnya</a>
                @else
                    <span class="inline-flex items-center rounded-r-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-400">Berikutnya</span>
                @endif
            </span>
        @endif
    </div>
</nav>
