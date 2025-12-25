<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ImportUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import {url} {limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a JSON URL with a specified limit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $limit = (int) $this->argument('limit');

        $this->info("Importing up to {$limit} users from {$url}");

        try {
            // Validate URL
            $validator = Validator::make(['url' => $url], [
                'url' => 'required|url',
            ]);

            if ($validator->fails()) {
                $this->error('Invalid URL provided.');
                return 1;
            }

            // Fetch users from URL
            $response = Http::get($url);

            if (!$response->successful()) {
                $this->error("Failed to fetch data from {$url}. Status code: {$response->status()}");
                return 1;
            }

            $users = $response->json();

            if (!is_array($users)) {
                $this->error('Invalid JSON response. Expected an array of users.');
                return 1;
            }

            // Apply limit
            $users = array_slice($users, 0, $limit);

            $importCount = 0;
            $skipCount = 0;

            // Process each user
            foreach ($users as $userData) {
                // Validate required fields
                if (!isset($userData['name']) || !isset($userData['email'])) {
                    $this->warn("Skipping user with incomplete data: " . json_encode($userData));
                    $skipCount++;
                    continue;
                }

                // Check if user with this email already exists
                if (User::where('email', $userData['email'])->exists()) {
                    $this->info("User with email {$userData['email']} already exists. Skipping.");
                    $skipCount++;
                    continue;
                }

                // Create new user
                User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'), // Default password
                ]);

                $importCount++;
                $this->info("Imported user: {$userData['name']} ({$userData['email']})");
            }

            $this->info("Import completed. {$importCount} users imported, {$skipCount} users skipped.");
            return 0;
        } catch (\Exception $e) {
            $this->error("An error occurred: {$e->getMessage()}");
            return 1;
        }
    }
}
