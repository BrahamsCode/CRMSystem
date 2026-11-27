{{-- Ventana emergente sin menú (list/contents_detail.php, list/movie_contents_detail.php) --}}
@extends('admin.senalizacion.layout')

@section('head')
{!! $parts['head'] !!}
@endsection

@section('body')
<body>
<div id="layout" style="padding-left: 0px;">

{!! $parts['main'] !!}
</body>
@endsection

@section('footer')
<script src="/signage/js/jquery-3.1.1.js"></script>
@endsection

@section('scripts')
{!! $parts['scripts'] !!}
@endsection
