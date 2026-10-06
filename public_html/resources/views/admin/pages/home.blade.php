@extends('layouts.base')

@section('title', 'Inicio')

@section('content')
    <h1 class="text-2xl font-bold">Hola, {{ auth('admin')->user()->name }}</h1>
@endsection
