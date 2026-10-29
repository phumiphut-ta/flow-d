<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Initial Admin User
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'ผู้ดูแลระบบ (Admin)',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // 2. Initial Staff User for testing
        User::firstOrCreate(
            ['username' => 'staff'],
            [
                'name' => 'เจ้าหน้าที่สารบรรณ',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'is_active' => true,
            ]
        );

        // 3. Default Pipeline Route requested by user
        Route::firstOrCreate(
            ['route_name' => 'ลงเลขรับ-หอบ. ลงนาม-สารบรรณ-เสร็จสิ้น'],
            [
                'steps_json' => ['ลงเลขรับ', 'หอบ. ลงนาม', 'สารบรรณ', 'เสร็จสิ้น'],
                'description' => 'เส้นทางหลัก 4 ขั้นตอน: ลงเลขรับ -> หอบ. ลงนาม -> สารบรรณ -> เสร็จสิ้น',
                'is_active' => true,
            ]
        );

        // 4. Point-to-Point Route
        Route::firstOrCreate(
            ['route_name' => 'ผบป.อบ.-หอบ.'],
            [
                'steps_json' => ['หอบ.'],
                'description' => 'เส้นทางตรงจุดเดียว (Point-to-Point) ส่งออกหนังสือจบรอบทันที',
                'is_active' => true,
            ]
        );
    }
}
