<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $bootstrapToHex = [
        'primary'   => '#405189',
        'secondary' => '#6c757d',
        'success'   => '#0ab39c',
        'danger'    => '#f06548',
        'warning'   => '#f7b84b',
        'info'      => '#299cdb',
    ];

    public function up(): void
    {
        // Convert Bootstrap color names to hex codes
        foreach ($this->bootstrapToHex as $bootstrap => $hex) {
            DB::table('transaction_statuses')
                ->where('color', $bootstrap)
                ->update(['color' => $hex]);
        }
    }

    public function down(): void
    {
        $hexToBootstrap = array_flip($this->bootstrapToHex);

        foreach ($hexToBootstrap as $hex => $bootstrap) {
            DB::table('transaction_statuses')
                ->where('color', $hex)
                ->update(['color' => $bootstrap]);
        }
    }
};
