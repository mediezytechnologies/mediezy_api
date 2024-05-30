<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bannermodel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class BannersController extends Controller
{
    public function getallBanner()
    {

        $Banner = Bannermodel::all();
        $data = [
            'status' => 200,
            'Banner' => $Banner,

        ];

        //  return response()->json($data,200);
        return view('DashboardBanner.Banner_add');
    }

    public function addBanner(Request $request)
    {

        $Banners = Bannermodel::all();
        $validator = Validator::make($request->all(), [
            'banner_title' => 'required',
            'banner_image' => 'required',
            'banner_type' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }
    
        if ($request->hasFile('banner_image')) {
            $banner_image = $request->file('banner_image');
            $banner_image_name = time() . '_' . $banner_image->getClientOriginalName();
            $banner_image->move(public_path('img'), $banner_image_name);
        }
    
        $Banner = new Bannermodel();
        $Banner->banner_title = $request->banner_title;
        $Banner->banner_image = $banner_image_name;
        $Banner->banner_type = $request->banner_type;
        $Banner->save();
    
        $message = 'Banner added successfully';

    
    
         return view('DashboardBanner.Banner_view', ['Banners' => $Banners],compact('Banner'))->with('success', $message);

      
}

public function updateBanner($id)
    {

    //     $Flags = Flag::all();
    //   return view('flags.flag_listview');

    $banners = Bannermodel::all();
    $banner = Bannermodel::findOrFail($id);
    // return view('flags.flag_update', ['flags' => $flags]);
    return view('DashboardBanner.Banner_update', ['banners' => $banner]);
}
}
