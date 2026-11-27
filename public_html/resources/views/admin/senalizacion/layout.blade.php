{{-- Señalización web (signage/): documento del legacy = inc/head.php + inc/side.php + inc/footer.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
@include('admin.senalizacion.partials.head')
@yield('head')
</head>

@yield('body')

@section('footer')
@include('admin.senalizacion.partials.footer')
@show

@yield('scripts')

</html>
