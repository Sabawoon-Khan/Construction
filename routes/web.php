<?php

use App\Http\Controllers\Admin\FieldNoteController;
use App\Http\Controllers\Admin\FieldReportController;
use App\Http\Controllers\AiAnalyticsController;
use App\Http\Controllers\AiAssistController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseUnitController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

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
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');

    Route::patch('{resource}/{id}/status', [StatusController::class, 'update'])
        ->whereIn('resource', ['projects', 'units', 'sales', 'payments', 'expenses', 'incomes'])
        ->name('status.update');

    Route::inertia('dev/gallery', 'dev/ComponentGallery')
        ->middleware('role:Owner')
        ->name('dev.gallery');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::resource('field-reports', FieldReportController::class)
        ->parameters(['field-reports' => 'fieldReport'])
        ->except(['show']);

    Route::resource('field-notes', FieldNoteController::class)
        ->parameters(['field-notes' => 'fieldNote'])
        ->except(['show']);

    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/expenses', [ProjectController::class, 'storeExpense'])
        ->middleware('permission:expenses.create')
        ->name('projects.expenses.store');
    Route::post('projects/{project}/shareholders', [ProjectController::class, 'storeShareholder'])
        ->middleware('permission:partners.create')
        ->name('projects.shareholders.store');
    Route::post('projects/{project}/shareholders/{partner}/contributions', [ProjectController::class, 'storeShareholderContribution'])
        ->middleware('permission:partners.edit')
        ->name('projects.shareholders.contributions.store');
    Route::post('projects/{project}/shareholders/{partner}/payouts', [ProjectController::class, 'storeShareholderPayout'])
        ->middleware('permission:partners.edit')
        ->name('projects.shareholders.payouts.store');
    Route::get('projects/{project}/contributions/{investment}', [ProjectController::class, 'showContribution'])
        ->middleware('permission:projects.view')
        ->name('projects.contributions.show');
    Route::resource('units', UnitController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('partners', PartnerController::class);
    Route::resource('expenses', ExpenseController::class);
    Route::resource('expense-units', ExpenseUnitController::class)->except(['create', 'edit', 'show']);
    Route::resource('incomes', IncomeController::class);
    Route::resource('sales', SaleController::class);
    Route::post('sales/{sale}/installments/{installment}/pay', [SaleController::class, 'payInstallment'])
        ->middleware('permission:sales.edit')
        ->name('sales.installments.pay');
    Route::resource('payments', PaymentController::class)->only(['index', 'show']);

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
