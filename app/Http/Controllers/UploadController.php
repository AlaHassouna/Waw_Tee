<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Quality;

class UploadController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            'folder' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $file = $request->file('file');
            $folder = $request->get('folder', 'ecommerce');
        
            $uploadOptions = [
                'folder' => $folder,
                'resource_type' => 'auto',
                'quality' => 'auto:good',
                'fetch_format' => 'auto',
            ];

            $result = $this->cloudinary->uploadApi()->upload(
                $file->getPathname(),
                $uploadOptions
            );

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => [
                    'url' => $result['secure_url'],
                    'publicId' => $result['public_id'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function delete($publicId)
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);

            if ($result['result'] === 'ok') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'File deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete file',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
