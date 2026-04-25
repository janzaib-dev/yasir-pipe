<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Unit;


class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $units = ['Piece', 'Dozen', 'Meter', 'Kilogram'];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit]);
        }
    }
}
