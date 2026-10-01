<?php

use App\Http\Controllers\Admin\FieldReportController;
use App\Http\Controllers\AiAnalyticsController;
use App\Http\Controllers\AiAssistController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BalanceSheetController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseUnitController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialUnitController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PortfolioProjectController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicPortfolioController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('contact.store');
Route::get('/work', [PublicPortfolioController::class, 'index'])
    ->name('website.portfolio.index');
Route::get('/portfolio/{slug}', [PublicPortfolioController::class, 'show'])
    ->where('slug', '^(?!create$)[A-Za-z0-9\-]+$')
    ->name('website.portfolio.show');

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::redirect('dashboard-v2', '/dashboard')->name('dashboard.v2');
    Route::redirect('projects-v2', '/projects')->name('projects.v2');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');

    Route::get('balance-sheet', [BalanceSheetController::class, 'index'])->name('balance-sheet.index');
    Route::get('balance-sheet/export', [BalanceSheetController::class, 'export'])->name('balance-sheet.export');
    Route::get('balance-sheet/print', [BalanceSheetController::class, 'print'])->name('balance-sheet.print');

    Route::patch('{resource}/{id}/status', [StatusController::class, 'update'])
        ->whereIn('resource', ['projects', 'units', 'sales', 'payments', 'expenses', 'incomes'])
        ->name('status.update');

    Route::inertia('dev/gallery', 'dev/ComponentGallery')
        ->middleware('role:Owner')
        ->name('dev.gallery');

    Route::get('/search', GlobalSearchController::class)->name('search');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::get('field-reports/export', [FieldReportController::class, 'export'])->name('field-reports.export');
    Route::resource('field-reports', FieldReportController::class)
        ->parameters(['field-reports' => 'fieldReport'])
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy', 'show']);

    Route::get('projects/export', [ProjectController::class, 'export'])->name('projects.export');
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/expenses', [ProjectController::class, 'storeExpense'])
        ->middleware('permission:expenses.create')
        ->name('projects.expenses.store');
    Route::get('projects/{project}/expenses/print', [ProjectController::class, 'printExpenses'])
        ->middleware('permission:projects.view')
        ->name('projects.expenses.print');
    Route::post('projects/{project}/shareholders', [ProjectController::class, 'storeShareholder'])
        ->middleware('permission:partners.create')
        ->name('projects.shareholders.store');
    Route::post('projects/{project}/employees', [ProjectController::class, 'storeEmployee'])
        ->middleware('permission:employees.edit')
        ->name('projects.employees.store');
    Route::delete('projects/{project}/employees/{employee}', [ProjectController::class, 'destroyEmployee'])
        ->middleware('permission:employees.edit')
        ->name('projects.employees.destroy');
    Route::post('projects/{project}/shareholders/{partner}/contributions', [ProjectController::class, 'storeShareholderContribution'])
        ->middleware('permission:partners.edit')
        ->name('projects.shareholders.contributions.store');
    Route::post('projects/{project}/shareholders/{partner}/payouts', [ProjectController::class, 'storeShareholderPayout'])
        ->middleware('permission:partners.edit')
        ->name('projects.shareholders.payouts.store');
    Route::get('projects/{project}/contributions/{investment}', [ProjectController::class, 'showContribution'])
        ->middleware('permission:projects.view')
        ->name('projects.contributions.show');
    Route::post('projects/{project}/purchases/{purchase}/pay-remaining', [ProjectController::class, 'payPurchaseRemaining'])
        ->middleware('permission:purchases.edit')
        ->name('projects.purchases.payRemaining');
    Route::post('projects/{project}/loans', [ProjectController::class, 'storeLoan'])
        ->middleware('permission:loans.create')
        ->name('projects.loans.store');
    Route::post('projects/{project}/loans/{loan}/pay', [ProjectController::class, 'payLoan'])
        ->middleware('permission:loans.edit')
        ->name('projects.loans.pay');
    Route::get('units/export', [UnitController::class, 'export'])->name('units.export');
    Route::resource('units', UnitController::class);
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::resource('customers', CustomerController::class);
    Route::get('partners/export', [PartnerController::class, 'export'])->name('partners.export');
    Route::resource('partners', PartnerController::class);
    Route::get('suppliers/export', [SupplierController::class, 'export'])->name('suppliers.export');
    Route::resource('suppliers', SupplierController::class);
    Route::get('materials/export', [MaterialController::class, 'export'])->name('materials.export');
    Route::resource('materials', MaterialController::class)->except(['show']);
    Route::get('material-units/export', [MaterialUnitController::class, 'export'])->name('material-units.export');
    Route::resource('material-units', MaterialUnitController::class)->except(['create', 'edit', 'show']);
    Route::get('portfolio/export', [PortfolioProjectController::class, 'export'])->name('portfolio.export');
    Route::resource('portfolio', PortfolioProjectController::class)
        ->parameters(['portfolio' => 'portfolio'])
        ->except(['show']);
    Route::get('contact-messages/export', [ContactMessageController::class, 'export'])->name('contact-messages.export');
    Route::resource('contact-messages', ContactMessageController::class)
        ->only(['index', 'show', 'destroy']);
    Route::get('purchases/export', [PurchaseController::class, 'export'])->name('purchases.export');
    Route::resource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/pay', [PurchaseController::class, 'pay'])
        ->name('purchases.pay');
    Route::get('inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::resource('inventory', InventoryController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['inventory' => 'inventoryTransaction']);
    Route::get('expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::resource('expenses', ExpenseController::class);
    Route::get('expense-categories/export', [ExpenseCategoryController::class, 'export'])->name('expense-categories.export');
    Route::resource('expense-categories', ExpenseCategoryController::class)->except(['create', 'edit', 'show']);
    Route::get('expense-units/export', [ExpenseUnitController::class, 'export'])->name('expense-units.export');
    Route::resource('expense-units', ExpenseUnitController::class)->except(['create', 'edit', 'show']);
    Route::get('incomes/export', [IncomeController::class, 'export'])->name('incomes.export');
    Route::resource('incomes', IncomeController::class);
    Route::get('sales/export', [SaleController::class, 'export'])->name('sales.export');
    Route::resource('sales', SaleController::class);
    Route::post('sales/{sale}/installments/{installment}/pay', [SaleController::class, 'payInstallment'])
        ->middleware('permission:sales.edit')
        ->name('sales.installments.pay');
    Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::resource('payments', PaymentController::class)->only(['index', 'show']);
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::resource('employees', EmployeeController::class);
    Route::get('attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
    Route::resource('attendance', AttendanceController::class);
    Route::get('salaries/export', [SalaryController::class, 'export'])->name('salaries.export');
    Route::resource('salaries', SalaryController::class);

    Route::post('/ai/assist', AiAssistController::class)
        ->middleware('throttle:20,1')
        ->name('ai.assist');

    Route::get('/ai/analytics', [AiAnalyticsController::class, 'index'])
        ->name('ai.analytics');

    Route::post('/ai/analytics/generate', [AiAnalyticsController::class, 'generate'])
        ->middleware('throttle:10,1')
        ->name('ai.analytics.generate');

    Route::post('/ai/chat', [AiChatController::class, 'chat'])
        ->middleware('throttle:20,1')
        ->name('ai.chat');

    Route::post('/ai/chat/approve', [AiChatController::class, 'approve'])
        ->middleware('throttle:20,1')
        ->name('ai.chat.approve');

    Route::get('/ai/chat/conversations', [AiChatController::class, 'conversations'])
        ->middleware('throttle:30,1')
        ->name('ai.chat.conversations');

    Route::get('/ai/chat/conversations/{conversation}', [AiChatController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('ai.chat.conversations.show');

    Route::delete('/ai/chat/conversations/{conversation}', [AiChatController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('ai.chat.conversations.destroy');
});

require __DIR__.'/settings.php';

Route::get('/offline', function () {
    return response()->view('offline');
})->name('offline');
