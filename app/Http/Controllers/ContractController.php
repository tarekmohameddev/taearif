<?php

namespace App\Http\Controllers;

use Storage;
use Mpdf\Mpdf;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\View;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contracts = Contract::with('customer')->latest()->get();
        return view('real-estate.back.contracts.index', compact('contracts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $customers = Customer::all();
        return view('real-estate.back.contracts.create', compact('customers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'contract_value' => 'required|numeric|min:0',
            'contract_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'contract_status' => 'required|in:draft,signed,expired',

        ]);

        Contract::create($request->all());

        return redirect()->route('contracts.index')->with('success', 'Contract created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Contract $contract)
    {
        $contract = Contract::with('customer')->find($contract->id);

        // dd($contract);
        return view('real-estate.back.contracts.show', compact('contract'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Contract $contract)
    {
        $customers = Customer::all();
        return view('real-estate.back.contracts.edit', compact('contract', 'customers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contract $contract)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'contract_value' => 'required|numeric|min:0',
            'contract_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'contract_status' => 'required|in:draft,signed,expired',

        ]);

        $contract->update($request->all());

        return redirect()->route('contracts.index')->with('success', 'Contract updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contract $contract)
    {
        $contract->delete();
        return redirect()->route('contracts.index')->with('success', 'Contract deleted successfully.');
    }

    public function contractsign(Contract $contract)
    {
        return view('pdf_view', compact('contract'));
    }

    public function downloadPDF(Contract $contract)
    {
        $html = view('pdf_view', compact('contract'))->render();

        $mpdf = new Mpdf(/* config for Arabic, if needed */);
        $mpdf->WriteHTML($html);

        $output = $mpdf->Output('contract.pdf', 'S');
        return response($output)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-disposition', 'attachment; filename="contract.pdf"');
    }

    public function sign(Request $request, Contract $contract)
    {
        $request->validate([
            'signature'      => 'required|string',
            'signed_name'    => 'required|string|max:255',
        ]);

        $base64Full = $request->input('signature');

        // $parts = explode(',', $base64Full);
        // $rawBase64 = $parts[1] ?? '';

        $decoded = base64_decode($base64Full, true);
        if ($decoded === false) {
            return back()->withErrors(['signature' => 'Invalid base64 data.']);
        }

        $fileName = 'signatures/contract_'.$contract->id.'_'.time().'.png';

        if (!Storage::disk('public')->put($fileName, $decoded)) {
            return back()->withErrors(['signature' => 'Failed to save signature.']);
        }

        $contract->signed_name   = $request->input('signed_name');
        $contract->signed_date   = now();
        $contract->signed_ip     = $request->ip();
        $contract->is_signed     = true;
        $contract->signature_path = $fileName;
        $contract->save();

        return redirect()
            ->route('contracts.show', $contract->id)
            ->with('success', 'Successfully signed!');
    }
    //
    //
    public function handleAction(Contract $contract, $action)
    {
        // Ensure the method exists before calling it
        if (method_exists($this, $action)) {
            // Optionally, you might check if the method is allowed
            return $this->$action($contract);
        }

        abort(404);
    }

    // Be cautious using names like "print" since it can be confused with PHP language constructs.
    public function print(Contract $contract)
    {
        // Your logic for printing
        return view('contracts.print', compact('contract'));
    }

    public function send(Contract $contract)
    {
        // Your logic for sending the contract
        return redirect()->back()->with('success', 'Contract sent successfully!');
    }

    public function reminder(Contract $contract)
    {
        // Your logic for sending a reminder
        return redirect()->back()->with('success', 'Reminder sent successfully!');
    }

    public function cancel(Contract $contract)
    {
        // Your logic for cancelling the contract
        $contract->cancel(); // Example
        return redirect()->back()->with('success', 'Contract cancelled!');
    }

    public function renew(Contract $contract)
    {
        // Your logic for renewing the contract
        $contract->renew(); // Example
        return redirect()->back()->with('success', 'Contract renewed!');
    }

    //
    //
}
