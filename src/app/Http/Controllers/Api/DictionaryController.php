<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DictionaryController extends Controller
{
    protected $modelMap = [
        'dress-codes' => \App\Models\DressCode::class,
        'payment-types' => \App\Models\PaymentType::class,
        'driver-attachment-types' => \App\Models\DriverAttachmentType::class,
        'vehicle-attachment-types' => \App\Models\VehicleAttachmentType::class,
        'service-statuses' => \App\Models\ServiceStatus::class,
        'ztl' => \App\Models\Ztl::class,
        'activity-types' => \App\Models\ActivityType::class,
        'service-types' => \App\Models\ServiceType::class,
        'accounting-entries' => \App\Models\AccountingEntry::class,
    ];

    /**
     * Get the model class for the given dictionary type
     */
    protected function getModel(string $type)
    {
        if (!isset($this->modelMap[$type])) {
            abort(404, 'Dictionary type not found');
        }

        return new $this->modelMap[$type];
    }

    /**
     * Display a listing of the dictionary items.
     */
    public function index(Request $request, string $type)
    {
        $model = $this->getModel($type);

        // Determine eager-load relationships
        $relationships = ['company:id,name'];
        if ($type === 'ztl') {
            $relationships[] = 'vehicles:id,license_plate,brand,model';
        }

        // Super-admin can filter by company_id or see all
        if ($request->user()->hasRole('super-admin')) {
            $query = $model->newQuery()->with($relationships);

            if ($request->has('company_id') && $request->company_id) {
                $query->where('company_id', $request->company_id);
            }

            // Use 'city' for ZTL, 'name' for others
            $orderField = $type === 'ztl' ? 'city' : 'name';
            $items = $query->orderBy($orderField)->get();
        } else {
            // Other users see only their company's items
            $orderField = $type === 'ztl' ? 'city' : 'name';
            $items = $model->where('company_id', $request->user()->company_id)
                ->with($relationships)
                ->orderBy($orderField)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Store a newly created dictionary item.
     */
    public function store(Request $request, string $type)
    {
        $model = $this->getModel($type);

        $rules = $this->getValidationRules($type);

        // Super-admin can specify company_id, others use their own
        if ($request->user()->hasRole('super-admin') && $request->has('company_id')) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $validated = $request->validate($rules);

        // If not super-admin or company_id not provided, use user's company
        if (!isset($validated['company_id'])) {
            $validated['company_id'] = $request->user()->company_id;
        }

        // Handle is_default logic - only one item can be default
        if (isset($validated['is_default']) && $validated['is_default']) {
            $model->where('company_id', $validated['company_id'])
                ->update(['is_default' => false]);
        }

        // Remove vehicle_ids from validated data before creating
        $vehicleIds = $validated['vehicle_ids'] ?? null;
        unset($validated['vehicle_ids']);

        $item = $model->create($validated);

        // Sync vehicles for ZTL
        if ($type === 'ztl' && $vehicleIds !== null) {
            $item->vehicles()->sync($vehicleIds);
            $item->load('vehicles:id,license_plate,brand,model');
        }

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully',
            'data' => $item,
        ], 201);
    }

    /**
     * Display the specified dictionary item.
     */
    public function show(Request $request, string $type, int $id)
    {
        $model = $this->getModel($type);

        $item = $model->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /**
     * Update the specified dictionary item.
     */
    public function update(Request $request, string $type, int $id)
    {
        $model = $this->getModel($type);

        // Super-admin can update any item, others only their company's items
        if ($request->user()->hasRole('super-admin')) {
            $item = $model->findOrFail($id);
        } else {
            $item = $model->where('company_id', $request->user()->company_id)
                ->findOrFail($id);
        }

        $rules = $this->getValidationRules($type, $id);

        // Super-admin can change company_id
        if ($request->user()->hasRole('super-admin') && $request->has('company_id')) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $validated = $request->validate($rules);

        // Determine the target company_id for is_default logic
        $targetCompanyId = $validated['company_id'] ?? $item->company_id;

        // Handle is_default logic - only one item can be default
        if (isset($validated['is_default']) && $validated['is_default']) {
            $model->where('company_id', $targetCompanyId)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        // Remove vehicle_ids from validated data before updating
        $vehicleIds = $validated['vehicle_ids'] ?? null;
        unset($validated['vehicle_ids']);

        $item->update($validated);

        // Sync vehicles for ZTL
        if ($type === 'ztl' && $vehicleIds !== null) {
            $item->vehicles()->sync($vehicleIds);
            $item->load('vehicles:id,license_plate,brand,model');
        }

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $item,
        ]);
    }

    /**
     * Remove the specified dictionary item.
     */
    public function destroy(Request $request, string $type, int $id)
    {
        $model = $this->getModel($type);

        $item = $model->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully',
        ]);
    }

    /**
     * Get validation rules based on dictionary type
     */
    protected function getValidationRules(string $type, ?int $id = null): array
    {
        $baseRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];

        // Type-specific rules
        switch ($type) {
            case 'dress-codes':
                $baseRules['is_default'] = 'boolean';
                $baseRules['is_active'] = 'boolean';
                break;

            case 'payment-types':
            case 'driver-attachment-types':
            case 'vehicle-attachment-types':
                $baseRules['is_active'] = 'boolean';
                break;

            case 'service-statuses':
                $baseRules['color_code'] = 'nullable|string|max:7';
                $baseRules['is_active'] = 'boolean';
                break;

            case 'ztl':
                $baseRules = [
                    'city' => 'required|string|max:255',
                    'periodicity' => 'nullable|string|max:255',
                    'expiration_date' => 'nullable|date',
                    'notes' => 'nullable|string',
                    'is_active' => 'boolean',
                    'vehicle_ids' => 'nullable|array',
                    'vehicle_ids.*' => 'exists:vehicles,id',
                ];
                break;

            case 'activity-types':
                $baseRules['abbreviation'] = 'required|string|max:20';
                // 'description' viene usato per 'notes' nel frontend
                break;

            case 'service-types':
                $baseRules['abbreviation'] = 'required|string|max:20';
                // 'description' viene usato per 'notes' nel frontend
                break;

            case 'accounting-entries':
                $baseRules['abbreviation'] = 'required|string|max:20';
                $baseRules['type'] = 'required|in:debit,credit';
                // 'description' viene usato per 'notes' nel frontend
                break;
        }

        return $baseRules;
    }
}
