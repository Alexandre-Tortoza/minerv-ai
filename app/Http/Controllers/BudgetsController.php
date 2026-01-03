<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorebudgetsRequest;
use App\Http\Requests\UpdatebudgetsRequest;
use App\Models\budgets;

class BudgetsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorebudgetsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(budgets $budgets)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(budgets $budgets)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatebudgetsRequest $request, budgets $budgets)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(budgets $budgets)
    {
        //
    }
}
