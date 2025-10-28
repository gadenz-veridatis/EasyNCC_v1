<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Company::create([
            'name' => 'NCC Roma Luxury',
            'email' => 'info@nccromaluxury.it',
            'phone' => '+39 06 1234567',
            'vat_number' => '12345678901',
            'sdi' => 'ABCD1234',
            'pec' => 'nccromaluxury@pec.it',
            'address' => 'Via Veneto, 50 - 00187 Roma (RM)',
            'website' => 'www.nccromaluxury.it',
            'is_active' => true,
        ]);

        \App\Models\Company::create([
            'name' => 'Milano Executive Transport',
            'email' => 'info@milanexec.it',
            'phone' => '+39 02 9876543',
            'vat_number' => '98765432101',
            'sdi' => 'EFGH5678',
            'pec' => 'milanexec@pec.it',
            'address' => 'Corso Vittorio Emanuele II, 25 - 20122 Milano (MI)',
            'website' => 'www.milanexec.it',
            'is_active' => true,
        ]);
    }
}
