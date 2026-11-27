<?php

require_once __DIR__ . '/helpers.php';

// Gráficos en iframe (system/inc/graph_pie.php, graph_bar.php)

beforeEach(function () {
    seedNucleo();
    loginAs('master', 'secreto1');
});

it('dibuja el circular con los pares nombre,valor y lo oculta si el total es 0', function () {
    $this->get('/system/inc/graph_pie.php?w=500&h=200&v=' . urlencode('男性,3|女性,5'))
        ->assertOk()
        ->assertSee('/system/js/circle.js', false)
        ->assertSee('var total_items = [["男性",3],["女性",5]];', false)
        ->assertSee('<canvas width="500" height="200" id="total"></canvas>', false);

    $this->get('/system/inc/graph_pie.php?v=' . urlencode('a,0|b,0'))->assertOk()->assertDontSee('<canvas', false);
});

it('dibuja las barras con ejes, colores y forma, escapando lo que llega por la URL', function () {
    $this->get('/system/inc/graph_bar.php?b=FFFFFF&gb=CCCCCC&gtype=cylinder&l=false&w=500&h=150&x=' . urlencode(',A,B') . '&y=回答数&bc=005DFF&v=' . urlencode(',2,7'))
        ->assertOk()
        ->assertSee('var items = [["",2,7]];', false)
        ->assertSee('barShape: "cylinder"', false)
        ->assertSee('gBackgroundColor: "#CCCCCC"', false)
        ->assertSee('barColors: ["#005DFF"]', false)
        ->assertSee('x: ["","A","B"]', false)
        ->assertSee('legend: false', false)
        ->assertSee('<canvas width="500" height="150" id="total"></canvas>', false);

    $this->get('/system/inc/graph_bar.php?b=' . urlencode('red;}</style><script>') . '&y=' . urlencode('"</script>'))
        ->assertOk()->assertSee('background-color: transparent;', false)->assertDontSee('"</script>', false);
});

it('exige sesión del personal', function () {
    $this->get('/system/logout.php');
    $this->get('/system/inc/graph_pie.php?v=a,1')->assertRedirect('/system/login.php');
});
