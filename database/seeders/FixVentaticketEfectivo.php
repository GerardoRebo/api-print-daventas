<?php

namespace Database\Seeders;

use App\Models\Ventaticket;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FixVentaticketEfectivo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ventatickets = Ventaticket::whereDate('created_at', '>', '2024-09-28 ')->get();
        foreach ($ventatickets as $ventaticket) {
            $ventaticket->fp_efectivo = $ventaticket->total;
            $ventaticket->save();
        }
    }
}
