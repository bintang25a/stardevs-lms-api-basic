<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
  public function run(): void
  {
    User::create([
      'name'         => 'Administrator',
      'nim'          => '00000001',
      'phone_number' => '081111111111',
      'email'        => 'admin@stardevs.com',
      'role'         => 'admin',
      'password'     => Hash::make('password123'),
      'email_verified_at' => '2026-10-31 11:12:23.000',
    ]);

    // 2. Buat Akun Asisten
    User::create([
      'name'         => 'Asisten Lab',
      'nim'          => '00000002',
      'phone_number' => '082222222222',
      'email'        => 'asisten@stardevs.com',
      'role'         => 'asisten',
      'password'     => Hash::make('password123'),
      'email_verified_at' => '2026-10-31 11:12:23.000',
    ]);

    // 3. Buat Akun Praktikan
    User::create([
      'name'         => 'Praktikan Mahasiswa',
      'nim'          => '21120120120001',
      'phone_number' => '083333333333',
      'email'        => 'praktikan@stardevs.com',
      'role'         => 'praktikan',
      'password'     => Hash::make('password123'),
      'email_verified_at' => '2026-10-31 11:12:23.000',
    ]);
  }
}
