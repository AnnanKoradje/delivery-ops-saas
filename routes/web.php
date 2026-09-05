<?php

use App\Http\Controllers\AssignedDeliveryController;
use App\Http\Controllers\CompanyAdminInvitationController;
use App\Http\Controllers\CompanyApplicationController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\CompanyWorkspaceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPortalAuthController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\CustomerPortalInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DeliveryPersonnelController;
use App\Http\Controllers\DeliveryProofController;
use App\Http\Controllers\DeliveryTrackingEmailController;
use App\Http\Controllers\DeliveryTrackingLinkController;
use App\Http\Controllers\DispatcherOperationsController;
use App\Http\Controllers\PlatformCompanyController;
use App\Http\Controllers\PlatformDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicTrackingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffInvitationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/track', [PublicTrackingController::class, 'create'])->middleware('throttle:20,1')->name('tracking.lookup');
Route::post('/track', [PublicTrackingController::class, 'lookup'])->middleware('throttle:10,1')->name('tracking.lookup.store');
Route::get('/track/{token}', [PublicTrackingController::class, 'show'])->middleware('throttle:20,1')->name('tracking.show');

Route::middleware('throttle:5,1')->group(function (): void {
    Route::get('/apply', [CompanyApplicationController::class, 'create'])->name('onboarding.application.create');
    Route::post('/apply', [CompanyApplicationController::class, 'store'])->name('onboarding.application.store');
    Route::get('/application-submitted', [CompanyApplicationController::class, 'submitted'])->name('onboarding.application.submitted');
});
Route::middleware('guest')->group(function (): void {
    Route::get('/accept-invitation/{token}', [CompanyAdminInvitationController::class, 'show'])->name('onboarding.invitation.show');
    Route::post('/accept-invitation/{token}', [CompanyAdminInvitationController::class, 'store'])->name('onboarding.invitation.store');
    Route::get('/accept-staff-invitation/{token}', [StaffInvitationController::class, 'show'])->name('tenant.staff-invitation.show');
    Route::post('/accept-staff-invitation/{token}', [StaffInvitationController::class, 'store'])->name('tenant.staff-invitation.store');
});

Route::middleware('guest:customer')->prefix('portal')->group(function (): void {
    Route::get('/login', [CustomerPortalAuthController::class, 'create'])->name('portal.login');
    Route::post('/login', [CustomerPortalAuthController::class, 'store'])->middleware('throttle:5,1')->name('portal.login.store');
    Route::get('/accept-invitation/{token}', [CustomerPortalInvitationController::class, 'show'])->name('portal.invitation.show');
    Route::post('/accept-invitation/{token}', [CustomerPortalInvitationController::class, 'store'])->middleware('throttle:5,1')->name('portal.invitation.store');
});

Route::middleware('auth:customer')->prefix('portal')->group(function (): void {
    Route::get('/deliveries', [CustomerPortalController::class, 'index'])->name('portal.deliveries.index');
    Route::get('/deliveries/{delivery}', [CustomerPortalController::class, 'show'])->name('portal.deliveries.show');
    Route::get('/profile', [CustomerPortalController::class, 'editProfile'])->name('portal.profile.edit');
    Route::patch('/profile', [CustomerPortalController::class, 'updateProfile'])->name('portal.profile.update');
    Route::post('/logout', [CustomerPortalAuthController::class, 'destroy'])->name('portal.logout');
});

Route::middleware(['auth', 'staff.active', 'company.context'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/platform', PlatformDashboardController::class)
        ->middleware('can:view-platform-dashboard')
        ->name('platform.dashboard');

    Route::middleware('can:manage-platform-companies')->prefix('platform/companies')->group(function (): void {
        Route::get('/', [PlatformCompanyController::class, 'index'])->name('platform.companies.index');
        Route::get('/create', [PlatformCompanyController::class, 'create'])->name('platform.companies.create');
        Route::post('/', [PlatformCompanyController::class, 'store'])->name('platform.companies.store');
        Route::get('/{company}', [PlatformCompanyController::class, 'show'])->name('platform.companies.show');
        Route::post('/{company}/approve', [PlatformCompanyController::class, 'approve'])->name('platform.companies.approve');
        Route::post('/{company}/reject', [PlatformCompanyController::class, 'reject'])->name('platform.companies.reject');
        Route::post('/{company}/suspend', [PlatformCompanyController::class, 'suspend'])->name('platform.companies.suspend');
        Route::post('/{company}/reactivate', [PlatformCompanyController::class, 'reactivate'])->name('platform.companies.reactivate');
    });

    Route::middleware('tenant')->prefix('workspace')->group(function (): void {
        Route::get('/settings/{companySetting}', [CompanySettingController::class, 'show'])
            ->name('tenant.settings.show');
        Route::patch('/settings/{companySetting}', [CompanySettingController::class, 'update'])
            ->name('tenant.settings.update');
        Route::get('/profile', [CompanyWorkspaceController::class, 'edit'])->name('tenant.profile.edit');
        Route::patch('/profile', [CompanyWorkspaceController::class, 'update'])->name('tenant.profile.update');
        Route::get('/staff', [StaffController::class, 'index'])->name('tenant.staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('tenant.staff.store');
        Route::post('/staff/{staff}/activate', [StaffController::class, 'activate'])->name('tenant.staff.activate');
        Route::post('/staff/{staff}/deactivate', [StaffController::class, 'deactivate'])->name('tenant.staff.deactivate');
        Route::get('/personnel', [DeliveryPersonnelController::class, 'index'])->name('tenant.personnel.index');
        Route::post('/personnel', [DeliveryPersonnelController::class, 'store'])->name('tenant.personnel.store');
        Route::patch('/personnel/{deliveryPersonnel}/status', [DeliveryPersonnelController::class, 'updateStatus'])->name('tenant.personnel.status');
        Route::get('/proof-files/{deliveryProofFile}', [DeliveryProofController::class, 'show'])->name('tenant.proof-files.show');

        Route::middleware('can:manage-operations')->group(function (): void {
            Route::get('/customers', [CustomerController::class, 'index'])->name('tenant.customers.index');
            Route::post('/customers', [CustomerController::class, 'store'])->name('tenant.customers.store');
            Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('tenant.customers.edit');
            Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('tenant.customers.update');
            Route::post('/customers/{customer}/portal-invitation', [CustomerController::class, 'inviteToPortal'])->name('tenant.customers.portal-invitation');
            Route::get('/deliveries', [DeliveryController::class, 'index'])->name('tenant.deliveries.index');
            Route::get('/deliveries/create', [DeliveryController::class, 'create'])->name('tenant.deliveries.create');
            Route::post('/deliveries', [DeliveryController::class, 'store'])->name('tenant.deliveries.store');
            Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show'])->name('tenant.deliveries.show');
            Route::post('/deliveries/{delivery}/assign', [DeliveryController::class, 'assign'])->name('tenant.deliveries.assign');
            Route::patch('/deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus'])->name('tenant.deliveries.status');
            Route::post('/deliveries/{delivery}/tracking-link', [DeliveryTrackingLinkController::class, 'createOrRotate'])->name('tenant.deliveries.tracking-link.create');
            Route::post('/deliveries/{delivery}/tracking-email', [DeliveryTrackingEmailController::class, 'store'])->name('tenant.deliveries.tracking-email.store');
            Route::post('/deliveries/{delivery}/tracking-window', [DeliveryTrackingLinkController::class, 'updateWindow'])->name('tenant.deliveries.tracking-window.update');
            Route::post('/tracking-links/{trackingLink}/revoke', [DeliveryTrackingLinkController::class, 'revoke'])->name('tenant.tracking-links.revoke');
            Route::post('/deliveries/bulk-assign', [DispatcherOperationsController::class, 'bulkAssign'])->name('tenant.deliveries.bulk-assign');
            Route::post('/deliveries/{delivery}/cancel', [DispatcherOperationsController::class, 'cancel'])->name('tenant.deliveries.cancel');
            Route::post('/deliveries/{delivery}/exceptions', [DispatcherOperationsController::class, 'storeException'])->name('tenant.deliveries.exceptions.store');
            Route::post('/exceptions/{exception}/resolve', [DispatcherOperationsController::class, 'resolveException'])->name('tenant.exceptions.resolve');
            Route::get('/operations/reports', [DispatcherOperationsController::class, 'reports'])->name('tenant.operations.reports');
        });

        Route::middleware('can:view-assigned-operations')->group(function (): void {
            Route::get('/my-work', [AssignedDeliveryController::class, 'index'])->name('tenant.assigned-deliveries.index');
            Route::patch('/my-work/{delivery}/status', [AssignedDeliveryController::class, 'updateStatus'])->name('tenant.assigned-deliveries.status');
            Route::post('/my-work/{delivery}/proof', [DeliveryProofController::class, 'store'])->middleware('can:update-assigned-operations')->name('tenant.assigned-deliveries.proof.store');
        });
    });
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
