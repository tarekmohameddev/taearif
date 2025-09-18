<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class WhatsAppTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = WhatsAppTemplate::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by language
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active');
        }

        $templates = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('admin.communication.whatsapp-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $types = WhatsAppTemplate::getTypes();
        $languages = WhatsAppTemplate::getLanguages();
        $allVariables = WhatsAppTemplate::getVariablesForType('welcome');
        
        return view('admin.communication.whatsapp-templates.create', compact('types', 'languages', 'allVariables'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:whatsapp_templates,name',
            'content' => 'required|string|max:1600',
            'type' => 'required|in:' . implode(',', array_keys(WhatsAppTemplate::getTypes())),
            'language' => 'required|in:' . implode(',', array_keys(WhatsAppTemplate::getLanguages())),
            'description' => 'nullable|string|max:500',
            'status' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $template = WhatsAppTemplate::create([
            'name' => $request->name,
            'content' => $request->content,
            'type' => $request->type,
            'language' => $request->language,
            'description' => $request->description,
            'status' => $request->has('status'),
            'character_count' => strlen($request->content),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Validate template content
        $errors = $template->validateContent();
        if (!empty($errors)) {
            $template->delete();
            return redirect()->back()
                ->withErrors(['content' => implode(', ', $errors)])
                ->withInput();
        }

        Session::flash('success', 'WhatsApp template created successfully!');
        return redirect()->route('admin.whatsapp-templates.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(WhatsAppTemplate $whatsappTemplate)
    {
        return view('admin.communication.whatsapp-templates.show', compact('whatsappTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WhatsAppTemplate $whatsappTemplate)
    {
        $types = WhatsAppTemplate::getTypes();
        $languages = WhatsAppTemplate::getLanguages();
        $allVariables = WhatsAppTemplate::getVariablesForType($whatsappTemplate->type);
        $supportedVariables = WhatsAppTemplate::getVariablesForType($whatsappTemplate->type);
        
        return view('admin.communication.whatsapp-templates.edit', compact('whatsappTemplate', 'types', 'languages', 'allVariables', 'supportedVariables'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WhatsAppTemplate $whatsappTemplate)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:whatsapp_templates,name,' . $whatsappTemplate->id,
            'content' => 'required|string|max:1600',
            'type' => 'required|in:' . implode(',', array_keys(WhatsAppTemplate::getTypes())),
            'language' => 'required|in:' . implode(',', array_keys(WhatsAppTemplate::getLanguages())),
            'description' => 'nullable|string|max:500',
            'status' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $whatsappTemplate->update([
            'name' => $request->name,
            'content' => $request->content,
            'type' => $request->type,
            'language' => $request->language,
            'description' => $request->description,
            'status' => $request->has('status'),
            'character_count' => strlen($request->content),
            'updated_at' => now()
        ]);

        // Validate template content
        $errors = $whatsappTemplate->validateContent();
        if (!empty($errors)) {
            return redirect()->back()
                ->withErrors(['content' => implode(', ', $errors)])
                ->withInput();
        }

        Session::flash('success', 'WhatsApp template updated successfully!');
        return redirect()->route('admin.whatsapp-templates.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WhatsAppTemplate $whatsappTemplate)
    {
        $whatsappTemplate->delete();
        Session::flash('success', 'WhatsApp template deleted successfully!');
        return redirect()->route('admin.whatsapp-templates.index');
    }

    /**
     * Toggle template status
     */
    public function toggleStatus(WhatsAppTemplate $whatsappTemplate)
    {
        $whatsappTemplate->update(['status' => !$whatsappTemplate->status]);
        
        $status = $whatsappTemplate->status ? 'activated' : 'deactivated';
        Session::flash('success', "WhatsApp template {$status} successfully!");
        
        return redirect()->back();
    }

    /**
     * Duplicate template
     */
    public function duplicate(WhatsAppTemplate $whatsappTemplate)
    {
        $newTemplate = $whatsappTemplate->replicate();
        $newTemplate->name = $whatsappTemplate->name . ' (Copy)';
        $newTemplate->save();

        Session::flash('success', 'WhatsApp template duplicated successfully!');
        return redirect()->route('admin.whatsapp-templates.edit', $newTemplate);
    }

    /**
     * Preview template
     */
    public function preview(WhatsAppTemplate $whatsappTemplate)
    {
        return view('admin.communication.whatsapp-templates.preview', compact('whatsappTemplate'));
    }
}