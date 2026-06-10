<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // =====================
        // 1. BEMS CLIENTS
        // =====================
        $clients = [
            ['code' => 'FPTK-001',  'user_id' => 1, 'name' => 'Fakultas Pendidikan Teknologi & Kejuruan', 'expirity' => '2027-12-31'],
            ['code' => 'FPIPS-001', 'user_id' => 1, 'name' => 'Fakultas Pendidikan Ilmu Pengetahuan Sosial', 'expirity' => '2027-12-31'],
            ['code' => 'FPMIPA-001','user_id' => 1, 'name' => 'Fakultas Pendidikan Matematika & IPA', 'expirity' => '2027-12-31'],
        ];

        foreach ($clients as $c) {
            DB::table('bems_clients')->updateOrInsert(['code' => $c['code']], array_merge($c, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        $c1 = DB::table('bems_clients')->where('code', 'FPTK-001')->first();
        $c2 = DB::table('bems_clients')->where('code', 'FPIPS-001')->first();
        $c3 = DB::table('bems_clients')->where('code', 'FPMIPA-001')->first();

        // =====================
        // 2. BUILDINGS
        // =====================
        $buildings = [
            ['client_id' => $c1->id, 'name' => 'Gedung FPTK A'],
            ['client_id' => $c1->id, 'name' => 'Gedung FPTK B'],
            ['client_id' => $c1->id, 'name' => 'Gedung Workshop'],
            ['client_id' => $c2->id, 'name' => 'Gedung FPIPS Utama'],
            ['client_id' => $c2->id, 'name' => 'Gedung Seminar FPIPS'],
            ['client_id' => $c3->id, 'name' => 'Gedung FPMIPA Tower'],
        ];

        foreach ($buildings as $b) {
            DB::table('buildings')->updateOrInsert(
                ['client_id' => $b['client_id'], 'name' => $b['name']],
                array_merge($b, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // =====================
        // 3. CLASSROOMS
        // =====================
        $classrooms = [
            // FPTK A
            ['building' => 'Gedung FPTK A',         'rooms' => ['Ruang 101', 'Ruang 102', 'Ruang 103', 'Lab Jaringan', 'Lab Elektronika']],
            // FPTK B
            ['building' => 'Gedung FPTK B',         'rooms' => ['Ruang 201', 'Ruang 202', 'Lab Komputer', 'Ruang Dosen']],
            // Workshop
            ['building' => 'Gedung Workshop',       'rooms' => ['Workshop Kayu', 'Workshop Logam', 'Workshop Listrik']],
            // FPIPS
            ['building' => 'Gedung FPIPS Utama',    'rooms' => ['Ruang 101', 'Ruang 102', 'Ruang Dosen', 'Perpustakaan Mini']],
            ['building' => 'Gedung Seminar FPIPS',  'rooms' => ['Aula Utama', 'Ruang Rapat']],
            // FPMIPA
            ['building' => 'Gedung FPMIPA Tower',   'rooms' => ['Lab Fisika', 'Lab Kimia', 'Lab Biologi', 'Ruang Kelas A', 'Ruang Kelas B']],
        ];

        foreach ($classrooms as $group) {
            $building = DB::table('buildings')->where('name', $group['building'])->first();
            if (!$building) continue;
            foreach ($group['rooms'] as $room) {
                DB::table('classrooms')->updateOrInsert(
                    ['building_id' => $building->id, 'name' => $room],
                    ['building_id' => $building->id, 'name' => $room, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // =====================
        // 4. NODES
        // =====================
        $roomIds = DB::table('classrooms')->pluck('id')->toArray();

        $nodes = [
            ['name' => 'Sensor Suhu & Kelembaban', 'chip' => 'ESP32',           'purpose' => 'Monitoring suhu dan kelembaban ruangan', 'reading' => '28.5°C / 65%',   'battery' => '85%', 'uptime' => '3d 4h 12m'],
            ['name' => 'Sensor Kualitas Udara',    'chip' => 'ESP8266',          'purpose' => 'Monitoring CO2 dan kualitas udara',      'reading' => '420 ppm CO2',    'battery' => '92%', 'uptime' => '1d 18h 5m'],
            ['name' => 'Sensor Cahaya',            'chip' => 'Arduino Nano 33',  'purpose' => 'Monitoring intensitas cahaya ruangan',   'reading' => '350 lux',        'battery' => '67%', 'uptime' => '7d 2h 30m'],
            ['name' => 'Sensor Gerak PIR',         'chip' => 'ESP32',            'purpose' => 'Deteksi kehadiran dan pergerakan',       'reading' => 'Motion detected','battery' => '78%', 'uptime' => '12h 44m'],
        ];

        foreach ($roomIds as $roomId) {
            foreach ($nodes as $n) {
                DB::table('nodes')->updateOrInsert(
                    ['classroom_id' => $roomId, 'name' => $n['name']],
                    [
                        'classroom_id'         => $roomId,
                        'name'                 => $n['name'],
                        'microcontroller_chip' => $n['chip'],
                        'purpose'              => $n['purpose'],
                        'sensor_reading'       => $n['reading'],
                        'battery'              => $n['battery'],
                        'uptime'               => $n['uptime'],
                        'last_status_at'       => now()->subMinutes(rand(1, 120)),
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]
                );
            }
        }

        $this->command->info('✅ Dummy data berhasil dibuat!');
        $this->command->info('   3 clients, 6 buildings, 23 classrooms, 92 nodes');
    }
}
