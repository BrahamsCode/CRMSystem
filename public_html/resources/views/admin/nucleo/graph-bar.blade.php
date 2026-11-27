@php $js = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); @endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex" />
<meta name="robots" content="nofollow" />
<meta name="robot-control" content="deny-all" />
<meta name="robots" content="none" />
<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />

<style type="text/css"><!--

body {
	background-color: {{ $background }};
}

.area {
	margin-left: 10px;
}

--></style>


<script type="text/javascript" src="/system/js/vbar.js"></script>

<script type="text/javascript">
window.onload = function() {
  var g = new html5jp.graph.vbar("total");
  if( ! g ) { return; }
  var items = {!! $js($items) !!};
  var params = {
    backgroundColor: {!! $js($background) !!},
    legend: {{ $legend ? 'true' : 'false' }},
    barShape: {!! $js($barShape) !!},
    @if ($graphBackground)gBackgroundColor: {!! $js($graphBackground) !!},
    @endif
barColors: {!! $js($barColors) !!},
    x: {!! $js($x) !!},
    y: {!! $js($y) !!}
  };
  g.draw(items, params);
};
</script>

<title>グラフ</title>
</head>
<body>

<div class="area" style="margin-left:30px;">
<canvas width="{{ $width }}" height="{{ $height }}" id="total"></canvas>
</div>

</body>
</html>
