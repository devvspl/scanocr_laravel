<?php

namespace Database\Seeders;

use App\Models\TaxRate;
use App\Models\HsnCode;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        // ── Tax Rates ──────────────────────────────────────────────────────────

        $taxes = [
            ['name' => 'GST 0%',   'code' => 'GST0',   'type' => 'gst',  'rate' => 0,    'cgst' => 0,    'sgst' => 0,    'igst' => 0],
            ['name' => 'GST 5%',   'code' => 'GST5',   'type' => 'gst',  'rate' => 5,    'cgst' => 2.5,  'sgst' => 2.5,  'igst' => 5],
            ['name' => 'GST 12%',  'code' => 'GST12',  'type' => 'gst',  'rate' => 12,   'cgst' => 6,    'sgst' => 6,    'igst' => 12],
            ['name' => 'GST 18%',  'code' => 'GST18',  'type' => 'gst',  'rate' => 18,   'cgst' => 9,    'sgst' => 9,    'igst' => 18],
            ['name' => 'GST 28%',  'code' => 'GST28',  'type' => 'gst',  'rate' => 28,   'cgst' => 14,   'sgst' => 14,   'igst' => 28],
            ['name' => 'IGST 5%',  'code' => 'IGST5',  'type' => 'igst', 'rate' => 5,    'cgst' => 0,    'sgst' => 0,    'igst' => 5],
            ['name' => 'IGST 12%', 'code' => 'IGST12', 'type' => 'igst', 'rate' => 12,   'cgst' => 0,    'sgst' => 0,    'igst' => 12],
            ['name' => 'IGST 18%', 'code' => 'IGST18', 'type' => 'igst', 'rate' => 18,   'cgst' => 0,    'sgst' => 0,    'igst' => 18],
            ['name' => 'IGST 28%', 'code' => 'IGST28', 'type' => 'igst', 'rate' => 28,   'cgst' => 0,    'sgst' => 0,    'igst' => 28],
            ['name' => 'Cess 1%',  'code' => 'CESS1',  'type' => 'cess', 'rate' => 1,    'cgst' => 0,    'sgst' => 0,    'igst' => 0],
            ['name' => 'TDS 1%',   'code' => 'TDS1',   'type' => 'tds',  'rate' => 1,    'cgst' => 0,    'sgst' => 0,    'igst' => 0],
            ['name' => 'TDS 2%',   'code' => 'TDS2',   'type' => 'tds',  'rate' => 2,    'cgst' => 0,    'sgst' => 0,    'igst' => 0],
            ['name' => 'TDS 10%',  'code' => 'TDS10',  'type' => 'tds',  'rate' => 10,   'cgst' => 0,    'sgst' => 0,    'igst' => 0],
            ['name' => 'TCS 1%',   'code' => 'TCS1',   'type' => 'tcs',  'rate' => 1,    'cgst' => 0,    'sgst' => 0,    'igst' => 0],
        ];

        foreach ($taxes as $tax) {
            TaxRate::firstOrCreate(
                ['code' => $tax['code']],
                array_merge($tax, ['is_active' => true, 'created_by' => 1])
            );
        }

        // ── HSN / SAC Codes ────────────────────────────────────────────────────

        $gst5  = TaxRate::where('code', 'GST5')->value('id');
        $gst12 = TaxRate::where('code', 'GST12')->value('id');
        $gst18 = TaxRate::where('code', 'GST18')->value('id');
        $gst28 = TaxRate::where('code', 'GST28')->value('id');
        $gst0  = TaxRate::where('code', 'GST0')->value('id');

        $hsnCodes = [
            // ── Goods (HSN) ──
            ['code' => '0101',   'type' => 'hsn', 'description' => 'Live horses, asses, mules and hinnies',                    'tax_rate_id' => $gst0],
            ['code' => '0201',   'type' => 'hsn', 'description' => 'Meat of bovine animals, fresh or chilled',                 'tax_rate_id' => $gst0],
            ['code' => '1001',   'type' => 'hsn', 'description' => 'Wheat and meslin',                                         'tax_rate_id' => $gst0],
            ['code' => '1006',   'type' => 'hsn', 'description' => 'Rice',                                                     'tax_rate_id' => $gst0],
            ['code' => '2201',   'type' => 'hsn', 'description' => 'Waters, including natural or artificial mineral waters',   'tax_rate_id' => $gst5],
            ['code' => '2202',   'type' => 'hsn', 'description' => 'Waters with added sugar, flavoured or other beverages',    'tax_rate_id' => $gst12],
            ['code' => '3004',   'type' => 'hsn', 'description' => 'Medicaments for therapeutic or prophylactic uses',         'tax_rate_id' => $gst12],
            ['code' => '3401',   'type' => 'hsn', 'description' => 'Soap, organic surface-active products for washing',        'tax_rate_id' => $gst18],
            ['code' => '4901',   'type' => 'hsn', 'description' => 'Printed books, brochures, leaflets',                       'tax_rate_id' => $gst0],
            ['code' => '6101',   'type' => 'hsn', 'description' => 'Mens overcoats, car-coats, cloaks and similar articles',   'tax_rate_id' => $gst5],
            ['code' => '6201',   'type' => 'hsn', 'description' => 'Mens overcoats, car-coats, anoraks and similar articles',  'tax_rate_id' => $gst5],
            ['code' => '7108',   'type' => 'hsn', 'description' => 'Gold (including gold plated with platinum)',                'tax_rate_id' => $gst5],
            ['code' => '8471',   'type' => 'hsn', 'description' => 'Automatic data processing machines and units thereof',     'tax_rate_id' => $gst18],
            ['code' => '8517',   'type' => 'hsn', 'description' => 'Telephone sets, smartphones and other apparatus',          'tax_rate_id' => $gst18],
            ['code' => '8703',   'type' => 'hsn', 'description' => 'Motor cars and other motor vehicles for persons',          'tax_rate_id' => $gst28],
            ['code' => '9403',   'type' => 'hsn', 'description' => 'Other furniture and parts thereof',                        'tax_rate_id' => $gst18],
            ['code' => '9503',   'type' => 'hsn', 'description' => 'Tricycles, scooters, pedal cars and similar wheeled toys', 'tax_rate_id' => $gst12],

            // ── Services (SAC) ──
            ['code' => '9954',   'type' => 'sac', 'description' => 'Construction services',                                    'tax_rate_id' => $gst12],
            ['code' => '9961',   'type' => 'sac', 'description' => 'Services in wholesale trade',                              'tax_rate_id' => $gst18],
            ['code' => '9962',   'type' => 'sac', 'description' => 'Services in retail trade',                                 'tax_rate_id' => $gst18],
            ['code' => '9971',   'type' => 'sac', 'description' => 'Financial and related services',                           'tax_rate_id' => $gst18],
            ['code' => '9972',   'type' => 'sac', 'description' => 'Real estate services',                                     'tax_rate_id' => $gst18],
            ['code' => '9973',   'type' => 'sac', 'description' => 'Leasing or rental services without operator',              'tax_rate_id' => $gst18],
            ['code' => '9981',   'type' => 'sac', 'description' => 'Research and development services',                        'tax_rate_id' => $gst18],
            ['code' => '9982',   'type' => 'sac', 'description' => 'Legal and accounting services',                            'tax_rate_id' => $gst18],
            ['code' => '9983',   'type' => 'sac', 'description' => 'Other professional, technical and business services',      'tax_rate_id' => $gst18],
            ['code' => '9984',   'type' => 'sac', 'description' => 'Telecommunications, broadcasting and information services','tax_rate_id' => $gst18],
            ['code' => '9985',   'type' => 'sac', 'description' => 'Support services',                                         'tax_rate_id' => $gst18],
            ['code' => '9986',   'type' => 'sac', 'description' => 'Agriculture, forestry, fishing and related services',      'tax_rate_id' => $gst0],
            ['code' => '9987',   'type' => 'sac', 'description' => 'Maintenance, repair and installation services',            'tax_rate_id' => $gst18],
            ['code' => '9988',   'type' => 'sac', 'description' => 'Manufacturing services on physical inputs owned by others','tax_rate_id' => $gst5],
            ['code' => '9993',   'type' => 'sac', 'description' => 'Human health and social care services',                    'tax_rate_id' => $gst0],
            ['code' => '9995',   'type' => 'sac', 'description' => 'Services of membership organisations',                     'tax_rate_id' => $gst18],
            ['code' => '9996',   'type' => 'sac', 'description' => 'Recreational, cultural and sporting services',             'tax_rate_id' => $gst18],
            ['code' => '9997',   'type' => 'sac', 'description' => 'Other services',                                           'tax_rate_id' => $gst18],
            ['code' => '998314', 'type' => 'sac', 'description' => 'IT design and development services',                       'tax_rate_id' => $gst18],
            ['code' => '998315', 'type' => 'sac', 'description' => 'IT infrastructure provisioning services',                  'tax_rate_id' => $gst18],
            ['code' => '998316', 'type' => 'sac', 'description' => 'IT technical support services',                            'tax_rate_id' => $gst18],
        ];

        foreach ($hsnCodes as $hsn) {
            HsnCode::firstOrCreate(
                ['code' => $hsn['code']],
                array_merge($hsn, ['is_active' => true, 'created_by' => 1])
            );
        }
    }
}
