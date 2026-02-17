<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignRecipient;
use App\Models\SmsTemplate;
use App\Models\CustomerSegment;
use App\Models\CustomerOptOut;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MarketingController extends Controller
{
    use HandlesStaffPermissions;

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Marketing Dashboard - Overview with Analytics
     */
    public function dashboard()
    {
        if (!$this->hasPermission('marketing', 'view')) {
            abort(403, 'You do not have permission to access marketing dashboard.');
        }

        $ownerId = $this->getOwnerId();

        // Get customer statistics
        $totalCustomers = $this->getTotalCustomers($ownerId);
        $activeCustomers = $this->getActiveCustomers($ownerId);
        $vipCustomers = $this->getVipCustomers($ownerId)->count();

        // Get campaign statistics
        $totalCampaigns = SmsCampaign::where('user_id', $ownerId)->count();
        $todayCampaigns = SmsCampaign::where('user_id', $ownerId)
            ->whereDate('created_at', today())
            ->count();
        $totalSmsSent = SmsCampaignRecipient::whereHas('campaign', function($q) use ($ownerId) {
            $q->where('user_id', $ownerId);
        })->where('status', 'sent')->orWhere('status', 'delivered')->count();

        // Get cost statistics
        $totalCost = SmsCampaign::where('user_id', $ownerId)->sum('actual_cost');
        $thisMonthCost = SmsCampaign::where('user_id', $ownerId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('actual_cost');

        // Recent campaigns
        $recentCampaigns = SmsCampaign::where('user_id', $ownerId)
            ->with('recipients')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Customer growth chart data (last 6 months)
        $customerGrowth = $this->getCustomerGrowthData($ownerId);

        // Campaign performance (last 6 months)
        $campaignPerformance = $this->getCampaignPerformanceData($ownerId);

        return view('marketing.dashboard', compact(
            'totalCustomers',
            'activeCustomers',
            'vipCustomers',
            'totalCampaigns',
            'todayCampaigns',
            'totalSmsSent',
            'totalCost',
            'thisMonthCost',
            'recentCampaigns',
            'customerGrowth',
            'campaignPerformance'
        ));
    }

    /**
     * Customer Database
     */
    public function customers(Request $request)
    {
        if (!$this->hasPermission('marketing', 'view')) {
            abort(403, 'You do not have permission to view customers.');
        }

        $ownerId = $this->getOwnerId();

        // Get unique customers from orders
        $query = DB::table('orders')
            ->select(
                'customer_phone',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('MAX(created_at) as last_order_date'),
                DB::raw('MIN(created_at) as first_order_date')
            )
            ->where('user_id', $ownerId)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->groupBy('customer_phone');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Get opt-out list
        $optOutPhones = CustomerOptOut::where('user_id', $ownerId)
            ->pluck('phone_number')
            ->toArray();

        // Filter out opted-out customers
        if (!empty($optOutPhones)) {
            $query->whereNotIn('customer_phone', $optOutPhones);
        }

        // Apply segmentation filters
        if ($request->has('segment')) {
            $segment = $request->get('segment');
            $query = $this->applySegmentFilter($query, $segment, $ownerId);
        }

        $customers = $query->orderBy('last_order_date', 'desc')->paginate(20);

        // Get segments
        $segments = CustomerSegment::where('user_id', $ownerId)
            ->where('is_active', true)
            ->get();

        // If JSON format requested (for AJAX)
        if ($request->has('format') && $request->get('format') === 'json') {
            return response()->json([
                'success' => true,
                'customers' => $customers->items(),
                'total' => $customers->total(),
            ]);
        }

        return view('marketing.customers', compact('customers', 'segments'));
    }

    /**
     * Create Campaign
     */
    public function createCampaign()
    {
        if (!$this->hasPermission('marketing', 'create')) {
            abort(403, 'You do not have permission to create campaigns.');
        }

        $ownerId = $this->getOwnerId();

        // Get templates
        $templates = SmsTemplate::where(function($q) use ($ownerId) {
            $q->where('user_id', $ownerId)
              ->orWhere('is_system_template', true);
        })
        ->where('is_active', true)
        ->orderBy('is_system_template', 'desc')
        ->orderBy('category')
        ->get();

        // Get segments
        $segments = CustomerSegment::where('user_id', $ownerId)
            ->where('is_active', true)
            ->get();

        // Get customer count
        $totalCustomers = $this->getTotalCustomers($ownerId);

        return view('marketing.create-campaign', compact('templates', 'segments', 'totalCustomers'));
    }

    /**
     * Store Campaign
     */
    public function storeCampaign(Request $request)
    {
        try {
            if (!$this->hasPermission('marketing', 'create')) {
                return response()->json(['error' => 'You do not have permission to create campaigns.'], 403);
            }

            // Custom validation to handle selected_customers as string or array
            $rules = [
                'name' => 'required|string|max:255',
                'message' => 'required|string|max:1600',
                'type' => 'required|in:template,custom,ab_test',
                'template_id' => 'nullable|exists:sms_templates,id',
                'scheduled_at' => 'nullable|date|after:now',
                'recipient_type' => 'required|in:all,selected,segment',
                'selected_customers' => 'nullable',
                'segment_id' => 'nullable|exists:customer_segments,id',
                'filters' => 'nullable|array',
            ];

            $validated = $request->validate($rules);

            $ownerId = $this->getOwnerId();

            // Handle selected_customers - can be array or comma-separated string
            if (isset($validated['selected_customers']) && $validated['selected_customers']) {
                if (is_string($validated['selected_customers'])) {
                    // If it's a string, split by comma and filter empty values
                    $validated['selected_customers'] = array_values(array_filter(
                        array_map('trim', explode(',', $validated['selected_customers']))
                    ));
                }
                // Ensure it's an array
                if (!is_array($validated['selected_customers'])) {
                    $validated['selected_customers'] = [];
                }
            } else {
                $validated['selected_customers'] = [];
            }

            // Check if there are any orders with customer phone numbers first
            $totalOrdersWithPhones = DB::table('orders')
                ->where('user_id', $ownerId)
                ->whereNotNull('customer_phone')
                ->where('customer_phone', '!=', '')
                ->count();

            if ($totalOrdersWithPhones === 0) {
                \Log::warning('Marketing: No orders with customer phone numbers', ['owner_id' => $ownerId]);
                return response()->json([
                    'error' => 'No customers found. You need to have orders with customer phone numbers in your system. Please ensure that when orders are created, customer phone numbers are recorded.',
                    'help' => 'To add customer phone numbers: 1) Create orders through the POS or waiter app with customer phone numbers, or 2) Update existing orders to include customer phone numbers.'
                ], 400);
            }

            // Get recipients based on type
            $recipients = $this->getRecipients($validated, $ownerId);

            if (empty($recipients)) {
                // Provide more helpful error message
                $errorMessage = 'No recipients found. ';
                
                if ($validated['recipient_type'] === 'selected') {
                    $errorMessage .= 'Please ensure you have selected valid customer phone numbers. The phone numbers must match exactly with those in your orders database.';
                } elseif ($validated['recipient_type'] === 'segment') {
                    $errorMessage .= 'The selected segment may not have any customers matching the criteria. Please check your segment filters.';
                } else {
                    $optOutCount = \App\Models\CustomerOptOut::where('user_id', $ownerId)->count();
                    if ($optOutCount > 0) {
                        $errorMessage .= "All {$totalOrdersWithPhones} customers with phone numbers have opted out of SMS. You have {$optOutCount} customers in your opt-out list.";
                    } else {
                        $errorMessage .= "There are {$totalOrdersWithPhones} orders with phone numbers, but none match your current filters. Please check your recipient selection.";
                    }
                }
                
                \Log::warning('Marketing: Campaign creation failed - no recipients', [
                    'owner_id' => $ownerId,
                    'recipient_type' => $validated['recipient_type'] ?? 'all',
                    'campaign_name' => $validated['name'] ?? 'N/A',
                    'total_orders_with_phones' => $totalOrdersWithPhones,
                ]);
                
                return response()->json(['error' => $errorMessage], 400);
            }

            // Calculate estimated cost (assuming 1 SMS = 1 unit, adjust based on your pricing)
            $estimatedCost = count($recipients) * 0.1; // Example: 0.1 per SMS

            // Create campaign
            $campaign = SmsCampaign::create([
                'user_id' => $ownerId,
                'name' => $validated['name'],
                'message' => $validated['message'],
                'type' => $validated['type'],
                'template_id' => $validated['template_id'] ?? null,
                'status' => $validated['scheduled_at'] ? 'scheduled' : 'draft',
                'total_recipients' => count($recipients),
                'estimated_cost' => $estimatedCost,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'recipient_filters' => [
                    'recipient_type' => $validated['recipient_type'],
                    'selected_customers' => $validated['selected_customers'] ?? [],
                    'segment_id' => $validated['segment_id'] ?? null,
                    'filters' => $validated['filters'] ?? [],
                ],
            ]);

            // Create recipient records
            foreach ($recipients as $recipient) {
                // Handle both array and object formats
                $phone = is_array($recipient) ? ($recipient['phone'] ?? null) : ($recipient->phone ?? null);
                $name = is_array($recipient) ? ($recipient['name'] ?? null) : ($recipient->name ?? null);
                
                if (!$phone) {
                    \Log::warning('Skipping recipient with no phone', ['recipient' => $recipient]);
                    continue;
                }
                
                SmsCampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'phone_number' => $phone,
                    'customer_name' => $name,
                    'status' => 'pending',
                ]);
            }

            // If not scheduled, send immediately
            if (!$validated['scheduled_at']) {
                try {
                    // Send campaign (this might take time for large campaigns)
                    // In production, you might want to use queues
                    $this->sendCampaign($campaign->id);
                    
                    // Reload campaign to get updated stats
                    $campaign->refresh();
                    
                    $sentCount = $campaign->sent_count ?? 0;
                    $failedCount = $campaign->failed_count ?? 0;
                    $totalRecipients = $campaign->total_recipients ?? 0;
                    
                    $message = "SMS Campaign sent successfully!\n\n";
                    $message .= "Total Recipients: {$totalRecipients}\n";
                    $message .= "Sent: {$sentCount}\n";
                    if ($failedCount > 0) {
                        $message .= "Failed: {$failedCount}\n";
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'campaign_id' => $campaign->id,
                        'sent_count' => $sentCount,
                        'failed_count' => $failedCount,
                        'total_recipients' => $totalRecipients,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error sending campaign', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Update campaign status to show error
                    $campaign->update([
                        'status' => 'draft',
                        'notes' => 'Error sending: ' . $e->getMessage()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'Campaign created but failed to send: ' . $e->getMessage(),
                        'campaign_id' => $campaign->id,
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Campaign created and scheduled successfully!',
                'campaign_id' => $campaign->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Campaign validation error', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Campaign creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'error' => 'Error creating campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Campaign History
     */
    public function campaigns(Request $request)
    {
        if (!$this->hasPermission('marketing', 'view')) {
            abort(403, 'You do not have permission to view campaigns.');
        }

        $ownerId = $this->getOwnerId();

        $query = SmsCampaign::where('user_id', $ownerId)
            ->with('recipients')
            ->withCount(['recipients as sent_count' => function($q) {
                $q->whereIn('status', ['sent', 'delivered']);
            }])
            ->withCount(['recipients as failed_count' => function($q) {
                $q->where('status', 'failed');
            }]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $campaigns = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('marketing.campaigns', compact('campaigns'));
    }

    /**
     * View Campaign Details
     */
    public function showCampaign($id)
    {
        if (!$this->hasPermission('marketing', 'view')) {
            abort(403, 'You do not have permission to view campaign details.');
        }

        $ownerId = $this->getOwnerId();

        $campaign = SmsCampaign::where('user_id', $ownerId)
            ->with('recipients')
            ->findOrFail($id);

        return view('marketing.campaign-details', compact('campaign'));
    }

    /**
     * Templates Management
     */
    public function templates()
    {
        if (!$this->hasPermission('marketing', 'view')) {
            abort(403, 'You do not have permission to view templates.');
        }

        $ownerId = $this->getOwnerId();

        $templates = SmsTemplate::where(function($q) use ($ownerId) {
            $q->where('user_id', $ownerId)
              ->orWhere('is_system_template', true);
        })
        ->orderBy('is_system_template', 'desc')
        ->orderBy('category')
        ->paginate(20);

        return view('marketing.templates', compact('templates'));
    }

    /**
     * Store Template
     */
    public function storeTemplate(Request $request)
    {
        if (!$this->hasPermission('marketing', 'create')) {
            return response()->json(['error' => 'You do not have permission to create templates.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:1600',
            'category' => 'required|in:holiday,promotion,update,engagement,custom',
            'language' => 'required|in:sw,en,both',
            'description' => 'nullable|string',
        ]);

        $ownerId = $this->getOwnerId();

        // Extract placeholders from content
        preg_match_all('/\{([^}]+)\}/', $validated['content'], $matches);
        $placeholders = $matches[1] ?? [];

        $template = SmsTemplate::create([
            'user_id' => $ownerId,
            'name' => $validated['name'],
            'content' => $validated['content'],
            'category' => $validated['category'],
            'language' => $validated['language'],
            'description' => $validated['description'],
            'placeholders' => $placeholders,
            'is_system_template' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully!',
            'template' => $template,
        ]);
    }

    /**
     * Send Campaign (process sending) - Route handler
     */
    public function sendCampaignRoute(Request $request, $id)
    {
        if (!$this->hasPermission('marketing', 'create')) {
            return response()->json(['error' => 'You do not have permission to send campaigns.'], 403);
        }

        $ownerId = $this->getOwnerId();
        $campaign = SmsCampaign::where('user_id', $ownerId)->findOrFail($id);

        // Allow sending if draft, scheduled, or stuck in sending status
        if (!in_array($campaign->status, ['draft', 'scheduled', 'sending'])) {
            return response()->json(['error' => 'Campaign cannot be sent in its current status.'], 400);
        }

        try {
            // Send campaign (synchronously for now, can be queued later)
            $this->sendCampaign($campaign->id);

            return response()->json([
                'success' => true,
                'message' => 'Campaign is being sent...',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in sendCampaignRoute', [
                'campaign_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Error sending campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Campaign (process sending) - Internal method
     */
    private function sendCampaign($campaignId)
    {
        try {
            $campaign = SmsCampaign::findOrFail($campaignId);

            // Allow retrying if status is "sending" (in case of previous failure)
            if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled' && $campaign->status !== 'sending') {
                \Log::info('Campaign cannot be sent', [
                    'campaign_id' => $campaignId,
                    'status' => $campaign->status
                ]);
                return;
            }

            // Clear previous error notes when retrying
            $campaign->update([
                'status' => 'sending',
                'sent_at' => now(),
                'notes' => null, // Clear previous errors
            ]);

            $recipients = $campaign->recipients()->where('status', 'pending')->get();
            
            if ($recipients->isEmpty()) {
                \Log::info('No pending recipients found', ['campaign_id' => $campaignId]);
                $campaign->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                return;
            }

            $successCount = 0;
            $failedCount = 0;
            $totalCost = 0;

            foreach ($recipients as $recipient) {
                try {
                    // Check if opted out
                    $isOptedOut = CustomerOptOut::where('user_id', $campaign->user_id)
                        ->where('phone_number', $recipient->phone_number)
                        ->exists();

                    if ($isOptedOut) {
                        $recipient->update([
                            'status' => 'failed',
                            'error_message' => 'Customer opted out',
                        ]);
                        $failedCount++;
                        continue;
                    }

                    // Personalize message
                    $message = $this->personalizeMessage($campaign->message, $recipient, $campaign->user_id);

                    if (empty($message)) {
                        $recipient->update([
                            'status' => 'failed',
                            'error_message' => 'Empty message',
                        ]);
                        $failedCount++;
                        continue;
                    }

                    // Send SMS
                    \Log::info('Sending SMS', [
                        'campaign_id' => $campaignId,
                        'recipient_id' => $recipient->id,
                        'phone' => $recipient->phone_number
                    ]);

                    $result = $this->smsService->sendSms($recipient->phone_number, $message);

                    \Log::info('SMS result', [
                        'campaign_id' => $campaignId,
                        'recipient_id' => $recipient->id,
                        'success' => $result['success'] ?? false,
                        'error' => $result['error'] ?? null,
                        'http_code' => $result['http_code'] ?? null,
                        'response' => $result['response'] ?? null
                    ]);

                    $cost = 0.1; // Adjust based on your pricing
                    $totalCost += $cost;

                    if ($result['success'] ?? false) {
                        $recipient->update([
                            'status' => 'sent',
                            'personalized_message' => $message,
                            'sms_provider_response' => $result['response'] ?? null,
                            'sent_at' => now(),
                            'cost' => $cost,
                        ]);
                        $successCount++;
                    } else {
                        $errorMsg = $result['error'] ?? ($result['response'] ?? 'Unknown error');
                        // Truncate error message if too long
                        if (strlen($errorMsg) > 1000) {
                            $errorMsg = substr($errorMsg, 0, 997) . '...';
                        }
                        
                        // Store response as JSON string, truncate if extremely long
                        $responseData = $result['response'] ?? null;
                        if ($responseData && is_string($responseData) && strlen($responseData) > 65535) {
                            // TEXT can hold up to 65,535 bytes, but let's be safe
                            $responseData = substr($responseData, 0, 65000) . '... [truncated]';
                        }
                        
                        $recipient->update([
                            'status' => 'failed',
                            'error_message' => $errorMsg,
                            'sms_provider_response' => $responseData,
                            'cost' => $cost,
                        ]);
                        $failedCount++;
                    }

                    // Small delay to avoid rate limiting
                    usleep(100000); // 0.1 second
                } catch (\Exception $e) {
                    \Log::error('Error sending SMS to recipient', [
                        'campaign_id' => $campaignId,
                        'recipient_id' => $recipient->id ?? null,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Truncate error message if too long (error_message is TEXT but keep it reasonable)
                    $errorMsg = 'Exception: ' . $e->getMessage();
                    if (strlen($errorMsg) > 1000) {
                        $errorMsg = substr($errorMsg, 0, 997) . '...';
                    }
                    
                    $recipient->update([
                        'status' => 'failed',
                        'error_message' => $errorMsg,
                    ]);
                    $failedCount++;
                }
            }

            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
                'sent_count' => $successCount,
                'failed_count' => $failedCount,
                'success_count' => $successCount,
                'actual_cost' => $totalCost,
            ]);

            \Log::info('Campaign sending completed', [
                'campaign_id' => $campaignId,
                'success_count' => $successCount,
                'failed_count' => $failedCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in sendCampaign', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update campaign status to show error
            try {
                $campaign = SmsCampaign::find($campaignId);
                if ($campaign) {
                    $campaign->update([
                        'status' => 'draft',
                        'notes' => 'Error: ' . $e->getMessage()
                    ]);
                }
            } catch (\Exception $updateError) {
                \Log::error('Failed to update campaign status after error', [
                    'campaign_id' => $campaignId,
                    'error' => $updateError->getMessage()
                ]);
            }
        }
    }

    /**
     * Helper: Get total customers
     */
    private function getTotalCustomers($ownerId)
    {
        $optOutPhones = CustomerOptOut::where('user_id', $ownerId)
            ->pluck('phone_number')
            ->toArray();

        $query = DB::table('orders')
            ->select(DB::raw('COUNT(DISTINCT customer_phone) as count'))
            ->where('user_id', $ownerId)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '');

        if (!empty($optOutPhones)) {
            $query->whereNotIn('customer_phone', $optOutPhones);
        }

        $result = $query->first();
        return $result ? (int)$result->count : 0;
    }

    /**
     * Helper: Get active customers (ordered in last 30 days)
     */
    private function getActiveCustomers($ownerId)
    {
        $optOutPhones = CustomerOptOut::where('user_id', $ownerId)
            ->pluck('phone_number')
            ->toArray();

        $query = DB::table('orders')
            ->select(DB::raw('COUNT(DISTINCT customer_phone) as count'))
            ->where('user_id', $ownerId)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->where('created_at', '>=', now()->subDays(30));

        if (!empty($optOutPhones)) {
            $query->whereNotIn('customer_phone', $optOutPhones);
        }

        $result = $query->first();
        return $result ? (int)$result->count : 0;
    }

    /**
     * Helper: Get VIP customers (top 20% spenders)
     */
    private function getVipCustomers($ownerId)
    {
        $optOutPhones = CustomerOptOut::where('user_id', $ownerId)
            ->pluck('phone_number')
            ->toArray();

        $customers = DB::table('orders')
            ->select('customer_phone', DB::raw('SUM(total_amount) as total_spent'))
            ->where('user_id', $ownerId)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->groupBy('customer_phone');

        if (!empty($optOutPhones)) {
            $customers->whereNotIn('customer_phone', $optOutPhones);
        }

        $allCustomers = $customers->get();
        $totalSpent = $allCustomers->sum('total_spent');
        $threshold = $totalSpent * 0.2; // Top 20%

        return $allCustomers->where('total_spent', '>=', $threshold);
    }

    /**
     * Helper: Get recipients based on filters
     */
    private function getRecipients($validated, $ownerId)
    {
        $optOutPhones = CustomerOptOut::where('user_id', $ownerId)
            ->pluck('phone_number')
            ->toArray();

        // First, check if there are any orders with customer phone numbers
        $totalOrdersWithPhones = DB::table('orders')
            ->where('user_id', $ownerId)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->count();

        \Log::info('Marketing: Getting recipients', [
            'owner_id' => $ownerId,
            'recipient_type' => $validated['recipient_type'] ?? 'all',
            'total_orders_with_phones' => $totalOrdersWithPhones,
            'opt_out_count' => count($optOutPhones),
        ]);

        if ($totalOrdersWithPhones === 0) {
            \Log::warning('Marketing: No orders with customer phone numbers found', ['owner_id' => $ownerId]);
            return [];
        }

        $query = DB::table('orders')
            ->select(
                'customer_phone as phone',
                DB::raw('MAX(customer_name) as name')
            )
            ->where('user_id', $ownerId)
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->groupBy('customer_phone');

        if (!empty($optOutPhones)) {
            $query->whereNotIn('customer_phone', $optOutPhones);
        }

        if ($validated['recipient_type'] === 'selected' && !empty($validated['selected_customers'])) {
            // Ensure selected_customers is an array
            $selectedPhones = is_array($validated['selected_customers']) 
                ? $validated['selected_customers'] 
                : array_filter(array_map('trim', explode(',', $validated['selected_customers'])));
            
            if (empty($selectedPhones)) {
                \Log::warning('Marketing: Selected customers list is empty', ['validated' => $validated]);
                return [];
            }
            
            $query->whereIn('customer_phone', $selectedPhones);
        } elseif ($validated['recipient_type'] === 'segment' && !empty($validated['segment_id'])) {
            $segment = CustomerSegment::find($validated['segment_id']);
            if ($segment) {
                $query = $this->applySegmentFilter($query, $segment->filters, $ownerId);
            } else {
                \Log::warning('Marketing: Segment not found', ['segment_id' => $validated['segment_id']]);
                return [];
            }
        }

        // Get the results
        $results = $query->get();
        
        \Log::info('Marketing: Recipients query result', [
            'count' => $results->count(),
            'recipient_type' => $validated['recipient_type'] ?? 'all',
        ]);

        // Convert to array format for consistency
        $recipients = $results->map(function($item) {
            return [
                'phone' => $item->phone,
                'name' => $item->name,
            ];
        })->toArray();

        // Filter out any recipients with empty phone numbers
        $recipients = array_filter($recipients, function($recipient) {
            return !empty($recipient['phone']);
        });

        return array_values($recipients); // Re-index array
    }

    /**
     * Helper: Apply segment filters
     */
    private function applySegmentFilter($query, $filters, $ownerId)
    {
        // This is a simplified version - you can expand based on your needs
        if (isset($filters['min_orders'])) {
            $query->havingRaw('COUNT(*) >= ?', [$filters['min_orders']]);
        }
        if (isset($filters['min_spent'])) {
            $query->havingRaw('SUM(total_amount) >= ?', [$filters['min_spent']]);
        }
        if (isset($filters['last_order_days'])) {
            $query->havingRaw('MAX(created_at) >= ?', [now()->subDays($filters['last_order_days'])]);
        }
        return $query;
    }

    /**
     * Helper: Personalize message
     */
    private function personalizeMessage($message, $recipient, $ownerId = null)
    {
        // Get phone number (handle both object and array)
        $phoneNumber = is_object($recipient) ? $recipient->phone_number : ($recipient['phone_number'] ?? $recipient['phone'] ?? null);
        
        if (!$phoneNumber) {
            return $message; // Return original message if no phone
        }
        
        // Get customer data
        $customer = DB::table('orders')
            ->select(
                DB::raw('MAX(customer_name) as name'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('MAX(created_at) as last_order_date')
            )
            ->where('customer_phone', $phoneNumber);
        
        if ($ownerId) {
            $customer->where('user_id', $ownerId);
        }
        
        $customer = $customer->first();

        // Get business name
        $businessName = 'Business';
        if ($ownerId) {
            $owner = \App\Models\User::find($ownerId);
            if ($owner && $owner->business_name) {
                $businessName = $owner->business_name;
            }
        }

        $replacements = [
            '{customer_name}' => $customer->name ?? 'Customer',
            '{total_orders}' => $customer->total_orders ?? '0',
            '{total_spent}' => number_format($customer->total_spent ?? 0, 2),
            '{last_order_date}' => $customer->last_order_date ? Carbon::parse($customer->last_order_date)->format('M d, Y') : 'N/A',
            '{business_name}' => $businessName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Helper: Get customer growth data
     */
    private function getCustomerGrowthData($ownerId)
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = DB::table('orders')
                ->where('user_id', $ownerId)
                ->whereNotNull('customer_phone')
                ->where('customer_phone', '!=', '')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->distinct('customer_phone')
                ->count('customer_phone');
            
            $data[] = [
                'month' => $date->format('M Y'),
                'count' => $count,
            ];
        }
        return $data;
    }

    /**
     * Helper: Get campaign performance data
     */
    private function getCampaignPerformanceData($ownerId)
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $campaigns = SmsCampaign::where('user_id', $ownerId)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->get();
            
            $sent = $campaigns->sum('sent_count');
            $failed = $campaigns->sum('failed_count');
            
            $data[] = [
                'month' => $date->format('M Y'),
                'sent' => $sent,
                'failed' => $failed,
                'success_rate' => $sent > 0 ? (($sent - $failed) / $sent) * 100 : 0,
            ];
        }
        return $data;
    }

    /**
     * Send SMS directly (quick send without creating campaign)
     */
    public function sendDirectSms(Request $request)
    {
        if (!$this->hasPermission('marketing', 'create')) {
            return response()->json(['error' => 'You do not have permission to send SMS.'], 403);
        }

        $validated = $request->validate([
            'phones' => 'required|array|min:1',
            'phones.*' => 'required|string',
            'message' => 'required|string|max:1600',
        ]);

        $ownerId = $this->getOwnerId();
        $phones = $validated['phones'];
        $message = $validated['message'];
        
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($phones as $phone) {
            try {
                // Check if opted out
                $isOptedOut = CustomerOptOut::where('user_id', $ownerId)
                    ->where('phone_number', $phone)
                    ->exists();

                if ($isOptedOut) {
                    $failedCount++;
                    $errors[] = "Customer $phone opted out";
                    continue;
                }

                // Get customer data for personalization
                $customer = DB::table('orders')
                    ->select(
                        DB::raw('MAX(customer_name) as name'),
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(total_amount) as total_spent'),
                        DB::raw('MAX(created_at) as last_order_date')
                    )
                    ->where('customer_phone', $phone)
                    ->where('user_id', $ownerId)
                    ->first();

                // Personalize message using customer data
                $personalizedMessage = $this->personalizeMessage($message, ['phone' => $phone], $ownerId);

                // Send SMS
                $result = $this->smsService->sendSms($phone, $personalizedMessage);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $errors[] = "Failed to send to $phone: " . ($result['error'] ?? 'Unknown error');
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Error sending to $phone: " . $e->getMessage();
                \Log::error('Error sending direct SMS', [
                    'phone' => $phone,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Sent {$successCount} SMS successfully" . ($failedCount > 0 ? ", {$failedCount} failed" : ""),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors
        ]);
    }

    /**
     * Get templates as JSON (for template selector)
     */
    public function getTemplatesJson(Request $request)
    {
        if (!$this->hasPermission('marketing', 'view')) {
            return response()->json(['error' => 'You do not have permission to view templates.'], 403);
        }

        $ownerId = $this->getOwnerId();

        $templates = SmsTemplate::where(function($q) use ($ownerId) {
            $q->where('user_id', $ownerId)
              ->orWhere('is_system_template', true);
        })
        ->orderBy('is_system_template', 'desc')
        ->orderBy('category')
        ->get(['id', 'name', 'content', 'category', 'description']);

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }
}
