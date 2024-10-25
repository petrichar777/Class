<?php

namespace App\Exports;

use App\Models\company_stars;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompanyStarExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return company_stars::all();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'company_name',
            'company_type',
            'applicant_rank',
            'registration_time',
            'company_scale',
            'materials',
            'Status',
            'rejection_reason',
        ];
    }
}
