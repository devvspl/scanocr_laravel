<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_predictions', function (Blueprint $table) {
            $table->foreignId('predicted_department_id')->nullable()->after('confidence_score')
                  ->constrained('departments')->nullOnDelete();
            $table->decimal('department_confidence', 5, 2)->nullable()->after('predicted_department_id');
            $table->foreignId('predicted_location_id')->nullable()->after('department_confidence')
                  ->constrained('locations')->nullOnDelete();
            $table->decimal('location_confidence', 5, 2)->nullable()->after('predicted_location_id');
            $table->json('prediction_reasoning')->nullable()->after('ocr_page_texts');
        });
    }

    public function down(): void
    {
        Schema::table('document_predictions', function (Blueprint $table) {
            $table->dropForeign(['predicted_department_id']);
            $table->dropForeign(['predicted_location_id']);
            $table->dropColumn([
                'predicted_department_id',
                'department_confidence',
                'predicted_location_id',
                'location_confidence',
                'prediction_reasoning',
            ]);
        });
    }
};
