<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storepurchase_analysisRequest;
use App\Http\Requests\Updatepurchase_analysisRequest;
use App\Models\purchase_analysis;

class PurchaseAnalysisController extends Controller
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
    public function store(Storepurchase_analysisRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(purchase_analysis $purchase_analysis)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(purchase_analysis $purchase_analysis)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Updatepurchase_analysisRequest $request, purchase_analysis $purchase_analysis)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(purchase_analysis $purchase_analysis)
    {
        //
    }
}
