<?php

namespace Database\Seeders;

use App\Models\HolidayCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class HolidayCalendarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (HolidayCalendar::whereYear('date', today()->year)->count() > 0) {
            return;
        }

        Artisan::call('generate:holiday', [
            '--is-seeder' => true,
        ]);
    }
}
