<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add the new name column
            $table->string('name')->after('id');
        });
        
        // Copy data from title to name
        DB::statement('UPDATE projects SET name = title WHERE title IS NOT NULL');
        
        Schema::table('projects', function (Blueprint $table) {
            // Drop the old title column
            $table->dropColumn('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add the title column back
            $table->string('title')->after('id');
            
            // Copy data from name to title
            DB::statement('UPDATE projects SET title = name WHERE name IS NOT NULL');
            
            // Drop the name column
            $table->dropColumn('name');
        });
    }
};
