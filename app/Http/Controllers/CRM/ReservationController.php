<?php

namespace App\Http\Controllers\CRM;

use App\Models\User;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Language;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User\RealestateManagement\City;
use App\Models\User\RealestateManagement\Category;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;


class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $reservations = Reservation::with(['property', 'customer'])->latest()->paginate(10);
        return view('real-estate.back.reservations.index', compact('reservations'));
        // real-estate.back.reservations.index
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $properties = PropertyContent::all();
        $customers = Customer::all();
        return view('real-estate.back.reservations.create', compact('properties', 'customers'));
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
        $request->validate([
            'property_id' => 'required|exists:user_properties,id',
            'customer_id' => 'required|exists:customers,id',
            'reservation_date' => 'required|date',
            'amount' => 'nullable|numeric',
            'status' => 'required|in:pending,confirmed,cancelled',
            'payment_status' => 'required|in:pending,paid,failed',
        ]);

        Reservation::create($request->all());

        return redirect()->route('crm.reservations.index')->with('success', 'Reservation created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $reservation
     * @return \Illuminate\Http\Response
     */
    public function show(Reservation $reservation)
    {
        //
        dd($reservation);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Reservation $reservation)
    {
        $properties = PropertyContent::all();
        $customers = Customer::all();
        return view('real-estate.back.reservations.edit', compact('reservation', 'properties', 'customers'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $reservation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reservation $reservation)
    {
        $request->validate([
            'property_id' => 'required|exists:user_properties,id',
            'customer_id' => 'required|exists:customers,id',
            'reservation_date' => 'required|date',
            'amount' => 'nullable|numeric',
            'status' => 'required|in:pending,confirmed,cancelled',
            'payment_status' => 'required|in:pending,paid,failed',
        ]);

        $reservation->update($request->all());

        return redirect()->route('crm.reservations.index')->with('success', 'Reservation updated successfully!');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $reservation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reservation $reservation)
    {
        $reservation->delete();
        return redirect()->route('crm.reservations.index')->with('success', 'Reservation deleted successfully!');
    }
}
