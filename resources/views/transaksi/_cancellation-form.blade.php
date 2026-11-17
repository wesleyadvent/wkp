@php($dialogId = 'batalkan-'.$transactionType.'-'.$transactionId)
@php($expired = $transactionCreatedAt && $transactionCreatedAt->lt(now()->subHours(24)))
@if($expired)
    <button type="button" class="action-button border-gray-200 bg-gray-100 text-gray-500" onclick="alert('Transaksi sudah lebih dari 24 jam sejak nota dibuat dan tidak dapat dibatalkan.')">Batalkan transaksi</button>
@else
    <button type="button" class="action-button action-delete" onclick="document.getElementById('{{ $dialogId }}').showModal()">Batalkan transaksi</button>
@endif

<dialog id="{{ $dialogId }}" class="w-full max-w-lg rounded-xl p-0 shadow-xl backdrop:bg-slate-900/50">
    <form method="POST" action="{{ route('transactions.cancel') }}" class="space-y-4 p-6">
        @csrf
        <input type="hidden" name="transaction_type" value="{{ $transactionType }}">
        <input type="hidden" name="transaction_id" value="{{ $transactionId }}">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Verifikasi pembatalan</h3>
                <p class="mt-1 text-sm text-gray-600">Masukkan email dan password admin untuk melanjutkan.</p>
            </div>
            <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" onclick="document.getElementById('{{ $dialogId }}').close()" aria-label="Tutup">&times;</button>
        </div>
        <div>
            <x-input-label for="admin_email_{{ $transactionType }}_{{ $transactionId }}" value="Email admin" />
            <x-text-input id="admin_email_{{ $transactionType }}_{{ $transactionId }}" type="email" name="admin_email" value="{{ old('admin_email') }}" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="admin_password_{{ $transactionType }}_{{ $transactionId }}" value="Password admin" />
            <x-text-input id="admin_password_{{ $transactionType }}_{{ $transactionId }}" type="password" name="admin_password" class="mt-1 block w-full" required />
        </div>
        <div>
            <x-input-label for="alasan_{{ $transactionType }}_{{ $transactionId }}" value="Alasan pembatalan" />
            <textarea id="alasan_{{ $transactionType }}_{{ $transactionId }}" name="alasan" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>{{ old('alasan') }}</textarea>
        </div>
        @if($errors->any() && (string) old('transaction_id') === (string) $transactionId)<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
        <div class="flex justify-end gap-2">
            <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700" onclick="document.getElementById('{{ $dialogId }}').close()">Tutup</button>
            <x-danger-button type="submit" onclick="return confirm('Yakin ingin membatalkan transaksi ini?')">Konfirmasi pembatalan</x-danger-button>
        </div>
    </form>
</dialog>
@if($errors->any() && (string) old('transaction_id') === (string) $transactionId)
    <script>document.getElementById('{{ $dialogId }}').showModal();</script>
@endif
