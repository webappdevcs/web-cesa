<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\CustomerPanelProvider::class,

    // Webkul core plugins.
    Webkul\Analytic\AnalyticServiceProvider::class,
    Webkul\Chatter\ChatterServiceProvider::class,
    Webkul\Field\FieldServiceProvider::class,
    Webkul\Partner\PartnerServiceProvider::class,
    Webkul\Security\SecurityServiceProvider::class,
    Webkul\Support\SupportServiceProvider::class,
    Webkul\TableViews\TableViewsServiceProvider::class,
    Webkul\FullCalendar\FullCalendarServiceProvider::class,
    Webkul\PluginManager\PluginManagerServiceProvider::class,

    // Webkul non-core plugins.
    // Webkul\Accounting\AccountingServiceProvider::class,
    // Webkul\Account\AccountServiceProvider::class,
    // Webkul\Blog\BlogServiceProvider::class,
    // Webkul\Contact\ContactServiceProvider::class,
    // Webkul\Inventory\InventoryServiceProvider::class,
    // Webkul\Invoice\InvoiceServiceProvider::class,
    // Webkul\Payment\PaymentServiceProvider::class,
    // Webkul\Product\ProductServiceProvider::class,
    // Webkul\Project\ProjectServiceProvider::class,
    // Webkul\Purchase\PurchaseServiceProvider::class,
    // Webkul\Recruitment\RecruitmentServiceProvider::class,
    // Webkul\Sale\SaleServiceProvider::class,
    // Webkul\TimeOff\TimeOffServiceProvider::class,
    // Webkul\Timesheet\TimesheetServiceProvider::class,
    // Webkul\Website\WebsiteServiceProvider::class,

    Cesa\Rekrutmen\RekrutmenServiceProvider::class,
    Cesa\LegacySync\LegacySyncServiceProvider::class,
    Cesa\Presensi\PresensiServiceProvider::class,
    Cesa\Payroll\PayrollServiceProvider::class,
    Cesa\FormTransfer\FormTransferServiceProvider::class,
    Cesa\ExitClearance\ExitClearanceServiceProvider::class,
    Cesa\Kepegawaian\KepegawaianServiceProvider::class,
    Cesa\Helpdesk\HelpdeskServiceProvider::class,
    Cesa\Shelf\ShelfServiceProvider::class,
];
