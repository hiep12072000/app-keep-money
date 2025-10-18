<?php

namespace App\Http\Controllers\APP_KEEP_MONEY;

use App\Http\Controllers\Controller;
use App\Interfaces\APP_KEEP_MONEY\NotificationInterface;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ResponseAPI;

    protected $notificationRepository;

    public function __construct(NotificationInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Get list of notifications (requires authentication)
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
            $type = $request->get('type');

            // Validate và convert pageNumber
            $page = $request->get('pageNumber', 1);
            $page = is_numeric($page) ? (int) $page : 1;
            $page = max(1, $page); // Đảm bảo pageNumber >= 1

            // Validate và convert pageSize
            $perPage = $request->get('pageSize', 10);
            $perPage = is_numeric($perPage) ? (int) $perPage : 10;
            $perPage = max(1, min(100, $perPage)); // Đảm bảo 1 <= pageSize <= 100

            // Sử dụng repository để lấy danh sách notifications
            return $this->notificationRepository->getList($user->id, $type, $page, $perPage);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark notification as seen (requires authentication)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function seen(Request $request, $id)
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::user();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            // Validate that $id is numeric
            if (!is_numeric($id)) {
                return $this->error('ID thông báo phải là số', 400);
            }

            $id = (int) $id;

            // Sử dụng repository để đánh dấu đã xem
            return $this->notificationRepository->seen($id, $user->id);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
