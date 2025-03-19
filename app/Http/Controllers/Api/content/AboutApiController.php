<?php

namespace App\Http\Controllers\Api\content;

use App\Models\Api\ApiAboutSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class AboutApiController extends Controller
{
    public function index(Request $request)
    {
        // Get the about data or create default if not exists
        $user = $request->user();
        $about = ApiAboutSettings::where('user_id', $user->id)->first();
        
        if (!$about) {
            // Default features
            $defaultFeatures = [
                ['id' => 1, 'title' => 'الجودة', 'description' => 'نلتزم بتقديم أعلى معايير الجودة في جميع منتجاتنا وخدماتنا.'],
                ['id' => 2, 'title' => 'الابتكار', 'description' => 'نسعى دائمًا لتطوير حلول مبتكرة تلبي احتياجات عملائنا المتغيرة.'],
                ['id' => 3, 'title' => 'الموثوقية', 'description' => 'يمكنك الاعتماد علينا لتقديم النتائج في الوقت المحدد وبالميزانية المتفق عليها.'],
            ];
            
            $about = ApiAboutSettings::create([
                'user_id' => $user->id,
                'title' => 'عن شركتنا',
                'subtitle' => 'قصتنا ورسالتنا',
                'history' => 'تأسست شركتنا في عام 2010، ونمت من شركة ناشئة صغيرة إلى مزود رائد في مجالنا. لقد التزمنا دائمًا بالجودة والابتكار.',
                'mission' => 'مهمتنا هي تقديم منتجات وخدمات استثنائية تحسن حياة عملائنا مع الحفاظ على أعلى معايير الجودة ورضا العملاء.',
                'vision' => 'نتطلع إلى أن نصبح الشركة الرائدة في مجالنا، معروفين بالابتكار والجودة والخدمة الاستثنائية للعملاء.',
                'image_path' => null,
                'features' => $defaultFeatures,
            ]);
        }
        
        // Format the response
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'about' => $about
            ]
        ]);
    }

    /**
     * Update about page content
     */
    public function update(Request $request)
    {
        $user = $request->user();
   
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'history' => 'nullable|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
            'image' => 'nullable|string',
            'features' => 'required|array',
            'features.*.id' => 'required|integer',
            'features.*.title' => 'required|string|max:255',
            'features.*.description' => 'required|string'
        ]);


        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            
            // Get or create the about record
            $about = ApiAboutSettings::where('user_id', $user->id)->first();
            if (!$about) {
                $about = new ApiAboutSettings();
                $about->user_id = $user->id;
            }
            
            // Update about data
            $about->title = $request->title;
            $about->subtitle = $request->subtitle;
            $about->history = $request->history;
            $about->mission = $request->mission;
            $about->vision = $request->vision;
            $about->features = $request->features;
            $about->image_path = $request->image;

            $about->save();
             
            return response()->json([
                'status' => 'success',
                'message' => 'About page updated successfully',
                'data' => [
                    'about' => $about
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update about page',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}