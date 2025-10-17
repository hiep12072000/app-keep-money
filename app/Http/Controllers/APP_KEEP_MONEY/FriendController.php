<?php

namespace App\Http\Controllers\APP_KEEP_MONEY;

use App\Http\Controllers\Controller;
use App\Http\Requests\APP_KEEP_MONEY\FriendRequest as FriendRequestValidation;
use App\Interfaces\APP_KEEP_MONEY\FriendInterface;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    use ResponseAPI;

    protected $friendRepository;

    public function __construct(FriendInterface $friendRepository)
    {
        $this->friendRepository = $friendRepository;
    }

    /**
     * Get list of friends (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::user();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            // Lấy parameters từ request với validation
            $keyword = $request->get('keyword');
            $status = $request->get('status');

            // Validate và convert page
            $page = $request->get('page', 1);
            $page = is_numeric($page) ? (int) $page : 1;
            $page = max(1, $page); // Đảm bảo page >= 1

            // Validate và convert per_page
            $perPage = $request->get('per_page', 10);
            $perPage = is_numeric($perPage) ? (int) $perPage : 10;
            $perPage = max(1, min(100, $perPage)); // Đảm bảo 1 <= per_page <= 100

            // Sử dụng repository để lấy danh sách friends
            return $this->friendRepository->getList($user->id, $keyword, $status, $page, $perPage);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create friend request (requires authentication)
     *
     * @param FriendRequestValidation $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(FriendRequestValidation $request)
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::user();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            // Validate request data
            $data = $request->validated();

            // Sử dụng repository để tạo friend request
            return $this->friendRepository->create($user->id, $data['phone']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update friend request status (requires authentication)
     *
     * @param Request $request
     * @param int $id
     * @param string $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id, $status)
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::user();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            // Validate that $id is numeric
            if (!is_numeric($id)) {
                return $this->error('ID friend request phải là số', 400);
            }

            $id = (int) $id;

            // Validate status parameter
            $allowedStatuses = ['PENDING', 'ACCEPT', 'CANCEL'];
            if (!in_array($status, $allowedStatuses)) {
                return $this->error('Status không hợp lệ. Chỉ chấp nhận: PENDING, ACCEPT, CANCEL', 400);
            }

            // Sử dụng repository để cập nhật status
            return $this->friendRepository->updateStatus($id, $status, $user->id);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
