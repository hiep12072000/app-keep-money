<?php

namespace App\Repositories\APP_KEEP_MONEY;

use App\Http\Requests\APP_KEEP_MONEY\UserRequest;
use App\Interfaces\APP_KEEP_MONEY\UserInterface;
use App\Models\APP_KEEP_MONEY\User;
use App\Traits\ResponseAPI;
use DB;

class UserRepository implements UserInterface
{
    // Use ResponseAPI Trait in this repository
    use ResponseAPI;

    public function getAllUsers($page = 1, $perPage = 10)
    {
        try {
            // Query builder để lấy users
            $query = User::query()
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereNotNull('phone')
                ->where('phone', '!=', '');

            // Đếm tổng số records
            $total = $query->count();

            // Tính toán pagination
            $totalPage = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;

            // Lấy dữ liệu với pagination
            $users = $query->orderBy('created_at', 'desc')
                          ->offset($offset)
                          ->limit($perPage)
                          ->get();

            // Format response theo yêu cầu
            $usersData = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'createdAt' => $user->created_at->format('Y-m-d\TH:i:s'),
                    'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
                    'tokenFcm' => $user->token_fcm,
                    'isOnline' => $user->is_online ?? false,
                    'lastOnlineAt' => $user->last_online_at ? $user->last_online_at->format('Y-m-d\TH:i:s.u') : null,
                ];
            });

            $responseData = [
                'data' => $usersData,
                'totalPage' => $totalPage,
                'total' => $total,
                'currentPage' => (int) $page,
            ];

            return $this->success("Lấy danh sách tất cả người dùng thành công", $responseData);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function getUserById($id)
    {
        try {
            $user = User::find($id);

            // Check the user
            if(!$user) return $this->error("Không tìm thấy người dùng", 404);

            return $this->success("Lấy thông tin thành công", $user);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function requestUser(UserRequest $request, $id = null)
    {
        DB::beginTransaction();
        try {
            // If user exists when we find it
            // Then update the user
            // Else create the new one.
            $user = $id ? User::find($id) : new User;

            // Check the user
            if($id && !$user) return $this->error("Không tìm thấy người dùng", 404);

            $user->name = $request->name;
            // Remove a whitespace and make to lowercase
            $user->email = preg_replace('/\s+/', '', strtolower($request->email));

            // I dont wanna to update the password,
            // Password must be fill only when creating a new user.
            if(!$id) $user->password = \Hash::make($request->password);

            // Save the user
            $user->save();

            DB::commit();
            return $this->success(
                $id ? "User updated"
                    : "User created",
                $user, $id ? 200 : 201);
        } catch(\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function deleteUser($id)
    {
        DB::beginTransaction();
        try {
            $user = User::find($id);

            // Check the user
            if(!$user) return $this->error("Không tìm thấy người dùng", 404);

            // Delete the user
            $user->delete();

            DB::commit();
            return $this->success("Xoá thành công", $user);
        } catch(\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function findMyself($id)
    {
        try {
            $user = User::find($id);

            // Check the user
            if(!$user) return $this->error("Không tìm thấy người dùng", 404);

            // Format response theo yêu cầu
            $userData = [
                'id' => $user->id,
                'fullName' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'createdAt' => $user->created_at->toISOString(),
                'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
                'tokenFcm' => $user->token_fcm ?? null,
                'isOnline' => $user->is_online ?? false,
                'lastOnlineAt' => $user->last_online_at ? $user->last_online_at->toISOString() : null,
            ];

            return $this->success("Lấy thông tin thành công", $userData);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function findExceptMe($currentUserId, $keyword = null, $page = 1, $perPage = 10)
    {
        try {
            // Query builder để lấy users trừ user hiện tại
            $query = User::where('id', '!=', $currentUserId);

            // Tìm kiếm theo keyword nếu có
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
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
            $users = $query->orderBy('created_at', 'desc')
                          ->offset($offset)
                          ->limit($perPage)
                          ->get();

            // Format response theo yêu cầu
            $usersData = $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'createdAt' => $user->created_at->format('Y-m-d\TH:i:s'),
                    'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
                    'tokenFcm' => $user->token_fcm,
                    'isOnline' => $user->is_online ?? false,
                    'lastOnlineAt' => $user->last_online_at ? $user->last_online_at->format('Y-m-d\TH:i:s.u') : null,
                ];
            });

            $responseData = [
                'data' => $usersData,
                'totalPage' => $totalPage,
                'total' => $total,
                'currentPage' => (int) $page,
            ];

            return $this->success("Lấy danh sách thông tin người dùng thành công", $responseData);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }
}
