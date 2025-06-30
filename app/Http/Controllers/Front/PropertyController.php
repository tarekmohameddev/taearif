<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use App\Models\User\Language;
use App\Models\User\UserCity;
// use App\Models\User\RealestateManagement\Category;
use App\Models\Api\FooterSetting;
use App\Models\User\BasicSetting;
use App\Models\User\UserDistrict;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\CategoryVisibility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;
// use App\Models\User\RealestateManagement\City;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Api\ApiUserCategorySetting;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User\RealestateManagement\State;
use App\Models\User\RealestateManagement\Amenity;
use App\Models\User\RealestateManagement\Country;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertyContact;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertyWishlist;
use App\Models\User\RealestateManagement\ApiUserCategory as Category;

class PropertyController extends Controller
{

    public function getStatesByCity($city_id)
    {
        $states = UserDistrict::where('city_id', $city_id)
            ->whereHas('propertyContent', function ($query) {
                $query->whereHas('property', function ($q) {
                    $q->where('status', 1);
                });
            })
            ->select('id', 'name_ar', 'name_en')
            ->get();

        return response()->json($states);
    }

    public function index($website, Request $request,CategoryVisibility $visibility)
    {
        $user = getUser();
        $tenantId = $user->id;

        $userCurrentLang = session()->has('user_lang')
            ? Language::where('code', session('user_lang'))->where('user_id', $tenantId)->first()
            : null;

        if (empty($userCurrentLang)) {
            $userCurrentLang = Language::where('is_default', 1)->where('user_id', $tenantId)->first();
            session()->put('user_lang', $userCurrentLang->code);
        }

        $cityNameColumn = $userCurrentLang->code === 'ar' ? 'user_cities.name_ar' : 'user_cities.name_en';


        $projectId = $request->filled('project') ? intval($request->project) : null;

        $information['categories'] = $visibility->forTenant(
            $tenantId,$request,(bool) $user->show_even_if_empty
        );

        $information['amenities'] = Amenity::where('user_id', $tenantId)
            ->where('language_id', $userCurrentLang->id)
            ->where('status', 1)
            ->orderBy('serial_number')
            ->get();

        $propertyCategory = null;
        $category = null;
        if ($request->filled('category') && $request->category !== 'all') {
            $category = $request->category;
            $propertyCategory = Category::where('id', $category)
                ->first();
        }

        $amenityInContentId = [];
        if ($request->filled('amenities')) {
            $amenities = $request->amenities;
            $amenityInContentId = Amenity::whereIn('name', $amenities)
                ->where('language_id', $userCurrentLang->id)
                ->pluck('id')
                ->unique()
                ->toArray();
        }

        $type = $request->filled('type') && $request->type !== 'all' ? $request->type : null;
        $price = $request->filled('price') && $request->price !== 'all' ? $request->price : null;
        $purpose = $request->filled('purpose') && $request->purpose !== 'all' ? $request->purpose : null;

        $min = $request->filled('min') ? intval($request->min) : null;
        $max = $request->filled('max') ? intval($request->max) : null;

        $countryId = $stateId = $cityId = null;
        if ($request->filled('city_id')) {
            $cityId = intval($request->input('city_id'));
        }

        $stateId = $request->filled('state_id') ? intval($request->input('state_id')) : null;
        $cityId = $request->filled('city_id') ? intval($request->input('city_id')) : null;

        $title = $request->filled('title') ? $request->title : null;
        $location = $request->filled('location') ? $request->location : null;
        $beds = $request->filled('beds') ? $request->beds : null;
        $baths = $request->filled('baths') ? $request->baths : null;
        $area = $request->filled('area') ? $request->area : null;

        $sortOptions = [
            'new' => ['user_properties.id', 'desc'],
            'old' => ['user_properties.id', 'asc'],
            'high-to-low' => ['user_properties.price', 'desc'],
            'low-to-high' => ['user_properties.price', 'asc'],
        ];
        [$order_by_column, $order] = $sortOptions[$request->sort] ?? ['user_properties.id', 'desc'];

        $property_contents = Property::where([
            ['user_properties.user_id', $tenantId],
            ['user_properties.status', 1],
        ])

            ->join('user_property_contents', 'user_properties.id', '=', 'user_property_contents.property_id')
            ->leftJoin('user_cities', 'user_cities.id', '=', 'user_property_contents.city_id')
            ->leftJoin('user_states', 'user_states.id', '=', 'user_property_contents.state_id')
            ->leftJoin('user_countries', 'user_countries.id', '=', 'user_property_contents.country_id')
            ->where('user_property_contents.language_id', $userCurrentLang->id)

            ->when($type, fn($q) => $q->where('user_properties.type', $type))
            ->when($projectId, fn($q) => $q->where('user_properties.project_id', $projectId))
            ->when($purpose, fn($q) => $q->where('user_properties.purpose', $purpose))
            ->when($countryId, fn($q) => $q->where('user_property_contents.country_id', $countryId))
            ->when($stateId, fn($q) => $q->where('user_property_contents.state_id', $stateId))
            ->when($cityId, fn($q) => $q->where('user_property_contents.city_id', $cityId))
            ->when($category && $propertyCategory, fn($q) => $q->where('user_properties.category_id', $propertyCategory->id))
            ->when(!empty($amenityInContentId), function ($q) use ($amenityInContentId) {
                $q->whereHas(
                    'proertyAmenities',
                    fn($q2) =>
                    $q2->whereIn('amenity_id', $amenityInContentId),
                    '=',
                    count($amenityInContentId)
                );
            })
            ->when($price === 'negotiable', fn($q) => $q->whereNull('user_properties.price'))
            ->when($price === 'fixed', fn($q) => $q->whereNotNull('user_properties.price'))
            ->when($min && $max && ($price === 'fixed' || !$price), fn($q) => $q->whereBetween('user_properties.price', [$min, $max]))
            ->when($beds, fn($q) => $q->where('user_properties.beds', $beds))
            ->when($baths, fn($q) => $q->where('user_properties.bath', $baths))
            ->when($area, fn($q) => $q->where('user_properties.area', $area))
            ->when($title, fn($q) => $q->where('user_property_contents.title', 'LIKE', "%$title%"))
            ->when($location, fn($q) => $q->where('user_property_contents.address', 'LIKE', "%$location%"))
            ->selectRaw("
                user_properties.*,
                user_property_contents.title,
                user_property_contents.slug,
                user_property_contents.address,
                user_property_contents.description,
                user_property_contents.language_id,
                {$cityNameColumn} as city_name,
                user_states.name as state_name,
                user_countries.name as country_name
            ")
            ->orderBy($order_by_column, $order)
            ->paginate(12);

        $information['property_contents'] = $property_contents;
        $information['contents'] = $property_contents;

        $allCities = UserCity::all();
        $allCountries = UserDistrict::select('country_name_ar')->distinct()->get();
        $information['all_cities'] = $allCities;
        $information['all_countries'] = $allCountries;
        $information['all_cities'] = \App\Models\User\UserCity::whereHas('propertyContent', function ($query) use ($user) {
            $query->whereHas('property', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('status', 1);
            });
        })->get();
        $selectedCityId = request('city_id');
        if ($selectedCityId) {
            $allStates = \App\Models\User\UserDistrict::where('city_id', $selectedCityId)->select('id', 'name_ar')->get();
        } else {
            $allStates = \App\Models\User\UserDistrict::select('id', 'name_ar')->get();
        }

        $information['all_states'] = \App\Models\User\UserDistrict::whereHas('propertyContent', function ($query) use ($user) {
            $query->whereHas('property', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('status', 1);
            });
        })->get();

        $priceRange = Property::where('user_id', $tenantId)
            ->where('status', 1)
            ->selectRaw('MIN(price) as min, MAX(price) as max')
            ->first();

        $information['min'] = intval($priceRange->min);
        $information['max'] = intval($priceRange->max);

        $information['projects'] = Project::with(['content'])
            ->where('user_id', $tenantId)
            ->whereHas('properties', function ($query) use ($tenantId, $userCurrentLang) {
                $query->where('user_id', $tenantId)
                    ->where('status', 1)
                    ->whereHas('contents', function ($subQuery) use ($userCurrentLang) {
                        $subQuery->where('language_id', $userCurrentLang->id);
                    });
            })
            ->get();

        if ($request->ajax()) {
            $viewContent = View::make('user-front.realestate.property.contents', $information)->render();
            return response()->json([
                'propertyContents' => $viewContent,
                'properties' => $property_contents
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        return view('user-front.realestate.property.index', $information + ['website' => $website]);
    }

    public function details($website, $slug)
    {
        $user = getUser();
        $tenantId = $user->id;

        if (session()->has('user_lang')) {
            $userCurrentLang = Language::where('code', session()->get('user_lang'))->where('user_id', $tenantId)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = Language::where('is_default', 1)->where('user_id', $tenantId)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = Language::where('is_default', 1)->where('user_id', $tenantId)->first();
        }

        $cityNameColumn = $userCurrentLang->code === 'ar' ? 'user_cities.name_ar' : 'user_cities.name_en';

        $property = PropertyContent::query()
        ->where('user_property_contents.slug', $slug)
        ->where('user_property_contents.language_id', $userCurrentLang->id)
        ->leftJoin('user_properties', 'user_property_contents.property_id', 'user_properties.id')
        ->where([['user_properties.status', 1], ['user_properties.user_id', $tenantId]])
        ->leftJoin('user_property_categories', 'user_property_categories.id', 'user_property_contents.category_id')
        ->leftJoin('user_cities', 'user_cities.id', '=', 'user_property_contents.city_id')
        ->leftJoin('user_states', 'user_states.id', '=', 'user_property_contents.state_id')
        ->leftJoin('user_countries', 'user_countries.id', '=', 'user_property_contents.country_id')
        ->with([
            'propertySpacifications',
            'galleryImages',
            'property.userPropertyCharacteristics.UserFacade'
        ])
        ->select('user_properties.*', 'user_property_contents.*', 'user_properties.id as propertyId', 'user_property_contents.id as contentId')
        ->firstOrFail();



        $information['propertyContent'] = $property;

        $information['sliders'] =  $property->galleryImages;

        $information['amenities'] = PropertyAmenity::with(['amenity' => function ($q) use ($userCurrentLang) {
            $q->where('language_id', $userCurrentLang->id);
        }])->where('property_id', $property->property_id)->get();

        $information['user'] = $user;

        $information['userApi_footerData_general_phone'] = FooterSetting::where('user_id', $user->id)->value('general')['phone'];

        $categories = Category::where('is_active', 1)->get();
        $categories->map(function ($category) use ($user) {
            // $category['propertiesCount'] = $category->properties()->where([['is_active', 1]])->count();
            $category['propertiesCount'] = $category->properties()->where('status', 1)->count();
        });
        $information['categories'] = $categories;

        // $information['relatedProperty'] = Property::where([['user_properties.status', 1], ['user_properties.approve_status', 1]])
        $information['relatedProperty'] = Property::where([['user_properties.status', 1]])
            ->leftJoin('user_property_contents', 'user_properties.id', 'user_property_contents.property_id')


            ->leftJoin('user_cities', 'user_cities.id', '=', 'user_property_contents.city_id')
            ->leftJoin('user_states', 'user_states.id', '=', 'user_property_contents.state_id')
            ->leftJoin('user_countries', 'user_countries.id', '=', 'user_property_contents.country_id')

            ->where([['user_properties.id', '!=', $property->property_id], ['user_property_contents.category_id', $property->category_id]])
            ->where('user_property_contents.language_id', $userCurrentLang->id)->latest('user_properties.created_at')
            ->selectRaw("
                user_properties.*,
                user_property_contents.title,
                user_property_contents.slug,
                user_property_contents.address,
                user_property_contents.language_id,
                {$cityNameColumn} as city_name,
                user_states.name as state_name,
                user_countries.name as country_name
            ")
            ->take(5)->get();

        // $information['info'] = Basic::select('google_recaptcha_status')->first();

        return view('user-front.realestate.property.details', $information);
    }

    public function contact($website, Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email:rfc,dns',
            'phone' => 'required|numeric',
            'message' => 'required'
        ];
        $user = getUser();

        $info = BasicSetting::where('user_id', $user->id)->select('is_recaptcha')->first();
        if ($info->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }

        $messages = [];

        if ($info->is_recaptcha == 1) {
            $messages['g-recaptcha-response.required'] = 'Please verify that you are not a robot.';
            $messages['g-recaptcha-response.captcha'] = 'Captcha error! try again later or contact site admin.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }


        $request['to_mail'] = $user->email;

        try {
            PropertyContact::createContact($user->id,  $request->all());
            $this->sendMail($request);
        } catch (\Exception $e) {
            // return back()->with('error', $e->getMessage());
            return back()->with('error', 'Something went wrong!');
        }



        return back()->with('success', 'Message sent successfully');
    }

    public function buynow($website, Request $request)
    {
        $user = getUser();

        $info = BasicSetting::where('user_id', $user->id)->select('is_recaptcha')->first();
        $messages = [];



        $request['to_mail'] = $user->email;

        try {
            PropertyContact::createContact($user->id,  $request->all());
            $this->sendMail($request);
        } catch (\Exception $e) {
            // return back()->with('error', $e->getMessage());
            return back()->with('error', 'Something went wrong!');
        }



        return back()->with('success', 'Message sent successfully');
    }

    public function contactUser(Request $request)
    {

        $rules = [
            'name' => 'required',
            'email' => 'required|email:rfc,dns',
            'phone' => 'required|numeric',
            'message' => 'required'
        ];
        $info = Basic::select('google_recaptcha_status')->first();
        if ($info->google_recaptcha_status == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }

        $messages = [];

        if ($info->google_recaptcha_status == 1) {
            $messages['g-recaptcha-response.required'] = 'Please verify that you are not a robot.';
            $messages['g-recaptcha-response.captcha'] = 'Captcha error! try again later or contact site admin.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }
        if ($request->vendor_id != 0) {

            if ($request->vendor_id) {
                $vendor = Vendor::find($request->vendor_id);

                if (empty($vendor)) {

                    return back()->with('error', 'Something went wrong!');
                }
                $request['to_mail'] = $vendor->email;
            }
        } else {
            $admin = Admin::where('role_id', null)->first();
            $request['to_mail'] = $admin->email;
        }
        if (!empty($request->agent_id)) {
            $agent = Agent::find($request->agent_id);
            if (empty($agent)) {
                return back()->with('error', 'Something went wrong!');
            }
            $request['to_mail'] = $agent->email;
        }

        try {
            $this->sendMail($request);
        } catch (\Exception $e) {
            return back()->with('error', 'Something went wrong!');
        }



        return back()->with('success', 'Message sent successfully');
    }

    private function sendMail($request)
    {

        $info = DB::table('basic_extendeds')
            ->select('is_smtp', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name', 'to_mail')
            ->first();
        $name = $request->name;
        $to = $request->to_mail;

        $subject = 'Contact for property';

        $message = '<p>A new message has been sent.<br/><strong>Client Name: </strong>' . $name . '<br/><strong>Client Mail: </strong>' . $request->email . '<br/><strong>Client Phone: </strong>' . $request->phone . '</p><p>Message : ' . $request->message . '</p>';

        $data = [
            'toMail' => $to,
            'subject' => $subject,
            'message' => $message,
        ];
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
        if ($info->is_smtp == 1) {
            try {
                $mail->isSMTP();
                $mail->Host       = $info->smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $info->smtp_username;
                $mail->Password   = $info->smtp_password;
                $mail->SMTPSecure = $info->encryption;
                $mail->Port       = $info->smtp_port;
            } catch (\Exception $e) {
                Session::flash('error', $e->getMessage());
                return back();
            }
        }
        try {
            //Recipients
            $mail->setFrom($info->from_mail, $info->from_name);
            $mail->addAddress($data['toMail'],);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $data['subject'];
            $mail->Body    = $data['message'];
            $mail->send();
        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
            return back();
        }
    }

    public function getStateCities(Request $request)
    {
        $userId = getUser()->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = Language::where('code', session()->get('user_lang'))->where('user_id', $userId)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = Language::where(
                    'is_default',
                    1
                )->where('user_id', $userId)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = Language::where('is_default', 1)->where('user_id', $userId)->first();
        }

        $states = State::where('country_id', $request->id)->where('language_id', $userCurrentLang->id)->with(['cities' => function ($q) use ($userCurrentLang) {
            $q->where('language_id', $userCurrentLang->id);
        }])->get();

        $cities = UserCity::all();

        return Response::json(['states' => $states, 'cities' => $cities], 200);
    }

    public function getCities(Request $request)
    {
        $userId = getUser()->id;
        if (session()->has('user_lang')) {
            $language = Language::where('code', session()->get('user_lang'))
                ->where('user_id', $userId)
                ->first();

            if (empty($language)) {
                $language = Language::where('is_default', 1)
                    ->where('user_id', $userId)
                    ->first();

                session()->put('user_lang', $language->code);
            }
        } else {
            $language = Language::where('is_default', 1)
                ->where('user_id', $userId)
                ->first();
        }

        $cities = UserCity::all();

        return Response::json(['cities' => $cities], 200);
    }

    public function getCategories(Request $request)
    {
        $userId = getUser()?->id;

        if (session()->has('user_lang')) {
            $userCurrentLang = Language::where('code', session()->get('user_lang'))->where('user_id', $userId)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = Language::where('is_default', 1)->where('user_id', $userId)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = Language::where('is_default', 1)->where('user_id', $userId)->first();
        }

        if ($request->type != 'all') {
            $categories = Category::where('type', $request->type)
                ->select('id', 'type', 'name', 'slug')
                ->orderBy('name', 'ASC')
                ->get();
        } else {
            $categories = Category::where('is_active', true)
                ->select('id', 'type', 'name', 'slug')
                ->orderBy('name', 'ASC')
                ->get();
        }


        return Response::json(['categories' => $categories], 200);
    }

    public function showJson($id)
    {
        $project = Project::findOrFail($id);
        return response()->json($project);
    }

}
