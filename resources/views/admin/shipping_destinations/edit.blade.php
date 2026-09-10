@extends('layouts.app')

@section('title', '直送先の編集')

@section('content')
    <h1 class="text-xl font-bold mb-6">直送先の編集</h1>
    <div class="bg-white shadow rounded-lg p-6 max-w-xl">
        @include('admin.partials.errors')
        <form method="POST" action="{{ route('admin.shipping_destinations.update', $destination) }}">
            @csrf @method('PUT')
            @include('admin.shipping_destinations._form')
        </form>
    </div>
@endsection
