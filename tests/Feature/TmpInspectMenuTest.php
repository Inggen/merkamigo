<?php

use App\Models\User;

test('inspect experience switch menu html', function () {
    $user = User::factory()->create(['experience' => 'cliente']);

    $html = $this->actingAs($user)->get(route('home'))->getContent();

    file_put_contents('/tmp/menu_dump.html', $html);

    expect(true)->toBeTrue();
});
