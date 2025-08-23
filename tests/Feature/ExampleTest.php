<?php

test('example', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});
