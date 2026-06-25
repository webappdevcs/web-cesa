<?php

test('homepage renders successfully and opens external links in a new tab', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    // External links should have target="_blank" and rel="noopener noreferrer"
    $response->assertSee('href="https://helpdesk.completeselular.com/"', false);
    $response->assertSee('href="/sam/redirect"', false);
    $response->assertSee('href="http://csa1.completeselular.com/owncloud/index.php/login"', false);
    $response->assertSee('href="https://wa.me/628112444444"', false);
    $response->assertSee('target="_blank"', false);
    $response->assertSee('rel="noopener noreferrer"', false);

    // Internal links should also be visible
    $response->assertSee('href="/form"', false);
});

test('sam redirects to android play store on android device', function () {
    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Linux; Android 10; SM-A505F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.119 Mobile Safari/537.36',
    ])->get('/sam/redirect');

    $response->assertRedirect('https://play.google.com/store/apps/details?id=com.mediaselularindonesia.sam');
});

test('sam redirects to apple testflight on ios device', function () {
    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
    ])->get('/sam/redirect');

    $response->assertRedirect('https://testflight.apple.com/join/yMZWH4CT');
});

test('sam redirects to web on desktop device', function () {
    $response = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ])->get('/sam/redirect');

    $response->assertRedirect('https://sam.mediaselularindonesia.com/');
});
