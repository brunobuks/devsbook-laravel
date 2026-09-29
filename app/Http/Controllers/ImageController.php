<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;

class FotoController extends Controller
{
    public function upload(Request $request)
    {
        $upload = $request->file('photo');
        
        $image = Image::decode($upload)->resize(300, 200);
        
        $image->save(storage_path('app/public/photo/photo.jpg'));
        
        return back();
    }
}
