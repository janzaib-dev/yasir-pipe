<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Electronics' => ['Fan', 'ceiling  Fan','Pedestal  Fan' , 'Fridge', 'Air-Condition(AC)', 'Washing Machine', 'Microwave Oven'],
            'machine' => ['Drill Machine', 'Grinder', 'Lathe Machine', 'Milling Machine', 'Shaper Machine'],
            'Tools' => ['Hammer', 'Screwdriver', 'Wrench', 'Pliers', 'Tape Measure'],
            'Plumbing' => ['Pipe', 'Faucet', 'Valve', 'Toilet', 'Sink'],
            'Hardware' => ['Nails', 'Screws', 'Bolts', 'Hinges', 'Brackets'],
            'Electrical' => ['Light', 'Switch', 'Wire', 'Cable'],
            'Automotive' => ['Engine Oil', 'Brake Pads', 'Tires', 'Batteries', 'Filters'],


        ];

        foreach ($data as $categoryName => $subcategories) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($subcategories as $sub) {
                Subcategory::create([
                    'category_id' => $category->id,
                    'name' => $sub,
                ]);
            }
        }
    }
}
