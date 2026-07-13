@extends('layouts.app')

@section('title', '営業所の新規登録')

@section('content')
    <h1 class="text-xl font-bold mb-6">営業所の新規登録</h1>
    <div class="bg-white shadow rounded-lg p-6 max-w-xl">
        @include('admin.partials.errors')
        <form method="POST" action="{{ route('admin.offices.store') }}">
            @csrf
            @include('admin.offices._form')
        </form>
    </div>
@endsection
