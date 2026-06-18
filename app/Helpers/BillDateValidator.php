<?php

namespace App\Helpers;

use App\Models\FinancialYear;
use Illuminate\Validation\ValidationException;

/**
 * Bill Date Validation Helper
 * 
 * Provides centralized validation for bill_date fields against the active financial year.
 */
class BillDateValidator
{
    /**
     * Returns the validation rules for bill_date scoped to the active FY.
     *
     * @throws \Illuminate\Validation\ValidationException if no financial year is configured
     * @return array
     */
    public static function rules(): array
    {
        $fy = FinancialYear::getCurrent();

        if (! $fy) {
            throw ValidationException::withMessages([
                'bill_date' => 'No active financial year is configured. Please contact your administrator.',
            ]);
        }

        // Carbon date casts — format as Y-m-d for Laravel's after/before rules
        $from = $fy->start_date->format('Y-m-d');
        $to   = $fy->end_date->format('Y-m-d');

        return [
            'bill_date' => [
                'required',
                'date',
                "after_or_equal:{$from}",
                "before_or_equal:{$to}",
            ],
        ];
    }

    /**
     * Returns a human-readable FY range string for error messages.
     *
     * @return string
     */
    public static function fyRangeLabel(): string
    {
        $fy = FinancialYear::getCurrent();
        if (! $fy) {
            return 'the active financial year';
        }

        return $fy->start_date->format('d M Y') . ' – ' . $fy->end_date->format('d M Y')
            . ' (' . $fy->label . ')';
    }

    /**
     * Get the current financial year date range for display purposes.
     *
     * @return array{start: string, end: string, label: string}|null
     */
    public static function getCurrentFyRange(): ?array
    {
        $fy = FinancialYear::getCurrent();
        
        if (! $fy) {
            return null;
        }

        return [
            'start' => $fy->start_date->format('Y-m-d'),
            'end' => $fy->end_date->format('Y-m-d'),
            'label' => $fy->label,
        ];
    }
}
