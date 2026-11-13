<?php

use App\Http\Controllers\CrmRegistrationController;
use App\Http\Middleware\AuthenticateCrm;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/crm')->middleware([AuthenticateCrm::class, 'throttle:60,1,crm'])->group(function () {
    Route::get('registrations', [CrmRegistrationController::class, 'index'])->name('crm.registrations.index');
    Route::get('registrations/{registration}', [CrmRegistrationController::class, 'show'])
        ->where('registration', 'LGA-[1-9][0-9]*')->name('crm.registrations.show');
});
