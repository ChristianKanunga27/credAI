<?php

test('language selection persists in the session and changes the page language', function () {
    $this->from('/')->get(route('language.switch', 'sw'))->assertRedirect('/');

    $this->get('/')->assertOk()->assertSee('<html lang="sw">', false);
});

test('language selection only accepts supported locales', function () {
    $this->get(route('language.switch', 'fr'))->assertNotFound();
});
