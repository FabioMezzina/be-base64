<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as ImageInt;
use App\User;
use App\Image;

// Validator class extension for base_64 validation (mimes type png and jpeg)
Validator::extend('custom_img',function($attribute, $value, $params, $validator) {
    $image = base64_decode($value);
    $f = finfo_open();
    $result = finfo_buffer($f, $image, FILEINFO_MIME_TYPE);
    return $result == 'image/png' || $result == 'image/jpeg';
});

class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $images = Image::all();
        dd($images);
        // return view('image.index', compact('images'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // return view('image.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validate img string and name
        $request->validate([
            'str' => 'required | custom_img',
            'name' => 'required | min: 6'
        ]);

        // get base64 string and image name from request
        $img_str = $request->input('str');
        $name = $request->input('name');

        // generate hash name
        $hash = "full_$name.png";

        // create array for fillable
        $data['hash'] = $hash;
        $data['img_url'] = Storage::url("full/$hash");
        $data['size'] = 'full';

        // save image on db
        $newImage = new Image();
        $newImage->fill($data);
        $saved = $newImage->save();
        if($saved) {
            // store image
            Storage::disk('public')->put("full/$hash", base64_decode($img_str));
        }

        return redirect()->route('home');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($name)
    {
        $hash = "full_$name.png";
        // dd($hash);
        $image = Image::where('hash', $hash)->first();
        dd($image);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Image $image)
    {
        dd($image);
        // return view('image.edit', compact('image'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Image $image)
    {
        // validation
        $request->validate([
            'str' => 'required | custom_img',
            'name' => 'required | min: 6'
        ]);

        $img_str = $request->input('str');
        $name = $request->input('name');
        
        // generate hash name
        $hash = "full_$name.png";
        $data['hash'] = $hash;
        // if new name != old name, delete old img
        if($data['hash'] != $image->hash) {
            Storage::disk('public')->delete($image->img_url);
        }
        
        // store new img
        Storage::disk('public')->put("full/$hash", base64_decode($img_str));

        // create array for fillable
        $data['img_url'] = Storage::url("full/$hash");
        $data['size'] = 'full';

        // update image record on db
        $updated = $image->update($data);
        if($updated) {
            dd('image updated correctly');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Image $image)
    {
        $deleted = $image->delete();
        if($deleted) {
            dd('image deleted correctly');
        }
    }
}
