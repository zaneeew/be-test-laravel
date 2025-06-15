<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TodoExport implements FromCollection, WithHeadings
{
    protected $data;
    public function __construct($data) { $this->data = $data; }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['title', 'assignee', 'due_date', 'time_tracked', 'status', 'priority'];
    }
}
