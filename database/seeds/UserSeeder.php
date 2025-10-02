<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Tạo user test để login
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'password' => bcrypt('123456'),
        ]);

        factory(User::class, 20)->create();
    }
}
