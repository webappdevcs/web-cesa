<?php

namespace Cesa\Payroll\Tests\Feature;

use Cesa\Payroll\Tests\PayrollTestCase;

class I18nTest extends PayrollTestCase
{
    public function test_payroll_record_relation_labels_are_translated(): void
    {
        app()->setLocale('en');

        $this->assertSame('Base Salary', __('payroll::app.resources.payroll_record.table.columns.base_salary'));
        $this->assertSame('Late Penalty', __('payroll::app.resources.payroll_record.table.columns.late_penalty'));
        $this->assertSame('View', __('filament-actions::view.single.label'));

        app()->setLocale('id');

        $this->assertSame('Gaji Pokok', __('payroll::app.resources.payroll_record.table.columns.base_salary'));
        $this->assertSame('Denda Keterlambatan', __('payroll::app.resources.payroll_record.table.columns.late_penalty'));
        $this->assertSame('Lihat', __('filament-actions::view.single.label'));
    }
}
