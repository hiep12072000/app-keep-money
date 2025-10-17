<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    use ResponseAPI;

    /**
     * Upload image file
     *
     * @param UploadImageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(UploadImageRequest $request)
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::guard('api')->user();

            $file = $request->file('file');

            // Tạo tên file unique
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

            // Tạo thư mục theo năm/tháng
            $directory = 'uploads/images/' . date('Y/m');

            // Lưu file vào storage/app/public
            $filePath = $file->storeAs($directory, $fileName, 'public');

            // Tạo full URL
            $fullUrl = url('storage/' . $filePath);

            // Thông tin file
            $fileInfo = [
                'original_name' => $file->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'url' => $fullUrl,
                'uploaded_by' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                ],
                'uploaded_at' => now()->toDateTimeString(),
            ];

            return $this->success('Upload ảnh thành công', $fileInfo, 201);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra khi upload: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete uploaded file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'file_path' => 'required|string'
            ]);

            $filePath = $request->file_path;

            // Kiểm tra file có tồn tại không
            if (!Storage::disk('public')->exists($filePath)) {
                return $this->error('File không tồn tại', 404);
            }

            // Xóa file
            Storage::disk('public')->delete($filePath);

            return $this->success('Xóa file thành công', [
                'file_path' => $filePath,
                'deleted_at' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra khi xóa file: ' . $e->getMessage(), 500);
        }
    }
}
