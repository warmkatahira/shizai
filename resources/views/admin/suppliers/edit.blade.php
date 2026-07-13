@extends('layouts.app')

@section('title', '業者の編集')

@section('content')
    <h1 class="text-xl font-bold mb-6">業者の編集</h1>
    <div class="bg-white shadow rounded-lg p-6 max-w-xl">
        @include('admin.partials.errors')
        <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">
            @csrf @method('PUT')
            @include('admin.suppliers._form')
        </form>
    </div>
@endsection
