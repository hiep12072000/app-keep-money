<?php

namespace App\Repositories\APP_KEEP_MONEY;

use App\Interfaces\APP_KEEP_MONEY\FriendInterface;
use App\Models\APP_KEEP_MONEY\Friend;
use App\Models\APP_KEEP_MONEY\User;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

class FriendRepository implements FriendInterface
{
    use ResponseAPI;

    public function getList($userId, $keyword = null, $status = null, $page = 1, $perPage = 10)
    {
        try {
            // Query để lấy danh sách bạn bè
            $query = Friend::where(function($q) use ($userId) {
                    $q->where('sender_id', $userId)
                      ->orWhere('receiver_id', $userId);
                })
                ->with(['sender', 'receiver']);

            // Filter theo status nếu có
            if (!empty($status) && is_string($status)) {
                $query->where('status', $status);
            }

            // Tìm kiếm theo keyword nếu có
            if (!empty($keyword) && is_string($keyword)) {
                $query->whereHas('sender', function($q) use ($keyword) {
                    $q->where('full_name', 'LIKE', "%{$keyword}%")
                      ->orWhere('email', 'LIKE', "%{$keyword}%")
                      ->orWhere('phone', 'LIKE', "%{$keyword}%");
                })->orWhereHas('receiver', function($q) use ($keyword) {
                    $q->where('full_name', 'LIKE', "%{$keyword}%")
                      ->orWhere('email', 'LIKE', "%{$keyword}%")
                      ->orWhere('phone', 'LIKE', "%{$keyword}%");
                });
            }

            // Đếm tổng số records
            $total = $query->count();

            // Tính toán pagination
            $totalPage = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;

            // Lấy dữ liệu với pagination
            $friends = $query->orderBy('created_at', 'desc')
                           ->offset($offset)
                           ->limit($perPage)
                           ->get();

            // Format response theo yêu cầu
            $friendsData = $friends->map(function($friend) use ($userId) {
                // Xác định friendId và thông tin người bạn
                $isSender = $friend->sender_id == $userId;
                $friendUser = $isSender ? $friend->receiver : $friend->sender;

                return [
                    'id' => $friend->id,
                    'senderId' => $friend->sender_id,
                    'receiverId' => $friend->receiver_id,
                    'status' => $friend->status,
                    'createdAt' => $friend->created_at->format('Y-m-d\TH:i:s'),
                    'updatedAt' => $friend->updated_at ? $friend->updated_at->format('Y-m-d\TH:i:s') : null,
                    'deletedAt' => $friend->deleted_at ? $friend->deleted_at->format('Y-m-d\TH:i:s') : null,
                    'friendId' => $friendUser->id,
                    'fullName' => $friendUser->full_name,
                    'phone' => $friendUser->phone,
                    'email' => $friendUser->email,
                    'avatar' => $friendUser->avatar ? url('storage/' . $friendUser->avatar) : null,
                    'isOnline' => $friendUser->is_online ?? false,
                    'lastOnlineAt' => $friendUser->last_online_at ? $friendUser->last_online_at->format('Y-m-d\TH:i:s.u') : null,
                ];
            });

            $responseData = [
                'data' => $friendsData,
                'totalPage' => $totalPage,
                'total' => $total,
                'currentPage' => (int) $page,
            ];

            return $this->success("Get list friend successfully", $responseData);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    public function create($senderId, $phone)
    {
        try {
            // Tìm user theo phone
            $receiver = User::where('phone', $phone)->first();

            if (!$receiver) {
                return $this->error('Không tìm thấy người dùng với số điện thoại này', 404);
            }

            // Kiểm tra không thể gửi lời mời cho chính mình
            if ($receiver->id == $senderId) {
                return $this->error('Không thể gửi lời mời kết bạn cho chính mình', 400);
            }

            // Kiểm tra đã tồn tại friend request chưa
            $existingRequest = Friend::where(function($query) use ($senderId, $receiver) {
                $query->where('sender_id', $senderId)
                      ->where('receiver_id', $receiver->id);
            })->orWhere(function($query) use ($senderId, $receiver) {
                $query->where('sender_id', $receiver->id)
                      ->where('receiver_id', $senderId);
            })->first();

            if ($existingRequest) {
                if ($existingRequest->status == 'PENDING') {
                    return $this->error('Lời mời kết bạn đã được gửi trước đó', 400);
                } elseif ($existingRequest->status == 'ACCEPT') {
                    return $this->error('Hai người đã là bạn bè', 400);
                } elseif ($existingRequest->status == 'REJECT') {
                    // Nếu trước đó bị từ chối, có thể gửi lại
                    $existingRequest->update([
                        'sender_id' => $senderId,
                        'receiver_id' => $receiver->id,
                        'status' => 'PENDING',
                        'deleted_at' => null,
                    ]);
                    return $this->success('Gửi yêu cầu thành công', [], 201);
                }
            }

            // Tạo friend request mới
            Friend::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiver->id,
                'status' => 'PENDING',
            ]);

            return $this->success('Gửi yêu cầu thành công', [], 201);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    public function updateStatus($friendId, $status, $userId)
    {
        try {
            // Validate status
            $allowedStatuses = ['PENDING', 'ACCEPT', 'CANCEL'];
            if (!in_array($status, $allowedStatuses)) {
                return $this->error('Status không hợp lệ. Chỉ chấp nhận: PENDING, ACCEPT, CANCEL', 400);
            }

            // Validate friendId
            if (!is_numeric($friendId) || $friendId <= 0) {
                return $this->error('ID friend request không hợp lệ', 400);
            }

            // Tìm friend request
            $friendRequest = Friend::find($friendId);
            if (!$friendRequest) {
                return $this->error('Không tìm thấy friend request', 404);
            }

            // Kiểm tra quyền: chỉ sender hoặc receiver mới có thể update
            if ($friendRequest->sender_id != $userId && $friendRequest->receiver_id != $userId) {
                return $this->error('Bạn không có quyền cập nhật friend request này', 403);
            }

            // Kiểm tra logic business
            if ($status == 'ACCEPT' && $friendRequest->receiver_id != $userId) {
                return $this->error('Chỉ người nhận mới có thể chấp nhận friend request', 400);
            }

            if ($status == 'CANCEL' && $friendRequest->sender_id != $userId) {
                return $this->error('Chỉ người gửi mới có thể hủy friend request', 400);
            }

            // Cập nhật status
            $friendRequest->update([
                'status' => $status,
                'updated_at' => now()
            ]);

            // Format response message
            $statusMessages = [
                'PENDING' => 'Đã cập nhật trạng thái thành chờ xử lý',
                'ACCEPT' => 'Đã chấp nhận lời mời kết bạn',
                'CANCEL' => 'Đã hủy lời mời kết bạn'
            ];

            return $this->success($statusMessages[$status], [
                'id' => $friendRequest->id,
                'senderId' => $friendRequest->sender_id,
                'receiverId' => $friendRequest->receiver_id,
                'status' => $friendRequest->status,
                'updatedAt' => $friendRequest->updated_at->format('Y-m-d\TH:i:s')
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
