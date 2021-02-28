<?php

namespace App\CustomHelpers;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Helpers
{
    /*
        Function uploadImage for upload image to spesific folder
    */
    public static function uploadImage($image, $path_upload, $image_dimensions = false, $oldimage = false, $path_delete = false){
        if (! File::isDirectory($path_upload)) {
            File::makeDirectory($path_upload, 0777, true);
        }

        $extension = $image->getClientOriginalExtension();
        $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $filenametostore = Str::slug($filename) . '-' . time() . '.' . $extension;

        // Upload File
        $filepath = Image::make($image)->save($path_upload . '/' . $filenametostore);

        if($filepath){

            // Delete old image
            if ($oldimage && $path_delete) {
                Storage::delete($path_delete . '/' . $oldimage);
            }

            if (is_array($image_dimensions) && ! empty($image_dimensions)) {
                foreach ($image_dimensions as $key => $dimension) {

                    // Delete old image
                    if ($oldimage && $path_delete) {
                        Storage::delete($path_delete . '/' . $dimension . '/' . $oldimage);
                    }

                    $resizeImage = Image::make($image)->resize($dimension, $dimension, function($constraint) {
                        $constraint->aspectRatio();
                    });

                    if (! File::isDirectory($path_upload . '/' . $dimension)) {
                        File::makeDirectory($path_upload . '/' . $dimension);
                    }

                    $resizeImage->save($path_upload . '/' . $dimension . '/' . $filenametostore);
                }
            }
        }

        return ($filepath) ? $filenametostore : false;
    }

    /*
        Function deleteUploadImage for unlink image in spesific folder in server
    */
    public static function deleteUploadImage($oldimage, $path_delete, $image_dimensions = false){
        $output = Storage::delete($path_delete . '/' . $oldimage);

        if ($image_dimensions && is_array($image_dimensions) && ! empty($image_dimensions)) {
            foreach ($image_dimensions as $key => $item) {
                Storage::delete($path_delete . '/' . $item . '/' . $oldimage);
            }
        }

        return $output;
    }
}
