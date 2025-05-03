<?php

namespace App\Http\Controllers\CRM;

// use PayPal\Api\Sale;
use App\Models\Sale;
use App\Models\User;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User\RealestateManagement\Category;
use App\Models\User\RealestateManagement\City;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $sales = Sale::with(['customer', 'property', 'user'])->paginate(10);
        return view('real-estate.back.sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $users = User::all();
        $properties = PropertyContent::all();
        $contracts = Contract::all();
        $categories = Category::all();
        $cities = City::all();
    // dd($cities);
        return view('real-estate.back.sales.create', compact('users', 'properties', 'contracts', 'categories' , 'cities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());

        // Ensure language_id is set
        $language = Language::where('is_default', 1)->first();

        // $language = Language::where('code', $request->language)
        // ->where('user_id', Auth::id()) // Now valid
        // ->first();


        if (!$language) {
            return back()->withErrors(['language' => 'Default language not found.']);
        }

        $request->validate([
            'sale_price' => 'required|numeric',
            'sale_date' => 'required|date',
            'status' => 'required|in:pending,completed,canceled',
            'property_title' => 'required|string',
            'price' => 'required|numeric',
            'purpose' => 'required|in:sale,rent,lease,investment',
            'type' => 'required',
            'beds' => 'required|integer|min:0',
            'bath' => 'required|integer|min:0',
            'area' => 'required|numeric|min:0',
            'property_status' => 'required|in:1,2',
            'category_id' => 'required|exists:user_property_categories,id',
            'contract_subject' => 'required|string|max:255',
        ]);



        // Create Property
        $property = Property::create([
            'user_id' => auth()->id(),
            'price' => $request->price,
            'purpose' => $request->purpose,
            'type' => $request->type,
            'beds' => $request->beds,
            'bath' => $request->bath,
            'area' => $request->area,
            'status' => $request->property_status,
        ]);

        // Create Property Content with language_id
        PropertyContent::create([
            'user_id' => auth()->id(),
            'property_id' => $property->id,
            'language_id' => $language->id,
            'category_id' => $request->category_id,
            'title' => $request->property_title,
            'city_id' => $request->city_id,
            'state_id' => $request->state_id,
            'slug' => $request->slug,
            'address' => $request->address,
            'description' => $request->description,
        ]);
        // Create Contract
        $contract = Contract::create([
            'customer_id' => auth()->id(),
            'subject' => $request->contract_subject,
            'contract_value' => 5454,
            'start_date' => $request->sale_date,
            'end_date' => $request->sale_date,
        ]);
        // Create Sale
        Sale::create([
            'user_id' => auth()->id(),
            'property_id' => $property->id,
            'sale_price' => $request->sale_price,
            'sale_date' => $request->sale_date,
            'status' => $request->status,
        ]);

        return redirect()->route('crm.sales.index')->with('success', 'Sale created successfully!');
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $sale)
    {
        //
        $sale->load(['customer', 'property', 'user', 'contracts']);
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $sale = Sale::with(['property.contents', 'user', 'contract'])->findOrFail($id);
        $users = User::all();
        $properties = Property::with('contents')->get(); // Ensure all contents are loaded
        $contracts = Contract::all();

        return view('real-estate.back.sales.edit')->with([
            'sale' => $sale,
            'users' => $users,
            'properties' => $properties,
            'contracts' => $contracts,
        ]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

     public function update(Request $request, $id)
     {
         // Debugging step
         // dd($request->all());

         $request->validate([
             'sale_price' => 'required|numeric',
             'sale_date' => 'required|date',
             'status' => 'required|in:pending,completed,canceled',
             'contract_subject' => 'required|string|max:255',

             // Property fields
             'price' => 'required|numeric',
             'purpose' => 'required|in:sale,rent,lease,investment',
             'type' => 'required|in:residential,commercial',
             'beds' => 'required|integer|min:0',
             'bath' => 'required|integer|min:0',
             'area' => 'required|numeric|min:0',
             'latitude' => 'required|string',
             'longitude' => 'required|string',
             'featured' => 'required|boolean',
             'video_url' => 'nullable|string|url',
             'featured_image' => 'nullable|string',
             'floor_planning_image' => 'nullable|string',
             'video_image' => 'nullable|string',
             'property_status' => 'required|in:1,2',
         ]);

         // Find the Sale with its related property and contract
         $sale = Sale::with(['property', 'contract'])->findOrFail($id);

         // Update Sale details
         $sale->update([
             'sale_price' => $request->sale_price,
             'sale_date' => $request->sale_date,
             'status' => $request->status,
         ]);

         // Update related Property if exists
         if ($sale->property) {
             $sale->property->update([
                 'price' => $request->price,
                 'purpose' => $request->purpose,
                 'type' => $request->type,
                 'beds' => $request->beds,
                 'bath' => $request->bath,
                 'area' => $request->area,
                 'latitude' => $request->latitude,
                 'longitude' => $request->longitude,
                 'featured' => $request->featured,
                 'video_url' => $request->video_url,
                 'status' => $request->property_status,
                 'featured_image' => $request->featured_image,
                 'floor_planning_image' => $request->floor_planning_image,
                 'video_image' => $request->video_image,
             ]);
         }
         if (!$sale->contract) {
             return redirect()->route('crm.sales.index')->with('error', 'No contract found for this sale.');
         }
         $sale->contract->update([
             'subject' => $request->contract_subject,
             'start_date' => $request->sale_date,
         ]);

         return redirect()->route('crm.sales.index')->with('success', 'Sale, Property, and Contract updated successfully!');
     }





    // public function update(Request $request, $id)
    // {
    //     //
    //     $validated = $request->validate([
    //         'property_id' => 'required|exists:user_properties,id',
    //         'user_id' => 'required|exists:users,id',
    //         'contract_id' => 'required|exists:contracts,id',
    //         'sale_price' => 'required|numeric|min:0',
    //         'sale_date' => 'required|date',
    //         'status' => 'required|in:pending,completed,canceled',
    //     ]);

    //     $sale = Sale::findOrFail($id);
    //     $sale->update($validated);

    //     return redirect()->route('crm.sales.index')->with('success', 'Sale updated successfully!');

    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sale $sale)
    {
        //
        $sale->delete();
        return redirect()->route('crm.sales.index')->with('success', 'Sale deleted!');
    }
}
