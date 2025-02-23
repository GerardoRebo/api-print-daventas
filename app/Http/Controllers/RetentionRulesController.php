<?php

namespace App\Http\Controllers;

use App\Models\RetentionRule;
use App\Models\Tax;
use Illuminate\Http\Request;

class RetentionRulesController extends Controller
{
    function index()
    {
        $user = request()->user();
        return RetentionRule::with('tax')->where('organization_id', $user->organization_id)->get();
    }
    function store()
    {
        $user = request()->user();

        $validated = request()->validate([
            'tax_id' => "required",
            'regimen_fiscal' => "numeric",
        ]);
        $validated['organization_id'] = $user->organization_id;
        RetentionRule::create($validated);
    }
    function update($id)
    {
        $validated = request()->validate([
            'tax_id' => "required",
            'regimen_fiscal' => "numeric",
        ]);
        $retentionRule = RetentionRule::findOrFail($id);
        $retentionRule->tax_id = $validated['tax_id'];
        $retentionRule->regimen_fiscal = $validated['regimen_fiscal'];
        $retentionRule->save();
    }
    function destroy($id)
    {
        return RetentionRule::destroy($id);
    }
}
