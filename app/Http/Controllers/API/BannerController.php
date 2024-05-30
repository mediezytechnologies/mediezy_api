<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bannermodel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class BannerController extends Controller
{
    public function getAllUserBanner()
    {
        $banners = Bannermodel::all();
        return response()->json(['status' => 200, 'user_banners' => $banners]);
    }

    // public function addUserBanner(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'banner_title' => 'required',
    //         'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'banner_type' => 'required|in:1,2,3',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
    //     }
    //     if ($request->hasFile('banner_image')) {
    //         $imageFile = $request->file('banner_image');
    
    
    //         if ($imageFile->isValid()) {
    //             $bannerImageName = $imageFile->getClientOriginalName();
    //             $imageFile->move(public_path('img'), $bannerImageName);
    //             $doctor->doctor_image = $bannerImageName;
    //         }
    //     }
      

    //     $Banner = new Bannermodel();
    //     $Banner->banner_title = $request->banner_title;
    //     $Banner->banner_image = $bannerImageName;
    //     $Banner->banner_type = $request->banner_type;
    //     $Banner->save();

    //     $message = 'Banner added successfully';
    //     return response()->json(['status' => true, 'message' => $message]);
    // }
    // public function addUserBanner(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'banner_title' => 'required',
    //         'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'banner_type' => 'required|in:1,2,3',
    //     ]);
    
    //     if ($validator->fails()) {
    //         return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
    //     }
    //     if ($request->hasFile('banner_image')) {
    //         $imageFile = $request->file('banner_image');
    
          
    //         if ($imageFile->isValid()) {
             
    //             $bannerImageName = time() . '_' . $imageFile->getClientOriginalName();
             
    //             $imageFile->move(public_path('img'), $bannerImageName);
    //         } 
    //     } 
    //     $banner = new Bannermodel();
    //     $banner->banner_title = $request->banner_title;
    //     $banner->banner_image = $bannerImageName;
    //     $banner->banner_type = $request->banner_type;
    //     $banner->save();
    //     $message = 'Banner added successfully';
    //     return response()->json(['status' => true, 'message' => $message]);
    // }
    public function addUserBanner(Request $request)
{
    $validator = Validator::make($request->all(), [
        'banner_title' => 'required',
        'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'banner_type' => 'required|in:1,2,3',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
    }

    if ($request->hasFile('banner_image')) {
        $imageFile = $request->file('banner_image');

        if ($imageFile->isValid()) {
            $bannerImageName = time() . '_' . $imageFile->getClientOriginalName();
            $imageFile->move(public_path('img'), $bannerImageName);
        }
    }

    $banner = new Bannermodel();
    $banner->banner_title = $request->banner_title;
    // Remove '/storage' from the image URL
    $banner->banner_image = 'img/' . $bannerImageName;
    $banner->banner_type = $request->banner_type;
    $banner->save();

    $message = 'Banner added successfully';
    return response()->json(['status' => true, 'message' => $message]);
}

    public function getUserBannerByType($banner_type)
    {
        $Banners = Bannermodel::where('banner_type', $banner_type)->get();

        if ($Banners->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No banners found for the specified type.']);
        }

        $bannerImages = [];

        foreach ($Banners as $Banner) {
            $bannerImages[] = asset('img/' . $Banner->banner_image);
        }

        return response()->json(['status' => true, 'banner_images' => $bannerImages]);
    }

    public function deleteBannersByType($banner_type)
{
   
    $deletedCount = Bannermodel::where('banner_type', $banner_type)->delete();

    if ($deletedCount === 0) {
        return response()->json(['status' => false, 'message' => 'No banners found for the specified type.']);
    }

    return response()->json(['status' => true, 'message' => 'Banners deleted successfully']);
}


public function updateBannerByType($banner_type, $new_banner_type)
{
   
    $updatedCount = Bannermodel::where('banner_type', $banner_type)->update(['banner_type' => $new_banner_type]);

    if ($updatedCount === 0) {
        return response()->json(['status' => false, 'message' => 'No banners found for the specified type.']);
    }

    return response()->json(['status' => true, 'message' => 'Banner types updated successfully']);
}

// public function updateBannersByType(Request $request, $banner_type)
// {
//     $validator = Validator::make($request->all(), [
//         'new_banner_title' => 'required',
//         'new_banner_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
//         'new_banner_type' => 'required|in:1,2,3',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
//     }

//     // Find banners where banner_type matches $banner_type
//     $banners = Bannermodel::where('banner_type', $banner_type)->get();

//     if ($banners->isEmpty()) {
//         return response()->json(['status' => false, 'message' => 'No banners found for the specified type.']);
//     }

//     $newBannerImageName = '';

//     if ($request->hasFile('new_banner_image')) {
//         $newBannerImage = $request->file('new_banner_image');
//         $newBannerImageName = time() . '_' . $newBannerImage->getClientOriginalName();
//         $newBannerImage->storeAs('public/img', $newBannerImageName);
//     } else {
//         return response()->json(['status' => false, 'message' => 'New banner image is required.']);
//     }

//     // Update each banner with new values
//     foreach ($banners as $banner) {
//         $banner->banner_title = $request->new_banner_title;
//         $banner->banner_image = $newBannerImageName;
//         $banner->banner_type = $request->new_banner_type;
//         $banner->save();
//     }

//     return response()->json(['status' => true, 'message' => 'Banners updated successfully']);
// }



}


