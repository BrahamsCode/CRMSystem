<?php

it('manda la raíz al login del personal', function () {
    $this->get('/')->assertRedirect('/system/login.php');
});
