<?php

namespace Database\Seeders;

use App\Models\CourseSection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CourseSection::factory()->count(10)->create();
    }
}
