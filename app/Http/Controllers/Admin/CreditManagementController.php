<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Api\markting\CreditPackage;
use App\Models\Api\markting\MarketingChannelPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class CreditManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display unified credit management dashboard
     */
    public function index(Request $request)
    {
        // Get total counts (unfiltered) for statistics cards
        $totalPackages = CreditPackage::count();
        $activePackagesCount = CreditPackage::where('is_active', true)->count();
        $totalChannels = MarketingChannelPricing::count();
        $activeChannelsCount = MarketingChannelPricing::where('is_active', true)->count();
        
        // Get credit packages with filters
        $packagesQuery = CreditPackage::query();
        
        if ($request->filled('package_status')) {
            $packagesQuery->where('is_active', $request->package_status === 'active');
        }
        
        if ($request->filled('marketing_support')) {
            $packagesQuery->where('supports_marketing_channels', $request->marketing_support === 'yes');
        }
        
        if ($request->filled('package_search')) {
            $search = $request->package_search;
            $packagesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }
        
        $packages = $packagesQuery->ordered()->paginate(10, ['*'], 'packages_page');
        
        // Get channel pricing with filters
        $pricingQuery = MarketingChannelPricing::query();
        
        if ($request->filled('channel_status')) {
            $pricingQuery->where('is_active', $request->channel_status === 'active');
        }
        
        if ($request->filled('channel_search')) {
            $search = $request->channel_search;
            $pricingQuery->where('channel_type', 'like', "%{$search}%");
        }
        
        // Order channels with SMS below WhatsApp, then others alphabetically
        $channelPricing = $pricingQuery->orderByRaw("
            CASE channel_type 
                WHEN 'whatsapp' THEN 1
                WHEN 'sms' THEN 2
                WHEN 'facebook' THEN 3
                WHEN 'telegram' THEN 4
                WHEN 'instagram' THEN 5
                ELSE 6
            END
        ")->paginate(10, ['*'], 'pricing_page');
        
        // Get channel types for dropdowns
        $channelTypes = MarketingChannelPricing::getChannelTypes();
        
        // Calculate package estimates for active channels
        $packageEstimates = [];
        $activeChannels = MarketingChannelPricing::active()->get();
        
        foreach ($packages as $package) {
            if ($package->supports_marketing_channels) {
                $packageEstimates[$package->id] = $package->getEstimatedMessagesPerChannel();
            }
        }
        
        return view('admin.credit_management.dashboard', compact(
            'packages', 
            'channelPricing', 
            'channelTypes', 
            'packageEstimates',
            'activeChannels',
            'totalPackages',
            'activePackagesCount',
            'totalChannels',
            'activeChannelsCount'
        ));
    }

    /**
     * Quick create credit package (AJAX)
     */
    public function quickCreatePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'supports_marketing_channels' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $package = CreditPackage::create([
            'name' => $request->name,
            'name_ar' => $request->name_ar,
            'description_ar' => $request->description_ar,
            'credits' => $request->credits,
            'price' => $request->price,
            'currency' => $request->currency,
            'supports_marketing_channels' => $request->boolean('supports_marketing_channels'),
            'is_active' => true,
        ]);

        // Auto-sync channel pricing when a marketing package is created
        if ($request->boolean('supports_marketing_channels')) {
            $this->autoSyncChannelPricing();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Package created successfully!',
            'package' => $package
        ]);
    }

    /**
     * Quick create channel pricing (AJAX)
     */
    public function quickCreatePricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'channel_type' => 'required|string|max:50|alpha_dash',
            'credits_per_message' => 'required|integer|min:1',
            'price_per_credit' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'description_ar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if channel pricing already exists
        $existingPricing = MarketingChannelPricing::where('channel_type', $request->channel_type)->first();
        
        if ($existingPricing) {
            // Update existing pricing
            $existingPricing->update([
                'credits_per_message' => $request->credits_per_message,
                'price_per_credit' => $request->price_per_credit,
                'effective_price_per_message' => $request->credits_per_message * $request->price_per_credit,
                'currency' => $request->currency,
                'description_ar' => $request->description_ar,
                'is_active' => true,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Channel pricing updated successfully!',
                'pricing' => $existingPricing
            ]);
        }

        // Create new pricing
        $pricing = MarketingChannelPricing::create([
            'channel_type' => $request->channel_type,
            'credits_per_message' => $request->credits_per_message,
            'price_per_credit' => $request->price_per_credit,
            'effective_price_per_message' => $request->credits_per_message * $request->price_per_credit,
            'currency' => $request->currency,
            'description_ar' => $request->description_ar,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Channel pricing created successfully!',
            'pricing' => $pricing
        ]);
    }

    /**
     * Quick update package (AJAX)
     */
    public function quickUpdatePackage(Request $request, $id)
    {
        $package = CreditPackage::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'supports_marketing_channels' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $package->update([
            'name' => $request->name,
            'name_ar' => $request->name_ar,
            'description_ar' => $request->description_ar,
            'credits' => $request->credits,
            'price' => $request->price,
            'currency' => $request->currency,
            'supports_marketing_channels' => $request->boolean('supports_marketing_channels'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Package updated successfully!',
            'package' => $package
        ]);
    }

    /**
     * Quick update channel pricing (AJAX)
     */
    public function quickUpdatePricing(Request $request, $id)
    {
        $pricing = MarketingChannelPricing::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'credits_per_message' => 'required|integer|min:1',
            'price_per_credit' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'description_ar' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $pricing->update([
            'credits_per_message' => $request->credits_per_message,
            'price_per_credit' => $request->price_per_credit,
            'effective_price_per_message' => $request->credits_per_message * $request->price_per_credit,
            'currency' => $request->currency,
            'description_ar' => $request->description_ar,
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Channel pricing updated successfully!',
            'pricing' => $pricing
        ]);
    }

    /**
     * Toggle package status (AJAX)
     */
    public function togglePackageStatus($id)
    {
        $package = CreditPackage::findOrFail($id);
        $package->is_active = !$package->is_active;
        $package->save();

        // Auto-sync channel pricing when a marketing package status changes
        if ($package->supports_marketing_channels) {
            $this->autoSyncChannelPricing();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Package status updated!',
            'is_active' => $package->is_active
        ]);
    }

    /**
     * Toggle channel pricing status (AJAX)
     */
    public function togglePricingStatus($id)
    {
        $pricing = MarketingChannelPricing::findOrFail($id);
        $pricing->is_active = !$pricing->is_active;
        $pricing->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Channel pricing status updated!',
            'is_active' => $pricing->is_active
        ]);
    }

    /**
     * Delete package (AJAX)
     */
    public function deletePackage($id)
    {
        $package = CreditPackage::findOrFail($id);
        $wasMarketingPackage = $package->supports_marketing_channels;
        $package->delete();

        // Auto-sync channel pricing when a marketing package is deleted
        if ($wasMarketingPackage) {
            $this->autoSyncChannelPricing();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Package deleted successfully!'
        ]);
    }

    /**
     * Delete channel pricing (AJAX)
     */
    public function deletePricing($id)
    {
        $pricing = MarketingChannelPricing::findOrFail($id);
        $pricing->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Channel pricing deleted successfully!'
        ]);
    }

    /**
     * Get package estimates for all channels (AJAX)
     */
    public function getPackageEstimates($packageId)
    {
        $package = CreditPackage::findOrFail($packageId);
        $estimates = $package->getEstimatedMessagesPerChannel();
        
        return response()->json([
            'status' => 'success',
            'estimates' => $estimates
        ]);
    }

    /**
     * Show edit form for package
     */
    public function editPackage($id)
    {
        $package = CreditPackage::findOrFail($id);
        return view('admin.credit_management.edit_package', compact('package'));
    }

    /**
     * Show edit form for pricing
     */
    public function editPricing($id)
    {
        $pricing = MarketingChannelPricing::findOrFail($id);
        $channelTypes = MarketingChannelPricing::getChannelTypes();
        return view('admin.credit_management.edit_pricing', compact('pricing', 'channelTypes'));
    }

    /**
     * Update package
     */
    public function updatePackage(Request $request, $id)
    {
        $package = CreditPackage::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'supports_marketing_channels' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $package->update([
            'name' => $request->name,
            'name_ar' => $request->name_ar,
            'description_ar' => $request->description_ar,
            'credits' => $request->credits,
            'price' => $request->price,
            'currency' => $request->currency,
            'supports_marketing_channels' => $request->boolean('supports_marketing_channels'),
            'is_active' => $request->boolean('is_active'),
        ]);

        // Auto-sync channel pricing when a marketing package is updated
        if ($request->boolean('supports_marketing_channels') || $package->supports_marketing_channels) {
            $this->autoSyncChannelPricing();
        }

        Session::flash('success', 'Package updated successfully!');
        return redirect()->route('admin.credit-management.index');
    }

    /**
     * Update pricing
     */
    public function updatePricing(Request $request, $id)
    {
        $pricing = MarketingChannelPricing::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'credits_per_message' => 'required|integer|min:1',
            'price_per_credit' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'description_ar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $pricing->update([
            'credits_per_message' => $request->credits_per_message,
            'price_per_credit' => $request->price_per_credit,
            'effective_price_per_message' => $request->credits_per_message * $request->price_per_credit,
            'currency' => $request->currency,
            'description_ar' => $request->description_ar,
            'is_active' => $request->boolean('is_active'),
        ]);

        Session::flash('success', 'Channel pricing updated successfully!');
        return redirect()->route('admin.credit-management.index');
    }

    /**
     * Auto-sync channel pricing (helper method)
     */
    private function autoSyncChannelPricing()
    {
        // Calculate average price per credit from active marketing packages
        $packages = CreditPackage::forMarketingChannels()
            ->active()
            ->get();

        if ($packages->count() > 0) {
            $totalPricePerCredit = 0;
            $count = 0;
            
            foreach ($packages as $package) {
                if ($package->credits > 0) {
                    $totalPricePerCredit += ($package->price / $package->credits);
                    $count++;
                }
            }
            
            if ($count > 0) {
                $avgPricePerCredit = $totalPricePerCredit / $count;
                
                MarketingChannelPricing::active()->get()->each(function ($pricing) use ($avgPricePerCredit) {
                    $pricing->price_per_credit = round($avgPricePerCredit, 4);
                    $pricing->updateEffectivePrice();
                });
            }
        }
    }

    /**
     * Sync pricing from credit packages (Manual trigger)
     */
    public function syncPricingFromPackages()
    {
        // Calculate average price per credit from active marketing packages
        $packages = CreditPackage::forMarketingChannels()
            ->active()
            ->get();

        if ($packages->count() > 0) {
            // Calculate average manually since price_per_credit is a computed attribute
            $totalPricePerCredit = 0;
            $count = 0;
            
            foreach ($packages as $package) {
                if ($package->credits > 0) {
                    $totalPricePerCredit += ($package->price / $package->credits);
                    $count++;
                }
            }
            
            if ($count > 0) {
                $avgPricePerCredit = $totalPricePerCredit / $count;
                
                MarketingChannelPricing::active()->get()->each(function ($pricing) use ($avgPricePerCredit) {
                    $pricing->price_per_credit = round($avgPricePerCredit, 4);
                    $pricing->updateEffectivePrice();
                });

                Session::flash('success', 'All channel pricing synced from credit packages! Average: ' . number_format($avgPricePerCredit, 4) . ' SAR/credit');
            } else {
                Session::flash('error', 'No valid credit packages found for syncing!');
            }
        } else {
            Session::flash('error', 'No active credit packages found for marketing channels!');
        }

        return redirect()->back();
    }
}
