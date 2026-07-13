@extends('layouts.app')

@section('title', 'ユーザーの新規登録')

@section('content')
    <h1 class="text-xl font-bold mb-6">ユーザーの新規登録</h1>
    <div class="bg-white shadow rounded-lg p-6 max-w-xl">
        @include('admin.partials.errors')
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            @include('admin.users._form', ['isNew' => true])
        </form>
    </div>
@endsection
