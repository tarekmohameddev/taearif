<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User\JobApplication;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    protected function tenantId(): int
    {
        return (int) auth()->id();
    }

    /**
     * List all job applications for the authenticated tenant.
     */
    public function index(Request $request)
    {
        $tenantId = $this->tenantId();
        $perPage = (int) ($request->input('per_page', 20));
        $perPage = min(max($perPage, 1), 100);

        $query = JobApplication::query()
            ->where('user_id', $tenantId)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        $items = $query->paginate($perPage);

        $data = $items->getCollection()->map(fn (JobApplication $a) => [
            'id' => (string) $a->id,
            'name' => $a->name,
            'phone' => $a->phone,
            'email' => $a->email,
            'description' => $a->description,
            'pdf_path' => $a->pdf_path ? asset('storage/' . $a->pdf_path) : null,
            'created_at' => $a->created_at?->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'job_applications' => $data,
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Show one job application (tenant-scoped).
     */
    public function show(string $id)
    {
        $tenantId = $this->tenantId();
        $a = JobApplication::where('user_id', $tenantId)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $a->id,
                'name' => $a->name,
                'phone' => $a->phone,
                'email' => $a->email,
                'description' => $a->description,
                'pdf_path' => $a->pdf_path ? asset('storage/' . $a->pdf_path) : null,
                'created_at' => $a->created_at?->toISOString(),
            ],
        ]);
    }
}
