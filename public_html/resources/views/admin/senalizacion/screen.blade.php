{{-- Pantalla de gestión con el menú lateral (list/*.php, index.php) --}}
@extends('admin.senalizacion.layout')

@section('head')
{!! $parts['head'] !!}
@endsection

@section('body')
<body>
<div id="layout">
@include('admin.senalizacion.partials.side')

{!! $parts['main'] !!}

</div>
</body>
@endsection

@section('scripts')
{!! $parts['scripts'] !!}
@endsection
