<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

class SatSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    LazyCollection::make(function () {
      $handle = fopen(Storage::url('public/unidad.csv'), 'r');

      while (($line = fgetcsv($handle, 4096)) !== false) {
        $dataString = implode(", ", $line);
        $row = explode(',', $dataString);
        yield $row;
      }

      fclose($handle);
    })
      ->skip(1)
      ->chunk(1000)
      ->each(function (LazyCollection $chunk) {
        $records = $chunk->map(function ($row) {
          return [
            "c_ClaveUnidad" => $row[0],
            "descripcion" => $row[1],
          ];
        })->toArray();

        DB::table('clave_unidads')->insert($records);
      });
    LazyCollection::make(function () {
      $handle = fopen(Storage::url('public/clave.csv'), 'r');

      while (($line = fgetcsv($handle, 4096)) !== false) {
        $dataString = implode(", ", $line);
        $row = explode(',', $dataString);
        yield $row;
      }

      fclose($handle);
    })
      ->skip(1)
      ->chunk(1000)
      ->each(function (LazyCollection $chunk) {
        $records = $chunk->map(function ($row) {
          return [
            "c_claveProdServ" => $row[0],
            "descripcion" => $row[1],
          ];
        })->toArray();

        DB::table('clave_prod_servicios')->insert($records);
      });
  }
}
