@extends('layouts.app')

@section('title', 'ユーザーの編集')

@section('content')
    <h1 class="text-xl font-bold mb-6">ユーザーの編集</h1>
    <div class="bg-white shadow rounded-lg p-6 max-w-xl">
        @include('admin.partials.errors')
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf @method('PUT')
            @include('admin.users._form', ['isNew' => false])
        </form>
    </div>
@endsection
