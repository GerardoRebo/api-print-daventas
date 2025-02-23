<?php

namespace App\MyClasses\Excel;


class ExcelFile
{
    private function isNull($value){
        if ($value === '') {
            return null;
        }
        return $value;
    }
    public function formatValues($records){
       foreach ($records as $key=>$value) {
          $records[$key]= self::isNull($value);
       } 
       return $records;
    }

}    