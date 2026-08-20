<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class OrganizerReportExport implements FromArray
{
    public function __construct(private array $rows)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }
}
