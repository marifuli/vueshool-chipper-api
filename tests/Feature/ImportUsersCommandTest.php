<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportUsersCommandTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear users table before each test
        User::query()->delete();
    }

    public function test_it_imports_users_from_json_url()
    {
        // Mock HTTP response
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([
                [
                    'id' => 1,
                    'name' => 'Leanne Graham',
                    'username' => 'Bret',
                    'email' => 'Sincere@april.biz',
                    'address' => [
                        'street' => 'Kulas Light',
                        'suite' => 'Apt. 556',
                        'city' => 'Gwenborough',
                        'zipcode' => '92998-3874',
                    ],
                    'phone' => '1-770-736-8031 x56442',
                    'website' => 'hildegard.org',
                    'company' => [
                        'name' => 'Romaguera-Crona',
                        'catchPhrase' => 'Multi-layered client-server neural-net',
                        'bs' => 'harness real-time e-markets',
                    ],
                ],
                [
                    'id' => 2,
                    'name' => 'Ervin Howell',
                    'username' => 'Antonette',
                    'email' => 'Shanna@melissa.tv',
                    'address' => [
                        'street' => 'Victor Plains',
                        'suite' => 'Suite 879',
                        'city' => 'Wisokyburgh',
                        'zipcode' => '90566-7771',
                    ],
                    'phone' => '010-692-6593 x09125',
                    'website' => 'anastasia.net',
                    'company' => [
                        'name' => 'Deckow-Crist',
                        'catchPhrase' => 'Proactive didactic contingency',
                        'bs' => 'synergize scalable supply-chains',
                    ],
                ],
            ], 200)
        ]);

        // Execute command
        $this->artisan('users:import', [
            'url' => 'https://jsonplaceholder.typicode.com/users',
            'limit' => 2
        ])
            ->expectsOutput('Importing up to 2 users from https://jsonplaceholder.typicode.com/users')
            ->expectsOutput('Imported user: Leanne Graham (Sincere@april.biz)')
            ->expectsOutput('Imported user: Ervin Howell (Shanna@melissa.tv)')
            ->expectsOutput('Import completed. 2 users imported, 0 users skipped.')
            ->assertExitCode(0);

        // Assert users were created in the database
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Ervin Howell',
            'email' => 'Shanna@melissa.tv',
        ]);
    }

    public function test_it_respects_limit_parameter()
    {
        // Mock HTTP response with 5 users
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([
                ['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com'],
                ['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com'],
                ['id' => 3, 'name' => 'User 3', 'email' => 'user3@example.com'],
                ['id' => 4, 'name' => 'User 4', 'email' => 'user4@example.com'],
                ['id' => 5, 'name' => 'User 5', 'email' => 'user5@example.com'],
            ], 200)
        ]);

        // Execute command with limit of 3
        $this->artisan('users:import', [
            'url' => 'https://jsonplaceholder.typicode.com/users',
            'limit' => 3
        ])
            ->expectsOutput('Import completed. 3 users imported, 0 users skipped.')
            ->assertExitCode(0);

        // Assert only 3 users were created
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', ['email' => 'user1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user2@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user3@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'user4@example.com']);
    }

    public function test_it_handles_invalid_url()
    {
        $this->artisan('users:import', [
            'url' => 'invalid-url',
            'limit' => 5
        ])
            ->expectsOutput('Invalid URL provided.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_handles_failed_http_request()
    {
        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response(null, 404)
        ]);

        $this->artisan('users:import', [
            'url' => 'https://jsonplaceholder.typicode.com/users',
            'limit' => 5
        ])
            ->expectsOutput('Failed to fetch data from https://jsonplaceholder.typicode.com/users. Status code: 404')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_skips_existing_users()
    {
        // Create a user that will conflict with the import
        User::create([
            'name' => 'Existing User',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);

        Http::fake([
            'jsonplaceholder.typicode.com/users' => Http::response([
                ['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com'],
                ['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com'],
            ], 200)
        ]);

        $this->artisan('users:import', [
            'url' => 'https://jsonplaceholder.typicode.com/users',
            'limit' => 2
        ])
            ->expectsOutput('User with email user1@example.com already exists. Skipping.')
            ->expectsOutput('Import completed. 1 users imported, 1 users skipped.')
            ->assertExitCode(0);

        // Assert only one new user was created (total of 2)
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['name' => 'Existing User']);
        $this->assertDatabaseHas('users', ['name' => 'User 2']);
    }
}
