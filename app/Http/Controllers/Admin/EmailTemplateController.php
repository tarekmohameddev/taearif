<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = EmailTemplate::latest()->paginate(10);
        return view('admin.communication.email-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $types = EmailTemplate::getTypes();
        $languages = EmailTemplate::getLanguages();
        $allVariables = EmailTemplate::getVariablesForType('password_reset');
        
        return view('admin.communication.email-templates.create', compact('types', 'languages', 'allVariables'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:email_templates,name',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:' . implode(',', array_keys(EmailTemplate::getTypes())),
            'language' => 'required|in:' . implode(',', array_keys(EmailTemplate::getLanguages())),
            'description' => 'nullable|string|max:500',
            'status' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $template = new EmailTemplate([
            'name' => $request->name,
            'description' => $request->description,
            'subject' => $request->subject,
            'content' => $request->content,
            'type' => $request->type,
            'language' => $request->language,
            'status' => $request->boolean('status', true),
            'character_count' => mb_strlen($request->content),
        ]);
        $template->save();

        // Validate template content
        $errors = $template->validateContent();
        if (!empty($errors)) {
            $template->delete();
            return redirect()->back()
                ->withErrors(['content' => implode(', ', $errors)])
                ->withInput();
        }

        Session::flash('success', 'Email template created successfully!');
        return redirect()->route('admin.email-templates.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmailTemplate $emailTemplate)
    {
        return view('admin.communication.email-templates.show', compact('emailTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        $types = EmailTemplate::getTypes();
        $languages = EmailTemplate::getLanguages();
        $allVariables = EmailTemplate::getVariablesForType($emailTemplate->type);
        $supportedVariables = EmailTemplate::getVariablesForType($emailTemplate->type);
        
        return view('admin.communication.email-templates.edit', compact('emailTemplate', 'types', 'languages', 'allVariables', 'supportedVariables'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:email_templates,name,' . $emailTemplate->id,
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:' . implode(',', array_keys(EmailTemplate::getTypes())),
            'language' => 'required|in:' . implode(',', array_keys(EmailTemplate::getLanguages())),
            'description' => 'nullable|string|max:500',
            'status' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $emailTemplate->update([
            'name' => $request->name,
            'description' => $request->description,
            'subject' => $request->subject,
            'content' => $request->content,
            'type' => $request->type,
            'language' => $request->language,
            'status' => $request->boolean('status', true),
            'character_count' => mb_strlen($request->content),
        ]);

        // Validate template content
        $errors = $emailTemplate->validateContent();
        if (!empty($errors)) {
            return redirect()->back()
                ->withErrors(['content' => implode(', ', $errors)])
                ->withInput();
        }

        Session::flash('success', 'Email template updated successfully!');
        return redirect()->route('admin.email-templates.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();
        Session::flash('success', 'Email template deleted successfully!');
        return redirect()->route('admin.email-templates.index');
    }

    /**
     * Toggle template status
     */
    public function toggleStatus(EmailTemplate $emailTemplate)
    {
        $emailTemplate->update(['status' => !$emailTemplate->status]);

        $statusMessage = $emailTemplate->status ? 'activated' : 'deactivated';
        Session::flash('success', "Email template {$statusMessage} successfully!");
        
        return redirect()->back();
    }

    /**
     * Duplicate template
     */
    public function duplicate(EmailTemplate $emailTemplate)
    {
        $newTemplate = $emailTemplate->replicate();
        $newTemplate->name = $emailTemplate->name . ' (Copy)';
        $newTemplate->save();

        Session::flash('success', 'Email template duplicated successfully!');
        return redirect()->route('admin.email-templates.edit', $newTemplate);
    }

    /**
     * Preview template
     */
    public function preview(EmailTemplate $emailTemplate)
    {
        return view('admin.communication.email-templates.preview', compact('emailTemplate'));
    }
}
