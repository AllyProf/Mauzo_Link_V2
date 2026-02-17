<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanManagementController extends Controller
{
    /**
     * Display all plans
     */
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->get();
        
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Show form to create new plan
     */
    public function create()
    {
        return view('admin.plans.create');
    }

    /**
     * Store new plan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:0',
            'max_locations' => 'required|integer|min:1',
            'max_users' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $plan = Plan::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'price' => $request->price,
            'trial_days' => $request->trial_days,
            'max_locations' => $request->max_locations,
            'max_users' => $request->max_users,
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? 0,
            'features' => $request->features ?? [],
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    /**
     * Show form to edit plan
     */
    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Update plan
     */
    public function update(Request $request, Plan $plan)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:0',
            'max_locations' => 'required|integer|min:1',
            'max_users' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $plan->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'price' => $request->price,
            'trial_days' => $request->trial_days,
            'max_locations' => $request->max_locations,
            'max_users' => $request->max_users,
            'is_active' => $request->has('is_active'),
            'sort_order' => $request->sort_order ?? $plan->sort_order,
            'features' => $request->features ?? [],
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    /**
     * Toggle plan active status
     */
    public function toggleStatus(Plan $plan)
    {
        $plan->update([
            'is_active' => !$plan->is_active
        ]);

        $status = $plan->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Plan {$status} successfully.");
    }
}
