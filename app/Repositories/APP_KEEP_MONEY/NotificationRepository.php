<?php

namespace App\Repositories\APP_KEEP_MONEY;

use App\Interfaces\APP_KEEP_MONEY\NotificationInterface;
use App\Models\APP_KEEP_MONEY\Notification;
use App\Traits\ResponseAPI;

class NotificationRepository implements NotificationInterface
{
    use ResponseAPI;

    public function getList($userId, $type = null, $page = 1, $perPage = 10)
    {
        try {
            // Query builder để lấy notifications của user
            $query = Notification::where('receive_user_id', $userId);

            // Filter theo type nếu có
            if (!empty($type) && is_string($type)) {
                $query->where('type', $type);
            }

            // Đếm tổng số records
            $total = $query->count();

            // Tính toán pagination
            $totalPage = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;

            // Lấy dữ liệu với pagination
            $notifications = $query->orderBy('created_at', 'desc')
                                 ->offset($offset)
                                 ->limit($perPage)
                                 ->get();

            // Format response theo yêu cầu
            $notificationsData = $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'receiveUserId' => $notification->receive_user_id,
                    'content' => $notification->content,
                    'params' => $notification->params ?? [],
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'isSeen' => (bool) $notification->is_seen,
                    'createdAt' => $notification->created_at->format('Y-m-d\TH:i:s'),
                ];
            });

            $responseData = [
                'data' => $notificationsData,
                'totalPage' => $totalPage,
                'total' => $total,
                'currentPage' => (int) $page,
            ];

            return $this->success("Get list notification successfully!", $responseData);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    public function seen($notificationId, $userId)
    {
        try {
            // Validate notificationId
            if (!is_numeric($notificationId) || $notificationId <= 0) {
                return $this->error('ID thông báo không hợp lệ', 400);
            }

            // Tìm notification
            $notification = Notification::find($notificationId);
            if (!$notification) {
                return $this->error('Không tìm thấy thông báo', 404);
            }

            // Kiểm tra quyền: chỉ người nhận mới có thể đánh dấu đã xem
            if ($notification->receive_user_id != $userId) {
                return $this->error('Bạn không có quyền đánh dấu thông báo này', 403);
            }

            // Kiểm tra xem đã được đánh dấu chưa
            if ($notification->is_seen) {
                return $this->error('Thông báo đã được đánh dấu đã xem', 400);
            }

            // Cập nhật trạng thái đã xem
            $notification->update([
                'is_seen' => true,
                'updated_at' => now()
            ]);

            return $this->success('Đánh dấu thông báo đã xem thành công', [
                'id' => $notification->id,
                'receiveUserId' => $notification->receive_user_id,
                'isSeen' => true,
                'updatedAt' => $notification->updated_at->format('Y-m-d\TH:i:s')
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
