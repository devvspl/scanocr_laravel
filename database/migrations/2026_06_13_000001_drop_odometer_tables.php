<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('odometer_training_data');
        Schema::dropIfExists('odometer_readings');
    }

    public function down(): void
    {
        // Tables were intentionally removed; no rollback defined.
    }
};
