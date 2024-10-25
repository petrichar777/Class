<?php

namespace App\Exports;

use App\Models\competition_stars;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompetitionStarExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return competition_stars::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'competition_name',
            'registration_time',
            'materials',
            'status',
            'Certificate',
            'rejection_reason',
        ];
    }
}
