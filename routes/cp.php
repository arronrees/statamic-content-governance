<?php

use Arronrees\ContentGovernance\Http\Controllers\CP\DashboardController;
use Arronrees\ContentGovernance\Http\Controllers\CP\GovernanceRecordController;
use Arronrees\ContentGovernance\Http\Controllers\CP\RecordActionController;
use Illuminate\Support\Facades\Route;

Route::prefix('governance')->name('governance.')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('records/actions', [RecordActionController::class, 'run'])->name('records.actions.run');
    Route::post('records/actions/list', [RecordActionController::class, 'bulkActions'])->name('records.actions.bulk');

    Route::get('records', [GovernanceRecordController::class, 'index'])->name('records.index');
    Route::get('records/{record}', [GovernanceRecordController::class, 'show'])->name('records.show');
});
