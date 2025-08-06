<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiCustomerInquiry;

class CustomerInquiryController extends Controller
{
    /**
     * Display a listing of the customer inquiries.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

     public function index(Request $request)
     {
         $user = $request->user();
         $perPage = $request->input('per_page', 10);

         $query = ApiCustomerInquiry::with('customer')
             ->where('user_id', $user->id)
             ->when($request->filled('inquiry_type'), function ($q) use ($request) {
                 $q->where('inquiry_type', $request->inquiry_type);
             })
             ->when($request->filled('property_type'), function ($q) use ($request) {
                 $q->where('property_type', $request->property_type);
             })
             ->when($request->filled('location'), function ($q) use ($request) {
                 $q->where('location', 'like', '%' . $request->location . '%');
             })
             ->when($request->filled('min_budget'), function ($q) use ($request) {
                 $q->where('budget', '>=', $request->min_budget);
             })
             ->when($request->filled('max_budget'), function ($q) use ($request) {
                 $q->where('budget', '<=', $request->max_budget);
             })
             ->when($request->filled('customer_id'), function ($q) use ($request) {
                 $q->where('customer_id', $request->customer_id);
             })
             ->orderBy('id', 'desc');

         $inquiries = $query->paginate($perPage);

         // Transform collection
         $inquiries->getCollection()->transform(function ($inquiry) {
             return [
                 'id' => $inquiry->id,
                 'message' => $inquiry->message,
                 'inquiry_type' => $inquiry->inquiry_type,
                 'property_type' => $inquiry->property_type,
                 'budget' => $inquiry->budget,
                 'location' => $inquiry->location,
                 'customer' => [
                     'id' => $inquiry->customer->id ?? null,
                     'name' => $inquiry->customer->name ?? null,
                 ],
             ];
         });

         return response()->json([
             'status' => 'success',
             'data' => [
                 'inquiries' => $inquiries->items(),
                 'pagination' => [
                     'total' => $inquiries->total(),
                     'per_page' => $inquiries->perPage(),
                     'current_page' => $inquiries->currentPage(),
                     'last_page' => $inquiries->lastPage(),
                     'from' => $inquiries->firstItem(),
                     'to' => $inquiries->lastItem(),
                 ],
             ]
         ], 200);
     }




}
