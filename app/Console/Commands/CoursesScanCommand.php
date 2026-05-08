<?php

namespace App\Console\Commands;

use App\Services\Courses\CourseScanner;
use Illuminate\Console\Command;

class CoursesScanCommand extends Command
{
    protected $signature = 'courses:scan
        {path? : Absolute path to the course library root}
        {--dry-run : Scan without persisting database changes}
        {--with-thumbnails : Force thumbnail generation attempt}
        {--no-thumbnails : Disable thumbnail generation}';

    protected $description = 'Scan course directories and sync course metadata into the database';

    public function __construct(private readonly CourseScanner $scanner)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $withThumbnails = (bool) $this->option('with-thumbnails');
        $withoutThumbnails = (bool) $this->option('no-thumbnails');

        if ($withThumbnails && $withoutThumbnails) {
            $this->error('Use only one of --with-thumbnails or --no-thumbnails.');

            return self::INVALID;
        }

        $path = (string) ($this->argument('path') ?: config('courses.root'));
        $dryRun = (bool) $this->option('dry-run');
        $thumbnailOption = null;
        if ($withThumbnails) {
            $thumbnailOption = true;
        } elseif ($withoutThumbnails) {
            $thumbnailOption = false;
        }

        $this->components->info(sprintf('Scanning courses at: %s', $path));

        try {
            $summary = $this->scanner->scan(
                scanRoot: $path,
                dryRun: $dryRun,
                generateThumbnails: $thumbnailOption
            );
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Courses created', $summary->stats['courses_created']],
                ['Courses updated', $summary->stats['courses_updated']],
                ['Courses unchanged', $summary->stats['courses_unchanged']],
                ['Courses marked missing', $summary->stats['courses_missing']],
                ['Sections created', $summary->stats['sections_created']],
                ['Sections updated', $summary->stats['sections_updated']],
                ['Sections unchanged', $summary->stats['sections_unchanged']],
                ['Sections marked missing', $summary->stats['sections_missing']],
                ['Lessons created', $summary->stats['lessons_created']],
                ['Lessons updated', $summary->stats['lessons_updated']],
                ['Lessons unchanged', $summary->stats['lessons_unchanged']],
                ['Lessons marked missing', $summary->stats['lessons_missing']],
                ['Resources created', $summary->stats['resources_created']],
                ['Resources updated', $summary->stats['resources_updated']],
                ['Resources unchanged', $summary->stats['resources_unchanged']],
                ['Resources marked missing', $summary->stats['resources_missing']],
                ['Errors', $summary->stats['errors']],
            ]
        );

        if ($summary->errors !== []) {
            $this->newLine();
            $this->warn('Errors:');
            foreach ($summary->errors as $error) {
                $this->line('- '.$error);
            }
        }

        if ($dryRun) {
            $this->comment('Dry run complete. No database changes were saved.');
        } else {
            $this->components->info('Scan complete.');
        }

        return self::SUCCESS;
    }
}
