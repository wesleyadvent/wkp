<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $userModel->exists ? 'Edit User' : 'Tambah User' }}</h2></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><form method="POST" action="{{ $userModel->exists ? route('users.update',$userModel) : route('users.store') }}" class="space-y-5 bg-white p-6 shadow-sm sm:rounded-lg">@csrf @if($userModel->exists) @method('PUT') @endif
        <x-input-error :messages="$errors->all()" />
        <div><x-input-label for="name" value="Nama" /><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name',$userModel->name)" required /></div>
        <div><x-input-label for="email" value="Email" /><x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email',$userModel->email)" required /></div>
        <div><x-input-label for="password" value="Password" /><x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $userModel->exists" /></div>
        <div><x-input-label for="role" value="Role" /><select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300"><option value="admin" @selected(old('role',$userModel->role)==='admin')>admin</option><option value="toko_cabang" @selected(old('role',$userModel->role)==='toko_cabang')>toko_cabang</option></select></div>
        <div><x-input-label for="branch_id" value="Cabang" /><select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300"><option value="">Tanpa cabang</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int) old('branch_id',$userModel->branch_id)===$branch->id)>{{ $branch->nama_cabang }}</option>@endforeach</select></div>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$userModel->is_active ?? true))><span>Aktif</span></label>
        <x-primary-button>Simpan</x-primary-button>
    </form></div></div>
</x-app-layout>
