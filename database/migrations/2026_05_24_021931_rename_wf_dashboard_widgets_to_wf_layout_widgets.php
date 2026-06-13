<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('wf_dashboard_widgets', 'wf_layout_widgets');
    }

    public function down(): void
    {
        Schema::rename('wf_layout_widgets', 'wf_dashboard_widgets');
    }
};
