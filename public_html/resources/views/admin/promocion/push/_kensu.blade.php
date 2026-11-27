{{-- 表示件数 de page_control.php (10 a 50) --}}
@for ($i = 1; $i <= 5; $i++)
<option value="{{ $i * 10 }}" {{ $kensu == $i * 10 ? 'selected' : '' }}>{{ $i * 10 }}</option>
@endfor
