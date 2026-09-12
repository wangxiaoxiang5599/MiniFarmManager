<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthRecordRequest;
use App\Models\Animal;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class HealthRecordController extends Controller
{
    public function store(StoreHealthRecordRequest $request, Animal $animal): RedirectResponse
    {
        $animal->healthRecords()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Health record added.')]);

        return to_route('animals.show', $animal);
    }
}
