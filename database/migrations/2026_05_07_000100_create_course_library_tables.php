<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('scan_root', 1024);
            $table->string('scan_root_hash', 64);
            $table->string('relative_path', 1024);
            $table->string('relative_path_hash', 64);
            $table->string('source_path', 2048);
            $table->string('source_title');
            $table->string('display_title');
            $table->unsignedInteger('sort_index')->default(0);
            $table->boolean('is_missing')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['scan_root_hash', 'relative_path_hash'], 'courses_root_relative_unique');
            $table->index(['scan_root_hash', 'is_missing'], 'courses_root_missing_idx');
        });

        Schema::create('course_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('relative_path', 1024);
            $table->string('relative_path_hash', 64);
            $table->string('source_path', 2048);
            $table->string('source_title');
            $table->string('display_title');
            $table->unsignedInteger('sort_index')->default(0);
            $table->boolean('is_missing')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'relative_path_hash'], 'course_sections_course_relative_unique');
            $table->index(['course_id', 'is_missing'], 'course_sections_course_missing_idx');
        });

        Schema::create('lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_section_id')->constrained('course_sections')->cascadeOnDelete();
            $table->string('relative_path', 1024);
            $table->string('relative_path_hash', 64);
            $table->string('source_path', 2048);
            $table->string('filename');
            $table->string('source_title');
            $table->string('display_title');
            $table->unsignedInteger('sort_index')->default(0);
            $table->decimal('duration_seconds', 10, 3)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('mime_type', 255)->default('application/octet-stream');
            $table->string('extension', 50)->nullable();
            $table->string('thumbnail_path', 2048)->nullable();
            $table->string('preview_manifest_path', 2048)->nullable();
            $table->boolean('is_missing')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'relative_path_hash'], 'lessons_course_relative_unique');
            $table->index(['course_section_id', 'sort_index'], 'lessons_section_order_idx');
            $table->index(['course_id', 'is_missing'], 'lessons_course_missing_idx');
        });

        Schema::create('resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_section_id')->nullable()->constrained('course_sections')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->string('relative_path', 1024);
            $table->string('relative_path_hash', 64);
            $table->string('source_path', 2048);
            $table->string('filename');
            $table->string('display_title');
            $table->unsignedInteger('sort_index')->default(0);
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('mime_type', 255)->default('application/octet-stream');
            $table->string('extension', 50)->nullable();
            $table->boolean('is_missing')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'relative_path_hash'], 'resources_course_relative_unique');
            $table->index(['course_id', 'is_missing'], 'resources_course_missing_idx');
        });

        Schema::create('lesson_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->decimal('last_position_seconds', 10, 3)->default(0);
            $table->decimal('duration_seconds', 10, 3)->nullable();
            $table->decimal('percent_watched', 5, 2)->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id'], 'lesson_progress_user_lesson_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('courses');
    }
};
