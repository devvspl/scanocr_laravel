<?php

namespace Database\Seeders;

use App\Models\ItemGroup;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // ── Item Groups ──────────────────────────────────────────────────────
        $groups = [
            ['name' => 'Electronics', 'description' => 'Electronic items and gadgets'],
            ['name' => 'Furniture', 'description' => 'Office and home furniture'],
            ['name' => 'Stationery', 'description' => 'Office supplies and stationery'],
            ['name' => 'Services', 'description' => 'Professional services'],
        ];

        foreach ($groups as $data) {
            ItemGroup::create(array_merge($data, ['is_active' => true, 'created_by' => 1]));
        }

        // ── Units of Measure ─────────────────────────────────────────────────
        $units = [
            ['name' => 'Piece', 'symbol' => 'Pcs', 'type' => 'quantity'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight'],
            ['name' => 'Gram', 'symbol' => 'g', 'type' => 'weight'],
            ['name' => 'Liter', 'symbol' => 'L', 'type' => 'volume'],
            ['name' => 'Meter', 'symbol' => 'm', 'type' => 'length'],
            ['name' => 'Box', 'symbol' => 'Box', 'type' => 'quantity'],
            ['name' => 'Set', 'symbol' => 'Set', 'type' => 'quantity'],
            ['name' => 'Hour', 'symbol' => 'hr', 'type' => 'time'],
        ];

        foreach ($units as $data) {
            UnitOfMeasure::create(array_merge($data, ['is_active' => true, 'created_by' => 1]));
        }

        // ── Products ─────────────────────────────────────────────────────────
        $electronicsId = ItemGroup::where('name', 'Electronics')->value('id');
        $furnitureId   = ItemGroup::where('name', 'Furniture')->value('id');
        $stationeryId  = ItemGroup::where('name', 'Stationery')->value('id');
        $servicesId    = ItemGroup::where('name', 'Services')->value('id');

        $pcsId = UnitOfMeasure::where('symbol', 'Pcs')->value('id');
        $setId = UnitOfMeasure::where('symbol', 'Set')->value('id');
        $boxId = UnitOfMeasure::where('symbol', 'Box')->value('id');
        $hrId  = UnitOfMeasure::where('symbol', 'hr')->value('id');

        $products = [
            [
                'code' => 'ELEC-001', 'name' => 'Laptop Dell Inspiron 15', 'description' => '15.6" FHD, i5, 8GB RAM, 512GB SSD',
                'type' => 'goods', 'item_group_id' => $electronicsId, 'unit_id' => $pcsId, 'hsn_sac' => '8471',
                'sale_price' => 55000.00, 'purchase_price' => 48000.00, 'tax_rate' => 18.00,
                'opening_stock' => 10, 'reorder_level' => 5, 'track_inventory' => true,
            ],
            [
                'code' => 'ELEC-002', 'name' => 'Wireless Mouse Logitech', 'description' => 'Ergonomic wireless mouse',
                'type' => 'goods', 'item_group_id' => $electronicsId, 'unit_id' => $pcsId, 'hsn_sac' => '8471',
                'sale_price' => 850.00, 'purchase_price' => 650.00, 'tax_rate' => 18.00,
                'opening_stock' => 50, 'reorder_level' => 20, 'track_inventory' => true,
            ],
            [
                'code' => 'FURN-001', 'name' => 'Office Chair Executive', 'description' => 'High-back ergonomic chair',
                'type' => 'goods', 'item_group_id' => $furnitureId, 'unit_id' => $pcsId, 'hsn_sac' => '9401',
                'sale_price' => 12500.00, 'purchase_price' => 9500.00, 'tax_rate' => 18.00,
                'opening_stock' => 15, 'reorder_level' => 5, 'track_inventory' => true,
            ],
            [
                'code' => 'FURN-002', 'name' => 'Office Desk 4ft', 'description' => 'Wooden office desk with drawers',
                'type' => 'goods', 'item_group_id' => $furnitureId, 'unit_id' => $pcsId, 'hsn_sac' => '9403',
                'sale_price' => 8500.00, 'purchase_price' => 6500.00, 'tax_rate' => 18.00,
                'opening_stock' => 8, 'reorder_level' => 3, 'track_inventory' => true,
            ],
            [
                'code' => 'STAT-001', 'name' => 'A4 Paper Ream', 'description' => '500 sheets per ream',
                'type' => 'goods', 'item_group_id' => $stationeryId, 'unit_id' => $boxId, 'hsn_sac' => '4802',
                'sale_price' => 350.00, 'purchase_price' => 280.00, 'tax_rate' => 12.00,
                'opening_stock' => 100, 'reorder_level' => 30, 'track_inventory' => true,
            ],
            [
                'code' => 'STAT-002', 'name' => 'Pen Set Blue', 'description' => 'Pack of 10 ballpoint pens',
                'type' => 'goods', 'item_group_id' => $stationeryId, 'unit_id' => $setId, 'hsn_sac' => '9608',
                'sale_price' => 120.00, 'purchase_price' => 85.00, 'tax_rate' => 12.00,
                'opening_stock' => 200, 'reorder_level' => 50, 'track_inventory' => true,
            ],
            [
                'code' => 'SERV-001', 'name' => 'IT Consulting', 'description' => 'Professional IT consulting services',
                'type' => 'service', 'item_group_id' => $servicesId, 'unit_id' => $hrId, 'hsn_sac' => '998314',
                'sale_price' => 2500.00, 'purchase_price' => 0.00, 'tax_rate' => 18.00,
                'opening_stock' => 0, 'reorder_level' => 0, 'track_inventory' => false,
            ],
            [
                'code' => 'SERV-002', 'name' => 'Software Development', 'description' => 'Custom software development',
                'type' => 'service', 'item_group_id' => $servicesId, 'unit_id' => $hrId, 'hsn_sac' => '998313',
                'sale_price' => 3000.00, 'purchase_price' => 0.00, 'tax_rate' => 18.00,
                'opening_stock' => 0, 'reorder_level' => 0, 'track_inventory' => false,
            ],
        ];

        foreach ($products as $data) {
            Product::create(array_merge($data, ['is_active' => true, 'created_by' => 1]));
        }
    }
}
