<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Cupboard;
use App\Models\Place;
use App\Models\Item;
use App\Models\Borrow;
use App\Models\AuditLog;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Users ──────────────────────────────────────────────
        $admin = User::create([
            'name'     => 'Ashan Fernando',
            'email'    => 'ashan@ceyntics.com',
            'password' => Hash::make('Admin@1234'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $staff1 = User::create([
            'name'     => 'Kasun Perera',
            'email'    => 'kasun@ceyntics.com',
            'password' => Hash::make('Staff@1234'),
            'role'     => 'staff',
            'status'   => 'active',
        ]);

        $staff2 = User::create([
            'name'     => 'Nimali Silva',
            'email'    => 'nimali@ceyntics.com',
            'password' => Hash::make('Staff@1234'),
            'role'     => 'staff',
            'status'   => 'active',
        ]);

        $staff3 = User::create([
            'name'     => 'Ruwan Jayasinghe',
            'email'    => 'ruwan@ceyntics.com',
            'password' => Hash::make('Staff@1234'),
            'role'     => 'staff',
            'status'   => 'active',
        ]);

        // ── 2. Cupboards ──────────────────────────────────────────
        $cupboardA = Cupboard::create([
            'name'        => 'Cupboard A',
            'code'        => 'CUP-A',
            'description' => 'Main electronics storage',
            'location'    => 'Lab Room 101',
            'color'       => '#6366f1',
            'bg_color'    => '#ede9fe',
        ]);

        $cupboardB = Cupboard::create([
            'name'        => 'Cupboard B',
            'code'        => 'CUP-B',
            'description' => 'Test & measurement equipment',
            'location'    => 'Lab Room 101',
            'color'       => '#10b981',
            'bg_color'    => '#d1fae5',
        ]);

        $cupboardC = Cupboard::create([
            'name'        => 'Cupboard C',
            'code'        => 'CUP-C',
            'description' => 'Hand tools & accessories',
            'location'    => 'Lab Room 102',
            'color'       => '#f59e0b',
            'bg_color'    => '#fef3c7',
        ]);

        // ── 3. Places ─────────────────────────────────────────────
        $a1 = Place::create(['cupboard_id' => $cupboardA->id, 'name' => 'Shelf 1', 'capacity' => 10]);
        $a2 = Place::create(['cupboard_id' => $cupboardA->id, 'name' => 'Shelf 2', 'capacity' => 10]);
        $a3 = Place::create(['cupboard_id' => $cupboardA->id, 'name' => 'Shelf 3', 'capacity' => 10]);

        $b1 = Place::create(['cupboard_id' => $cupboardB->id, 'name' => 'Shelf 1', 'capacity' => 8]);
        $b2 = Place::create(['cupboard_id' => $cupboardB->id, 'name' => 'Shelf 2', 'capacity' => 8]);
        $b3 = Place::create(['cupboard_id' => $cupboardB->id, 'name' => 'Shelf 3', 'capacity' => 8]);

        $c1 = Place::create(['cupboard_id' => $cupboardC->id, 'name' => 'Shelf 1', 'capacity' => 12]);
        $c2 = Place::create(['cupboard_id' => $cupboardC->id, 'name' => 'Shelf 2', 'capacity' => 12]);
        $c3 = Place::create(['cupboard_id' => $cupboardC->id, 'name' => 'Shelf 3', 'capacity' => 12]);

        // ── 4. Items ──────────────────────────────────────────────
        $solderingIron = Item::create([
            'place_id'    => $a1->id,
            'name'        => 'Soldering Iron',
            'code'        => 'EQ-001',
            'quantity'    => 5,
            'description' => '60W adjustable temperature soldering iron for PCB work.',
            'status'      => 'instore',
        ]);

        $oscilloscope = Item::create([
            'place_id'     => $b2->id,
            'name'         => 'Oscilloscope',
            'code'         => 'EQ-002',
            'quantity'     => 2,
            'serial_number'=> 'OS-2024-002',
            'description'  => '4-channel 100MHz digital oscilloscope for signal analysis.',
            'status'       => 'borrowed',
        ]);

        $arduinoMega = Item::create([
            'place_id'    => $a3->id,
            'name'        => 'Arduino Mega',
            'code'        => 'CM-003',
            'quantity'    => 12,
            'description' => 'Arduino Mega 2560 microcontroller board.',
            'status'      => 'instore',
        ]);

        $multimeter = Item::create([
            'place_id'     => $c1->id,
            'name'         => 'Multimeter',
            'code'         => 'EQ-004',
            'quantity'     => 1,
            'serial_number'=> 'MM-2023-004',
            'description'  => 'Digital multimeter for voltage, current and resistance measurement.',
            'status'       => 'damaged',
        ]);

        $raspberryPi = Item::create([
            'place_id'     => $b1->id,
            'name'         => 'Raspberry Pi 4',
            'code'         => 'CM-005',
            'quantity'     => 0,
            'serial_number'=> 'RP-2024-005',
            'description'  => 'Raspberry Pi 4 Model B 4GB single-board computer.',
            'status'       => 'missing',
        ]);

        $breadboard = Item::create([
            'place_id'    => $a2->id,
            'name'        => 'Breadboard',
            'code'        => 'CM-006',
            'quantity'    => 10,
            'description' => '830-point solderless breadboard for prototyping circuits.',
            'status'      => 'instore',
        ]);

        $wireStripper = Item::create([
            'place_id'    => $c2->id,
            'name'        => 'Wire Stripper',
            'code'        => 'TL-007',
            'quantity'    => 3,
            'description' => 'Automatic wire stripper and cutter for 10-24 AWG wire.',
            'status'      => 'instore',
        ]);

        $heatGun = Item::create([
            'place_id'     => $b3->id,
            'name'         => 'Heat Gun',
            'code'         => 'EQ-008',
            'quantity'     => 1,
            'serial_number'=> 'HG-2023-008',
            'description'  => 'Variable temperature heat gun for shrink tubing and rework.',
            'status'       => 'borrowed',
        ]);

        $logicAnalyzer = Item::create([
            'place_id'     => $a1->id,
            'name'         => 'Logic Analyzer',
            'code'         => 'EQ-009',
            'quantity'     => 2,
            'serial_number'=> 'LA-2024-009',
            'description'  => '8-channel USB logic analyzer for digital signal debugging.',
            'status'       => 'instore',
        ]);

        $powerSupply = Item::create([
            'place_id'     => $c3->id,
            'name'         => 'Power Supply',
            'code'         => 'EQ-010',
            'quantity'     => 2,
            'serial_number'=> 'PS-2024-010',
            'description'  => 'DC bench power supply 0-30V 0-5A with dual output.',
            'status'       => 'instore',
        ]);

        $esp32 = Item::create([
            'place_id'    => $a2->id,
            'name'        => 'ESP32 Module',
            'code'        => 'CM-011',
            'quantity'    => 8,
            'description' => 'ESP32 WiFi+Bluetooth microcontroller development board.',
            'status'      => 'instore',
        ]);

        $crimpingTool = Item::create([
            'place_id'    => $c1->id,
            'name'        => 'Crimping Tool',
            'code'        => 'TL-012',
            'quantity'    => 2,
            'description' => 'Ratcheting crimping tool for JST and Dupont connectors.',
            'status'      => 'instore',
        ]);

        // ── 5. Borrows ────────────────────────────────────────────
        Borrow::create([
            'item_id'              => $oscilloscope->id,
            'borrower_name'        => 'Ruwan Jayasinghe',
            'contact'              => '+94 77 555 6666',
            'quantity'             => 1,
            'borrow_date'          => '2026-02-20',
            'expected_return_date' => '2026-02-28',
            'actual_return_date'   => '2026-02-27',
            'return_condition'     => 'good',
            'status'               => 'returned',
            'processed_by'         => $staff3->id,
        ]);

        Borrow::create([
            'item_id'              => $solderingIron->id,
            'borrower_name'        => 'Kasun Perera',
            'contact'              => '+94 77 111 2222',
            'quantity'             => 1,
            'borrow_date'          => '2026-03-05',
            'expected_return_date' => '2026-03-12',
            'status'               => 'borrowed',
            'processed_by'         => $staff1->id,
        ]);

        Borrow::create([
            'item_id'              => $heatGun->id,
            'borrower_name'        => 'Nimali Silva',
            'contact'              => '+94 77 333 4444',
            'quantity'             => 1,
            'borrow_date'          => '2026-03-01',
            'expected_return_date' => '2026-03-08',
            'status'               => 'borrowed',
            'processed_by'         => $staff2->id,
        ]);

        // ── 6. Audit Logs ─────────────────────────────────────────
        // Seed some initial audit entries matching the seeded data
        $logs = [
            [AuditLog::USER_CREATED,    'User',     $staff1->id,        $staff1->name,        null,                                      ['name' => $staff1->name, 'role' => 'staff']],
            [AuditLog::USER_CREATED,    'User',     $staff2->id,        $staff2->name,        null,                                      ['name' => $staff2->name, 'role' => 'staff']],
            [AuditLog::CUPBOARD_CREATED,'Cupboard', $cupboardA->id,     $cupboardA->name,     null,                                      ['name' => 'Cupboard A',  'code' => 'CUP-A']],
            [AuditLog::CUPBOARD_CREATED,'Cupboard', $cupboardB->id,     $cupboardB->name,     null,                                      ['name' => 'Cupboard B',  'code' => 'CUP-B']],
            [AuditLog::CUPBOARD_CREATED,'Cupboard', $cupboardC->id,     $cupboardC->name,     null,                                      ['name' => 'Cupboard C',  'code' => 'CUP-C']],
            [AuditLog::ITEM_CREATED,    'Item',     $solderingIron->id, $solderingIron->name, null,                                      ['code' => 'EQ-001', 'quantity' => 5,  'status' => 'instore']],
            [AuditLog::ITEM_CREATED,    'Item',     $arduinoMega->id,   $arduinoMega->name,   null,                                      ['code' => 'CM-003', 'quantity' => 12, 'status' => 'instore']],
            [AuditLog::ITEM_BORROWED,   'Item',     $oscilloscope->id,  $oscilloscope->name,  ['quantity' => 3, 'status' => 'instore'],  ['quantity' => 2, 'status' => 'borrowed', 'borrower' => 'Ruwan Jayasinghe']],
            [AuditLog::ITEM_RETURNED,   'Item',     $oscilloscope->id,  $oscilloscope->name,  ['quantity' => 2, 'status' => 'borrowed'], ['quantity' => 3, 'status' => 'instore',  'condition' => 'good']],
            [AuditLog::ITEM_BORROWED,   'Item',     $solderingIron->id, $solderingIron->name, ['quantity' => 6, 'status' => 'instore'],  ['quantity' => 5, 'status' => 'borrowed', 'borrower' => 'Kasun Perera']],
            [AuditLog::ITEM_STATUS_CHANGED,'Item',  $multimeter->id,    $multimeter->name,    ['status' => 'instore'],                   ['status' => 'damaged']],
            [AuditLog::ITEM_STATUS_CHANGED,'Item',  $raspberryPi->id,   $raspberryPi->name,   ['status' => 'instore'],                   ['status' => 'missing']],
        ];

        foreach ($logs as [$action, $entityType, $entityId, $entityName, $prev, $new]) {
            AuditLog::record(
                action:        $action,
                entityType:    $entityType,
                entityId:      $entityId,
                entityName:    $entityName,
                previousValue: $prev,
                newValue:      $new,
                userId:        $admin->id,
            );
        }
    }
}
