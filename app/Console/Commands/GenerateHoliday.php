<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use Calendarific\Calendarific;
use App\Models\HolidayCalendar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GenerateHoliday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:holiday {--is-seeder : Check if command run from seeder} {--year= : Year of holiday to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate holidays for a specific year.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isSeeder = $this->option('is-seeder');

        if ($isSeeder) {
            $year = now()->year;
        } else {
            $year = $this->option('year');
        }

        if (!isset($year)) {
            $year = (int) $this->ask(
                'Input the year in which you want to generate the holidays for',
                is_null($this->option('year')) ? now()->year : $this->option('year'),
            );
        }

        if (is_null($year)) {
            $this->error('Year can`t be null');

            return Command::FAILURE;
        }

        try {
            $validator = Validator::make(
                [
                    'year' => $year,
                ],
                [
                    'year' => ['numeric', 'integer', 'max:' . now()->year],
                ]
            );

            if ($validator->fails()) {
                $this->error(
                    'Year must be less than or equals to '
                        . now()->year
                        . '!'
                );

                return Command::FAILURE;
            }

            $this->generateHolidays($year);

            $this->info('Holiday generated successfully!');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error($e->getMessage());

            Log::error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function generateHolidays($year)
    {
        $response = Calendarific::make(
            key: config('services.calendarific.key'),
            country: config('services.calendarific.country_code'),
            year: $year,
            types: ['national']
        );

        if ($response['meta']['code'] != 200) {
            throw new Exception('Something went wrong, please try again!');
        }

        $data = $response['response']['holidays'];

        $holidays = [];

        foreach ($data as $row) {
            $holidays[] = [
                'name' => $row['name'],
                'date' => Carbon::parse($row['date']['iso']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $idColumns = ['date', 'name'];

        HolidayCalendar::upsert(
            $holidays,
            $idColumns,
            [
                'created_at',
                'updated_at',
            ]
        );
    }
}
