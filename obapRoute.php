<?php

use App\Http\Controllers\OBAPController;
use App\Http\Controllers\UniversityPolicyController;
use Illuminate\Support\Facades\Route;

Route::prefix('OBAP')->name('obap.')->group(function () {
    Route::get('/', [OBAPController::class, 'index'])->name('index');

    Route::get('co-po-map-index', [OBAPController::class, 'COPOMapIndex'])->name('co-po-map.index');

    // add
    Route::get('create-co-po-mapping-index', [OBAPController::class, 'createCOPOMappingIndex'])->name('create-co-po-mapping-index');
    Route::post('modules', [OBAPController::class, 'modules'])->name('modules');
    Route::get('co-po-mapping-details', [OBAPController::class, 'COPOMappingDetails'])->name('co-po-mapping-details');
    Route::post('co-po-mapping-details/store', [OBAPController::class, 'COPOMappingStore'])->name('co-po-mapping-store');

    // view
    Route::get('view-co-po-mapping-index', [OBAPController::class, 'viewCOPOMappingIndex'])->name('view-co-po-mapping-index');
    Route::post('view-modules', [OBAPController::class, 'viewModules'])->name('view-co-po-mapping-modules');
    Route::get('view-co-po-mapping-details', [OBAPController::class, 'viewCOPOMappingDetails'])->name('view-co-po-mapping-details');
});

Route::prefix('OBAP')->middleware(['admin', 'checkSoftwareAccess:OBAP,Settings'])->name('obap.settings.')->group(function () {
    Route::get('/settings-index', [OBAPController::class, 'settingsIndex'])->name('index');

    // 
    Route::get('program-outcome-index', [OBAPController::class, 'createProgramOutcomeIndex'])->name('program-outcome.index');

    Route::post('/obap/settings/get-modules', [ObapController::class, 'getModules'])->name('get.modules');

    Route::post('program-outcome-store', [OBAPController::class, 'createProgramOutcomeStore'])->name('program-outcome.store');

    Route::get('program-outcome-view', [OBAPController::class, 'programOutcomeView'])->name('program-outcome-view');

    Route::delete('/program-outcome/bulk-delete', [OBAPController::class, 'bulkDelete'])->name('program-outcome.bulk-delete');


    // 
    Route::get('taxonomy-index', [OBAPController::class, 'createTexonomyIndex'])->name('taxonomy.index');

    Route::post('taxonomy-store', [OBAPController::class, 'taxonomyStore'])->name('taxonomy-store');

    Route::get('taxonomy-view', [OBAPController::class, 'taxonomyView'])->name('taxonomy-view');

    Route::delete('/taxonomy/bulk-delete', [OBAPController::class, 'taxonomybulkDelete'])->name('taxonomy.bulk-delete');
});
