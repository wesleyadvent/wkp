@props(['value', 'label' => null])
@php
    $key = is_bool($value) ? ($value ? 'aktif' : 'nonaktif') : strtolower((string) $value);
    $tone = match ($key) {
        'aktif', 'active', 'valid', 'tersedia', 'selesai', 'sudah_diambil' => 'success',
        'batal', 'cancelled', 'nonaktif', 'inactive', 'arsip' => 'muted',
        'service', 'diproses', 'proses', 'dikerjakan' => 'warning',
        default => 'info',
    };
@endphp
<span class="status-badge status-badge-{{ $tone }}">{{ $label ?? ucfirst(str_replace('_', ' ', $key)) }}</span>
