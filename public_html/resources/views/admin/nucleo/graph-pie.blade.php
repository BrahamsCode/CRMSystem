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

--></style>


<script type="text/javascript" src="/system/js/circle.js"></script>

<script type="text/javascript">
window.onload = function() {
  var params = {
    @if ($caption)caption: true,
    @endif
legend: true,
    shadow: false,
    legendColor: "{{ $legendColor }}"
  };

  var cg1 = new html5jp.graph.circle("total");
  if( ! cg1 ) { return; }
  var total_items = {!! json_encode($items, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
  cg1.draw(total_items, params);
};
</script>

<title>グラフ</title>
</head>
<body>

@if ($total > 0)
<div>
<canvas width="{{ $width }}" height="{{ $height }}" id="total"></canvas>
</div>
@endif

</body>
</html>
