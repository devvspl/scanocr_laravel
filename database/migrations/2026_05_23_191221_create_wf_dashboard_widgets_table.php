<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wf_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stage_id');
            $table->string('widget_type', 50);
            // widget_type: counter, chart, table, entry_form, file_upload, recent_entries
            $table->string('title', 200);
            $table->integer('position')->default(0);
            $table->integer('col_span')->default(1); // 1, 2, or 3 (out of 3-col grid)
            $table->json('config')->nullable();
            // config stores widget-specific settings:
            // counter: {source, filter, icon, color}
            // chart: {chart_type, source, x_field, y_field, color}
            // table: {source, columns, filters, pagination}
            // entry_form: {open_mode: 'inline'|'modal'|'new_page', page_id}
            // file_upload: {allowed_types, max_size, storage}
            // recent_entries: {limit, columns}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('stage_id')->references('id')->on('wf_stages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wf_dashboard_widgets');
    }
};
