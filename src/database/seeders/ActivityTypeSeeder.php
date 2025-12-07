<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ActivityType;

class ActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activityTypes = [
            [
                'name' => 'Degustazione Vino',
                'abbreviation' => 'WINE',
                'notes' => 'Attività di degustazione vino presso cantine e aziende vinicole'
            ],
            [
                'name' => 'Visita',
                'abbreviation' => 'VISIT',
                'notes' => 'Visita guidata a luoghi di interesse turistico, culturale o aziendale'
            ],
            [
                'name' => 'Light Lunch',
                'abbreviation' => 'LUNCH',
                'notes' => 'Pranzo leggero o pausa pranzo durante il servizio'
            ],
        ];

        foreach ($activityTypes as $type) {
            ActivityType::create($type);
        }
    }
}
