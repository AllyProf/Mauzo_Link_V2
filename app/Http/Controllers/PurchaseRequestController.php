<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\PurchaseRequest;
use App\Models\Staff;
use App\Models\PettyCashIssue;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    use HandlesStaffPermissions;

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function index()
    {
        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();
        
        $query = PurchaseRequest::where('user_id', $ownerId)->with(['requester', 'processor']);

        // Staff only see their own requests, unless they are managers or accountants
        $isPowerUser = $this->hasPermission('finance', 'view') || $this->hasPermission('reports', 'view');
        
        if ($staff && !$isPowerUser) {
            $query->where('staff_id', $staff->id);
        }

        $requests = $query->latest()->paginate(20);

        // Fetch products grouped by category
        $products = ProductVariant::where('is_active', true)
            ->whereHas('product', function($q) use ($ownerId) {
                $q->where('user_id', $ownerId);
            })
            ->with(['product', 'stockMovements'])
            ->get()
            ->map(function($variant) {
                // Calculate current available stock
                $variant->available_stock = $variant->stockMovements()->sum('quantity');
                $variant->category_name = $variant->product->category ?? 'General';
                return $variant;
            });

        $categories = $products->pluck('category_name')->unique()->sort();

        return view('purchase_requests.index', compact('requests', 'isPowerUser', 'products', 'categories'));
    }

    public function store(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();

        if (!$staff) {
            return back()->with('error', 'Only staff members can create purchase requests.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price_per_unit' => 'required|numeric|min:0',
            'estimated_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Format items as a readable text list for the database (compatibility)
        $itemsText = "";
        foreach ($validated['items'] as $item) {
            $product = ProductVariant::find($item['product_id']);
            $total = $item['quantity'] * $item['price_per_unit'];
            $itemsText .= "- " . $product->name . " (Qty: " . $item['quantity'] . " @ TSh " . number_format($item['price_per_unit']) . " = TSh " . number_format($total) . ")\n";
        }

        $purchaseRequest = PurchaseRequest::create([
            'user_id' => $ownerId,
            'staff_id' => $staff->id,
            'request_number' => PurchaseRequest::generateRequestNo($ownerId),
            'items_list' => trim($itemsText),
            'estimated_amount' => $validated['estimated_amount'],
            'status' => 'pending',
            'notes' => $validated['notes'],
        ]);

        // Notify Accountants via SMS
        try {
            $accountantRoles = Role::where('slug', 'accountant')
                ->orWhere('name', 'LIKE', '%accountant%')
                ->pluck('id');

            $accountants = Staff::where('user_id', $ownerId)
                ->whereIn('role_id', $accountantRoles)
                ->where('is_active', true)
                ->get();

            $businessName = DB::table('users')->where('id', $ownerId)->value('business_name') ?? 'MauzoLink';
            $message = "New Purchase Request {$purchaseRequest->request_number} from {$staff->full_name} for {$businessName}. Amount: TSh " . number_format($purchaseRequest->estimated_amount);

            foreach ($accountants as $accountant) {
                if ($accountant->phone_number) {
                    $this->smsService->sendSms($accountant->phone_number, $message);
                }
            }
        } catch (\Exception $e) {
            // Silently log or ignore SMS errors to not break the request flow
            \Log::error("Purchase Request SMS Alert Failed: " . $e->getMessage());
        }

        return back()->with('success', 'Purchase request submitted successfully and Accountant notified via SMS.');
    }

    public function update(Request $request, $id)
    {
        if (!$this->hasPermission('finance', 'edit')) {
            abort(403);
        }

        $purchaseRequest = PurchaseRequest::findOrFail($id);
        
        if (!in_array($purchaseRequest->status, ['pending', 'approved'])) {
            return back()->with('error', 'Only pending or approved requests can be modified.');
        }

        $validated = $request->validate([
            'items_list' => 'required|string',
            'estimated_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $purchaseRequest->update($validated);

        return back()->with('success', 'Purchase request updated successfully by accountant.');
    }

    public function process(Request $request, $id)
    {
        if (!$this->hasPermission('finance', 'edit')) {
            abort(403);
        }

        $purchaseRequest = PurchaseRequest::findOrFail($id);
        $staff = $this->getCurrentStaff();

        $validated = $request->validate([
            'action' => 'required|in:approve,reject,issue',
            'issued_amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        if ($validated['action'] === 'reject') {
            $purchaseRequest->update([
                'status' => 'rejected',
                'processed_by' => $staff->id ?? null,
                'processed_at' => now(),
                'reason' => $validated['reason'],
            ]);
            return back()->with('success', 'Request rejected.');
        }

        if ($validated['action'] === 'approve') {
            $purchaseRequest->update([
                'status' => 'approved',
                'processed_by' => $staff->id ?? null,
                'processed_at' => now(),
            ]);
            return back()->with('success', 'Request approved. Waiting for issuance.');
        }

        if ($validated['action'] === 'issue') {
            if (!$validated['issued_amount']) {
                return back()->with('error', 'Please specify the amount issued.');
            }

            DB::beginTransaction();
            try {
                $purchaseRequest->update([
                    'status' => 'issued',
                    'issued_amount' => $validated['issued_amount'],
                    'processed_by' => $staff->id ?? null,
                    'processed_at' => now(),
                ]);

                // Create a formal record in petty_cash_issues
                PettyCashIssue::create([
                    'user_id' => $purchaseRequest->user_id,
                    'issued_by' => $purchaseRequest->user_id, // Must be user ID (Owner) to satisfy foreign key
                    'staff_id' => $purchaseRequest->staff_id,
                    'amount' => $validated['issued_amount'],
                    'purpose' => "Funded Purchase Request: " . $purchaseRequest->request_number,
                    'status' => 'issued',
                    'issue_date' => now()->toDateString(),
                    'notes' => "Issued by Accountant " . ($staff->full_name ?? 'Unknown') . ". Request: " . $purchaseRequest->request_number,
                ]);

                DB::commit();
                return back()->with('success', 'Amount issued successfully and recorded in Petty Cash.');
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Purchase Request Issuance Failed: " . $e->getMessage());
                return back()->with('error', 'Failed to link with Petty Cash: ' . $e->getMessage());
            }
        }

        return back();
    }
}
