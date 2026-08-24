<?php

namespace App\Exports;

use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

/**
 * Exports the tenant's CRM reference settings (customer types, priorities,
 * stages, procedures) as a single sheet so they can be recreated on import
 * and keep customer type_id/priority_id/stage_id/procedure_id references intact.
 */
class CrmSettingsExportSheet implements FromArray, WithHeadings, WithTitle, WithStrictNullComparison
{
    public function __construct(private int $ownerId) {}

    public function title(): string
    {
        return 'CRM Settings';
    }

    public function headings(): array
    {
        return ['kind', 'name', 'value', 'order', 'color', 'icon', 'is_active', 'description'];
    }

    public function array(): array
    {
        $rows = [];

        foreach (UserApiCustomerType::where('user_id', $this->ownerId)->get() as $t) {
            $rows[] = ['type', $t->name, $t->value, $t->order, $t->color, $t->icon, (int) $t->is_active, null];
        }

        foreach (UserApiCustomerPriority::where('user_id', $this->ownerId)->get() as $p) {
            $rows[] = ['priority', $p->name, $p->value, $p->order, $p->color, $p->icon, (int) $p->is_active, null];
        }

        foreach (UserApiCustomerStage::where('user_id', $this->ownerId)->get() as $s) {
            $rows[] = ['stage', $s->stage_name, null, $s->order, $s->color, $s->icon, (int) $s->is_active, $s->description];
        }

        foreach (UserApiCustomerProcedure::where('user_id', $this->ownerId)->get() as $pr) {
            $rows[] = ['procedure', $pr->procedure_name, null, $pr->order, $pr->color, $pr->icon, (int) $pr->is_active, $pr->description];
        }

        return $rows;
    }
}
