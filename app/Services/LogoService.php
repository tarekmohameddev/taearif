<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\BasicSetting;

class LogoService
{
    public static function updateLogoAndFavicon(Request $request, $user)
    {
        try {
            // Validate files if provided
            if ($request->hasFile('logo')) {
                $request->validate([
                    'logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);
            }

            if ($request->hasFile('favicon')) {
                $request->validate([
                    'favicon' => 'image|mimes:jpeg,png,jpg,gif|max:512',
                ]);
            }

            // Fetch or create BasicSetting for the user
            $bss = BasicSetting::firstOrCreate(['user_id' => $user->id]);

            // Handle Logo Upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($bss->logo) {
                    Storage::disk('public')->delete($bss->logo);
                }

                // Upload new logo
                $logoFile = $request->file('logo');
                $logoFilename = $logoFile->store('logos', 'public');
                $bss->logo = $logoFilename;
            }

            // Handle Favicon Upload
            if ($request->hasFile('favicon')) {
                // Delete old favicon if exists
                if ($bss->favicon) {
                    Storage::disk('public')->delete($bss->favicon);
                }

                // Upload new favicon
                $faviconFile = $request->file('favicon');
                $faviconFilename = $faviconFile->store('favicons', 'public');
                $bss->favicon = $faviconFilename;
            }

            // Save updates
            $bss->save();

            return [
                'logo' => $bss->logo,
                'favicon' => $bss->favicon
            ];
        } catch (\Exception $e) {
            \Log::error("File upload failed: " . $e->getMessage());
            return false;
        }
    }
}
