<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function getcategory(Request $request)
    {

        $cat_name = $request->category_name;

       $new_category_model = Category::where('id', 20)->delete();
       
     

       return response()->json(['message' => ' Data deleteed successfully']);
    }

}
