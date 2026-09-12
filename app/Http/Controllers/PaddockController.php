<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaddockRequest;
use App\Http\Requests\UpdatePaddockRequest;
use App\Http\Resources\AnimalSummaryResource;
use App\Http\Resources\PaddockResource;
use App\Models\Paddock;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaddockController extends Controller
{
    public function index(): Response
    {
        $paddocks = Paddock::query()
            ->withOccupancy()
            ->orderBy('name')
            ->get();

        return Inertia::render('paddocks/Index', [
            'paddocks' => PaddockResource::collection($paddocks)->resolve(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('paddocks/Create');
    }

    public function store(StorePaddockRequest $request): RedirectResponse
    {
        $paddock = Paddock::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Paddock created.')]);

        return to_route('paddocks.show', $paddock);
    }

    public function show(Paddock $paddock): Response
    {
        $paddock->loadCount(['activeAnimals as occupancy']);

        $animals = $paddock->activeAnimals()
            ->orderBy('tag_number')
            ->get();

        return Inertia::render('paddocks/Show', [
            'paddock' => PaddockResource::make($paddock)->resolve(),
            'animals' => AnimalSummaryResource::collection($animals)->resolve(),
        ]);
    }

    public function edit(Paddock $paddock): Response
    {
        $paddock->loadCount(['activeAnimals as occupancy']);

        return Inertia::render('paddocks/Edit', [
            'paddock' => PaddockResource::make($paddock)->resolve(),
        ]);
    }

    public function update(UpdatePaddockRequest $request, Paddock $paddock): RedirectResponse
    {
        $paddock->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Paddock updated.')]);

        return to_route('paddocks.show', $paddock);
    }
}
