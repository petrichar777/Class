<?php

namespace App\Exports;

use App\Models\software_stars;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SoftwareStarExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return software_stars::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'software_name',
            'issuing_unit',
            'ranking_total',
            'approval_time',
            'materials',
            'status',
            'rejection_reason',
        ];
    }
}
