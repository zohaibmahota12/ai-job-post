@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">Users</h1>
    <div class="mt-6 overflow-x-auto rounded-2xl border border-line bg-card shadow-sm shadow-ink/5">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-line text-bark">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Verified</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $account)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3">{{ $account->name }}</td>
                        <td class="px-4 py-3">{{ $account->email }}</td>
                        <td class="px-4 py-3">{{ $account->role->label() }}</td>
                        <td class="px-4 py-3">{{ $account->hasVerifiedEmail() ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3">
                            @if (auth()->user()->is($account))
                                {{ $account->is_active ? 'Active' : 'Disabled' }}
                            @else
                                <form method="POST" action="{{ route('admin.users.update', $account) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $account->is_active ? '0' : '1' }}">
                                    <button class="underline" type="submit">{{ $account->is_active ? 'Disable' : 'Enable' }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
@endsection
