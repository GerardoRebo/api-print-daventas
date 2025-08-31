<?php

namespace App\Http\Controllers;

use App\Models\RetentionRule;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RetentionRulesController extends Controller
{
    function index()
    {
        if (request()->query('type') == 'common') {
            return config('retention_rules');
        }
        $user = request()->user();
        return RetentionRule::with('tax')
            ->where('organization_id', $user->organization_id)
            ->get();
    }
    function store()
    {
        $user = request()->user();

        $validated = request()->validate([
            'name' => "required",
            'regimen_fiscal' => "nullable",
            'isr_percentage' => "nullable",
            'iva_percentage' => "nullable|numeric",
        ]);

        $validated['organization_id'] = $user->organization_id;
        RetentionRule::create($validated);
    }
    function update($id)
    {
        $validated = request()->validate([
            'name' => "required",
            'regimen_fiscal' => "nullable",
            'isr_percentage' => "nullable",
            'iva_percentage' => "nullable|numeric",
        ]);
        $retentionRule = RetentionRule::findOrFail($id);
        $retentionRule->name = $validated['name'];
        $retentionRule->regimen_fiscal = $validated['regimen_fiscal'];
        $retentionRule->isr_percentage = $validated['isr_percentage'];
        $retentionRule->iva_percentage = $validated['iva_percentage'];
        $retentionRule->save();
    }
    function destroy($id)
    {
        return RetentionRule::destroy($id);
    }
}
