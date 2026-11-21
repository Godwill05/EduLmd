<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RecapAnnuelExport implements FromCollection,ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }
    public function collection()
    {
        return collect($this->data);
    }
}
