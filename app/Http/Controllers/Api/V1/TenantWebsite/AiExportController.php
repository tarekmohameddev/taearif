<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiExportController extends Controller
{
    protected function resolveTenant(string $tenantId): User
    {
        return User::where('username', $tenantId)->firstOrFail();
    }

    public function index(Request $request, string $tenantId): JsonResponse
    {
        $tenant = $this->resolveTenant($tenantId);

        $includeParam = trim((string) $request->query('include', 'tenant,properties,projects,documents'));
        $include = array_filter(array_map('trim', explode(',', $includeParam)));
        if (empty($include)) {
            $include = ['tenant','properties','projects','documents'];
        }

        $lang = $request->query('lang');
        $since = $request->query('since');
        $includeDrafts = (int) $request->query('include_drafts', 0) === 1;

        $limit = min(max((int) $request->query('limit', 50), 1), 100);
        $page  = max((int) $request->query('page', 1), 1);

        $chunkChars   = min(max((int) $request->query('chunk_chars', 1500), 500), 3000);
        $chunkOverlap = min(max((int) $request->query('chunk_overlap', 150), 0), 400);

        $data = [];

        if (in_array('tenant', $include, true)) {
            $data['tenant'] = $this->mapTenant($tenant);
        }

        $propertiesPaginator = null;
        if (in_array('properties', $include, true)) {
            $propertiesQuery = Property::query()
                ->with(['contents', 'galleryImages', 'proertyAmenities.amenity', 'UserPropertyCharacteristics'])
                ->where('user_id', $tenant->id);

            if (!$includeDrafts) {
                $propertiesQuery->where('status', 1);
            }

            if ($since) {
                $propertiesQuery->where('updated_at', '>=', $since);
            }

            $propertiesQuery->orderBy('updated_at', 'desc');
            $propertiesPaginator = $propertiesQuery->paginate($limit, ['*'], 'page', $page);
            $data['properties'] = collect($propertiesPaginator->items())->map(function ($property) use ($tenantId, $lang) {
                return $this->mapProperty($property, $tenantId, $lang);
            })->values();
        }

        $projectsPaginator = null;
        if (in_array('projects', $include, true)) {
            $projectsQuery = Project::query()
                ->with(['contents', 'galleryImages', 'specifications', 'types'])
                ->where('user_id', $tenant->id);

            if (!$includeDrafts) {
                $projectsQuery->where('published', 1);
            }

            if ($since) {
                $projectsQuery->where('updated_at', '>=', $since);
            }

            $projectsQuery->orderBy('updated_at', 'desc');
            $projectsPaginator = $projectsQuery->paginate($limit, ['*'], 'page', $page);
            $data['projects'] = collect($projectsPaginator->items())->map(function ($project) use ($tenantId, $lang) {
                return $this->mapProject($project, $tenantId, $lang);
            })->values();
        }

        if (in_array('documents', $include, true)) {
            $documents = [];
            if (!empty($data['tenant'])) {
                $documents[] = $this->buildTenantDocument($data['tenant']);
            }
            if (!empty($data['properties'])) {
                foreach ($data['properties'] as $p) {
                    $documents[] = $this->buildPropertyDocument($p);
                }
            }
            if (!empty($data['projects'])) {
                foreach ($data['projects'] as $p) {
                    $documents[] = $this->buildProjectDocument($p);
                }
            }
            $data['documents'] = $this->chunkDocuments($documents, $chunkChars, $chunkOverlap);
        }

        $data['meta'] = [
            'page' => $page,
            'limit' => $limit,
            'total_properties' => $propertiesPaginator?->total() ?? 0,
            'total_projects' => $projectsPaginator?->total() ?? 0,
            'since' => $since,
            'lang' => $lang,
            'chunk_chars' => $chunkChars,
            'chunk_overlap' => $chunkOverlap,
        ];

        return response()->json($data);
    }

    public function downloadTxt(Request $request, string $tenantId): StreamedResponse
    {
        $tenant = $this->resolveTenant($tenantId);
        $since = $request->query('since');
        $includeDrafts = (int) $request->query('include_drafts', 0) === 1;
        $lang = $request->query('lang');

        $fileName = sprintf('%s-ai-export-%s.txt', $tenantId, now()->format('Ymd'));

        $callback = function () use ($tenant, $tenantId, $since, $includeDrafts, $lang) {
            $write = function (string $text) {
                echo $text;
                flush();
            };

            $write('Tenant AI Export – ' . ($tenant->name ?? $tenant->username) . ' (' . $tenant->username . ') – ' . now()->toDateString() . "\n\n");

            // Tenant section
            $write("== Tenant ==\n");
            $tenantArr = $this->mapTenant($tenant);
            $write($this->buildTenantDocument($tenantArr) . "\n\n");

            // Properties
            $propertiesQuery = Property::query()
                ->with(['contents', 'galleryImages', 'proertyAmenities.amenity', 'UserPropertyCharacteristics'])
                ->where('user_id', $tenant->id);
            if (!$includeDrafts) {
                $propertiesQuery->where('status', 1);
            }
            if ($since) {
                $propertiesQuery->where('updated_at', '>=', $since);
            }
            $props = $propertiesQuery->orderBy('updated_at', 'desc')->cursor();

            $countProps = 0;
            foreach ($props as $property) { $countProps++; }
            $write("== Properties (" . $countProps . ") ==\n");

            // Re-run cursor to stream each property
            $props = $propertiesQuery->orderBy('updated_at', 'desc')->cursor();
            foreach ($props as $property) {
                $p = $this->mapProperty($property, $tenantId, $lang);
                $write($this->buildPropertyDocument($p) . "\n----\n\n");
            }

            // Projects
            $projectsQuery = Project::query()
                ->with(['contents', 'galleryImages', 'specifications', 'types'])
                ->where('user_id', $tenant->id);
            if (!$includeDrafts) {
                $projectsQuery->where('published', 1);
            }
            if ($since) {
                $projectsQuery->where('updated_at', '>=', $since);
            }
            $projs = $projectsQuery->orderBy('updated_at', 'desc')->cursor();

            $countProjs = 0;
            foreach ($projs as $project) { $countProjs++; }
            $write("\n== Projects (" . $countProjs . ") ==\n");

            // Re-run cursor to stream each project
            $projs = $projectsQuery->orderBy('updated_at', 'desc')->cursor();
            foreach ($projs as $project) {
                $p = $this->mapProject($project, $tenantId, $lang);
                $write($this->buildProjectDocument($p) . "\n");
            }
        };

        $response = new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        return $response;
    }

    private function mapTenant(User $tenant): array
    {
        return [
            'id' => (int) $tenant->id,
            'username' => (string) $tenant->username,
            'name' => (string) ($tenant->name ?? ''),
            'brand' => (string) ($tenant->brand ?? ''),
            'contact_email' => (string) ($tenant->email ?? ''),
            'phone' => (string) ($tenant->phone ?? ''),
            'website' => (string) ($tenant->website ?? ''),
            'address' => (string) ($tenant->address ?? ''),
            'city' => (string) ($tenant->city ?? ''),
            'country' => (string) ($tenant->country ?? ''),
            'languages' => array_values(array_filter((array) ($tenant->languages ?? []))),
            'about' => (string) ($tenant->about ?? ''),
            'logo_url' => $tenant->logo ? asset($tenant->logo) : null,
        ];
    }

    private function mapProperty($property, string $tenantId, ?string $lang): array
    {
        $content = optional($property->contents->first());
        $title = (string) ($content?->title ?? '');
        $slug = (string) ($content?->slug ?? '');
        $description = (string) ($content?->description ?? '');
        $featured = $property->featured_image ? asset($property->featured_image) : null;
        $gallery = $property->galleryImages ? $property->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray() : [];
        $images = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

        $frontendBase = $this->tenantFrontendBase($tenantId);
        $fullUrl = $slug && $frontendBase ? ($frontendBase . '/property/' . $slug) : null;

        return [
            'id' => (int) $property->id,
            'project_id' => $property->project_id,
            'payment_method' => $property->payment_method,
            'title' => $title,
            'slug' => $slug,
            'address' => (string) ($content?->address ?? ''),
            'city_id' => $content?->city_id,
            'state_id' => $content?->state_id,
            'price' => $property->price,
            'pricePerMeter' => $property->pricePerMeter,
            'purpose' => $property->purpose,
            'type' => $property->type,
            'beds' => $property->beds,
            'bath' => $property->bath,
            'area' => $property->area,
            'size' => $property->size ?? null,
            'features' => $property->features ?? [],
            'characteristics' => $property->UserPropertyCharacteristics ?? null,
            'status' => (bool) $property->status,
            'featured' => (bool) $property->featured,
            'featured_image' => $featured,
            'gallery' => $images,
            'description' => $description,
            'location' => [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'created_at' => optional($property->created_at)?->toISOString(),
            'updated_at' => optional($property->updated_at)?->toISOString(),
            'category_id' => $property->category_id,
            'faqs' => $property->faqs ?? [],
            'floor_planning_image' => collect($property->floor_planning_image ?? [])->map(fn($img) => asset($img))->toArray(),
            'url' => $fullUrl,
        ];
    }

    private function mapProject($project, string $tenantId, ?string $lang): array
    {
        $content = optional($project->contents->first());
        $title = (string) ($content?->title ?? '');
        $slug = (string) ($content?->slug ?? '');
        $description = (string) ($content?->description ?? '');
        $featured = $project->featured_image ? asset($project->featured_image) : null;
        $gallery  = $project->galleryImages ? $project->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray() : [];
        $images   = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

        $frontendBase = $this->tenantFrontendBase($tenantId);
        $fullUrl = $slug && $frontendBase ? ($frontendBase . '/project/' . $slug) : null;

        return [
            'id' => (int) $project->id,
            'title' => $title,
            'slug' => $slug,
            'developer' => (string) ($project->developer ?? ''),
            'min_price' => $project->min_price,
            'max_price' => $project->max_price,
            'featured' => (bool) $project->featured,
            'published' => (bool) $project->published,
            'units' => (int) ($project->units ?? 0),
            'completion_date' => (string) ($project->completion_date ?? ''),
            'location' => [
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
            ],
            'amenities' => array_values((array) ($project->amenities ?? [])),
            'specifications' => $project->specifications?->map(function ($spec) {
                return [
                    'title' => $spec->title,
                    'value' => $spec->value,
                ];
            })->values()->toArray() ?? [],
            'types' => $project->types?->map(function ($type) {
                return [
                    'title' => $type->title,
                    'min_area' => $type->min_area,
                    'max_area' => $type->max_area,
                    'min_price' => $type->min_price,
                    'max_price' => $type->max_price,
                    'unit' => $type->unit,
                ];
            })->values()->toArray() ?? [],
            'featured_image' => $featured,
            'gallery' => $images,
            'description' => $description,
            'created_at' => optional($project->created_at)?->toISOString(),
            'updated_at' => optional($project->updated_at)?->toISOString(),
            'url' => $fullUrl,
        ];
    }

    private function buildTenantDocument(array $tenant): string
    {
        $lines = [];
        $lines[] = 'Name: ' . ($this->stringify($tenant['name']) ?: $this->stringify($tenant['username']));
        if (!empty($tenant['brand'])) $lines[] = 'Brand: ' . $this->stringify($tenant['brand']);
        if (!empty($tenant['about'])) $lines[] = 'About: ' . $this->normalizeText($this->stringify($tenant['about']));
        $contact = [];
        if (!empty($tenant['contact_email'])) $contact[] = $this->stringify($tenant['contact_email']);
        if (!empty($tenant['phone'])) $contact[] = $this->stringify($tenant['phone']);
        if ($contact) $lines[] = 'Contact: ' . implode(' | ', $contact);
        if (!empty($tenant['website'])) $lines[] = 'Website: ' . $this->stringify($tenant['website']);
        $addrParts = array_filter([
            $this->stringify($tenant['address'] ?? ''),
            $this->stringify($tenant['city'] ?? ''),
            $this->stringify($tenant['country'] ?? ''),
        ]);
        if ($addrParts) $lines[] = 'Address: ' . implode(', ', $addrParts);
        if (!empty($tenant['languages'])) {
            $langs = array_map(fn($l) => $this->stringify($l), (array) $tenant['languages']);
            $lines[] = 'Languages: ' . implode(', ', array_filter($langs));
        }
        return implode("\n", $lines);
    }

    private function buildPropertyDocument(array $p): string
    {
        $lines = [];
        $lines[] = 'Property: ' . ($p['title'] ?: ('#' . $p['id'])) . ' (ID: ' . $p['id'] . ')';
        if (!empty($p['url'])) {
            $lines[] = 'URL: ' . $p['url'];
        }
        if (!empty($p['address'])) $lines[] = 'Address: ' . $p['address'];
        $summary = [];
        if (!empty($p['purpose'])) $summary[] = 'Purpose: ' . $p['purpose'];
        if (!empty($p['type'])) $summary[] = 'Type: ' . $p['type'];
        if ($p['price'] !== null) $summary[] = 'Price: ' . $p['price'];
        if ($p['beds'] !== null) $summary[] = 'Beds: ' . $p['beds'];
        if ($p['bath'] !== null) $summary[] = 'Baths: ' . $p['bath'];
        if (!empty($p['area'])) $summary[] = 'Area: ' . $p['area'];
        if (!empty($p['size'])) $summary[] = 'Size: ' . $p['size'];
        if ($summary) $lines[] = implode(' | ', $summary);
        if (!empty($p['location']['latitude']) || !empty($p['location']['longitude'])) {
            $lines[] = 'Location: ' . ($p['location']['latitude'] ?? '') . ', ' . ($p['location']['longitude'] ?? '');
        }
        if (!empty($p['features'])) {
            $feat = array_map(fn($f) => $this->stringify($f), (array) $p['features']);
            $feat = array_filter($feat, fn($v) => $v !== '');
            if ($feat) $lines[] = 'Features: ' . implode(' - ', $feat);
        }
        if (!empty($p['characteristics'])) {
            $chars = $p['characteristics'];
            if (is_object($chars) && method_exists($chars, 'toArray')) {
                $chars = $chars->toArray();
            }
            if (is_array($chars)) {
                $lines[] = 'Characteristics:';
                // 1) If array of {name,value}
                $handled = false;
                foreach ($chars as $ck => $cv) {
                    if (is_array($cv) && isset($cv['name'], $cv['value'])) {
                        $lines[] = '- ' . $this->stringify($cv['name']) . ': ' . $this->stringify($cv['value']);
                        $handled = true;
                    }
                }
                if (!$handled) {
                    // 2) Key-value attributes
                    foreach ($chars as $ck => $cv) {
                        if (is_scalar($cv) && $cv !== '' && $cv !== null) {
                            $label = ucwords(str_replace('_', ' ', (string) $ck));
                            $lines[] = '- ' . $label . ': ' . $this->stringify($cv);
                        }
                    }
                }
            }
        }
        if (!empty($p['faqs']) && is_array($p['faqs'])) {
            $lines[] = 'FAQs:';
            foreach ($p['faqs'] as $faq) {
                if (is_array($faq) && isset($faq['question'], $faq['answer'])) {
                    $lines[] = '- Q: ' . $this->normalizeText($faq['question']);
                    $lines[] = '  A: ' . $this->normalizeText($faq['answer']);
                }
            }
        }
        if (!empty($p['description'])) {
            $lines[] = 'Description:';
            $lines[] = $this->normalizeText($p['description']);
        }
        if (!empty($p['gallery'])) {
            $lines[] = 'Images:';
            foreach ($p['gallery'] as $img) {
                $lines[] = '- ' . $img;
            }
        }
        if (!empty($p['floor_planning_image'])) {
            foreach ($p['floor_planning_image'] as $img) {
                $lines[] = '- ' . $img;
            }
        }
        return implode("\n", $lines);
    }

    private function buildProjectDocument(array $p): string
    {
        $lines = [];
        $lines[] = 'Project: ' . ($p['title'] ?: ('#' . $p['id'])) . ' (ID: ' . $p['id'] . ')';
        if (!empty($p['url'])) {
            $lines[] = 'URL: ' . $p['url'];
        }
        $meta = [];
        if (!empty($p['developer'])) $meta[] = 'Developer: ' . $p['developer'];
        $meta[] = 'Published: ' . (!empty($p['published']) ? 'yes' : 'no');
        $meta[] = 'Featured: ' . (!empty($p['featured']) ? 'yes' : 'no');
        $lines[] = implode(' | ', $meta);
        if ($p['min_price'] !== null || $p['max_price'] !== null) {
            $lines[] = 'Price range: ' . ($p['min_price'] ?? '') . ' – ' . ($p['max_price'] ?? '');
        }
        if (!empty($p['units'])) $lines[] = 'Units: ' . $p['units'];
        if (!empty($p['completion_date'])) $lines[] = 'Completion: ' . $p['completion_date'];
        if (!empty($p['location']['latitude']) || !empty($p['location']['longitude'])) {
            $lines[] = 'Location: ' . ($p['location']['latitude'] ?? '') . ', ' . ($p['location']['longitude'] ?? '');
        }
        if (!empty($p['amenities'])) {
            $am = array_map(fn($a) => $this->stringify($a), (array) $p['amenities']);
            $am = array_filter($am, fn($v) => $v !== '');
            if ($am) $lines[] = 'Amenities: ' . implode(' - ', $am);
        }
        if (!empty($p['specifications'])) {
            $lines[] = 'Specifications:';
            foreach ($p['specifications'] as $s) {
                if (is_array($s) && isset($s['title'], $s['value'])) {
                    $lines[] = '- ' . $s['title'] . ': ' . $s['value'];
                }
            }
        }
        if (!empty($p['types'])) {
            $lines[] = 'Types:';
            foreach ($p['types'] as $t) {
                $parts = [];
                if (!empty($t['title'])) $parts[] = $t['title'] . ':';
                if (isset($t['min_area']) || isset($t['max_area'])) {
                    $parts[] = ($t['min_area'] ?? '') . '–' . ($t['max_area'] ?? '') . ' ' . ($t['unit'] ?? '');
                }
                if (isset($t['min_price']) || isset($t['max_price'])) {
                    $parts[] = ($t['min_price'] ?? '') . '–' . ($t['max_price'] ?? '');
                }
                $lines[] = '- ' . implode(' | ', array_filter($parts));
            }
        }
        if (!empty($p['description'])) {
            $lines[] = 'Description:';
            $lines[] = $this->normalizeText($p['description']);
        }
        if (!empty($p['gallery'])) {
            $lines[] = 'Images:';
            foreach ($p['gallery'] as $img) {
                $lines[] = '- ' . $img;
            }
        }
        return implode("\n", $lines);
    }

    private function chunkDocuments(array $documents, int $chunkChars, int $overlap): array
    {
        $chunks = [];
        foreach ($documents as $doc) {
            $text = $this->normalizeText($doc);
            $len = mb_strlen($text);
            if ($len <= $chunkChars) {
                $chunks[] = $text;
                continue;
            }
            $start = 0;
            while ($start < $len) {
                $chunk = mb_substr($text, $start, $chunkChars);
                if ($chunk === '') break;
                $chunks[] = $chunk;
                if ($start + $chunkChars >= $len) break;
                $start += $chunkChars - $overlap;
                if ($start < 0) $start = 0;
            }
        }
        return $chunks;
    }

    private function normalizeText(?string $htmlOrText): string
    {
        $text = (string) ($htmlOrText ?? '');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/[ \t\x0B\f\r]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    private function stringify($value): string
    {
        if (is_null($value)) return '';
        if (is_scalar($value)) return (string) $value;
        if (is_object($value) && method_exists($value, '__toString')) return (string) $value;
        if (is_array($value)) return implode(', ', array_map(fn($v) => $this->stringify($v), $value));
        return '';
    }

    private function tenantFrontendBase(string $tenantId): ?string
    {
        $base = (string) env('FRONTEND_URL', '');
        if ($base === '') return null;
        $base = rtrim($base, '/');

        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($base, PHP_URL_HOST);
        if (!$host) {
            // If FRONTEND_URL is just a host like "taearif.com"
            $host = preg_replace('#^https?://#', '', $base);
        }
        // Drop leading www.
        $host = preg_replace('/^www\./i', '', (string) $host);

        // Sanitize tenant subdomain
        $sub = strtolower(preg_replace('/[^a-z0-9-]/i', '-', $tenantId));
        $sub = trim($sub, '-');
        if ($sub === '') return $scheme . '://' . $host;

        return $scheme . '://' . $sub . '.' . $host;
    }
}


