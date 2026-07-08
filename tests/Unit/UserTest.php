<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_avatar_is_a_gravatar_url_derived_from_the_email(): void
    {
        $user = new User(['email' => 'Fian@Example.com']);

        $expectedHash = md5('fian@example.com');

        $this->assertSame(
            "https://www.gravatar.com/avatar/{$expectedHash}?d=404",
            $user->avatar,
        );
    }
}
