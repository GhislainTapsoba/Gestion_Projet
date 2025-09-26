<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;

class GenericExport implements WithMultipleSheets
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->data as $key => $rows) {
            $sheets[] = new class($rows, ucfirst($key)) implements FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                private $rows;
                private $title;

                public function __construct($rows, $title)
                {
                    $this->rows = $rows;
                    $this->title = $title;
                }

                public function array(): array
                {
                    if (empty($this->rows)) {
                        return [['Aucune donnée']];
                    }
                    return array_merge([array_keys($this->rows[0])], $this->rows);
                }

                public function title(): string
                {
                    return $this->title;
                }
            };
        }

        return $sheets;
    }
}
