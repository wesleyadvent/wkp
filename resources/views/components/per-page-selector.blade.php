@props(['paginator', 'label' => 'Tampilkan'])

@once
    <form method="GET" class="inline-flex items-center gap-2 text-sm text-gray-600">
        @foreach (request()->except(['page', 'per_page']) as $name => $value)
            @if (is_array($value))
                @foreach ($value as $arrayValue)
                    <input type="hidden" name="{{ $name }}[]" value="{{ $arrayValue }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="per-page" class="whitespace-nowrap">{{ $label }}</label>
        <select id="per-page" name="per_page" class="rounded-md border-gray-300 py-1.5 text-sm" onchange="this.form.submit()">
            @foreach ([10, 15, 20, 25, 50, 100] as $option)
                <option value="{{ $option }}" @selected((int) request('per_page', $paginator->perPage()) === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <span>data</span>
    </form>
@endonce
