<?php

namespace App\Repositories;

use App\Interfaces\GroupInterface;
use App\Models\Trip;
use App\Models\TripSpendingHistory;
use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

class GroupRepository implements GroupInterface
{
    use ResponseAPI;

    public function getList($keyword = null, $page = 1, $perPage = 10)
    {
        try {
            // Query builder để lấy trips với members từ trip_users
            $query = Trip::with(['creator', 'members']);


            // Tìm kiếm theo keyword nếu có
            if (!empty($keyword) && is_string($keyword)) {
                $query->where(function($q) use ($keyword) {
                    $q->where('name', 'LIKE', "%{$keyword}%")
                      ->orWhere('status', 'LIKE', "%{$keyword}%");
                });
            }

            // Lấy tất cả trips (không pagination vì cần group theo created_by)
            $trips = $query->orderBy('created_at', 'desc')->get();

            // Format response theo yêu cầu - nhóm theo created_by
            $groupedData = [];

            foreach ($trips as $trip) {
                $createdBy = (int) $trip->created_by;

//                if (!isset($groupedData[$createdBy])) {
//                    $groupedData[$createdBy] = [];
//                }

                // Lấy avatar URLs từ trip members
                $avatarUrls = [];
                $maxAvatars = 5; // Giới hạn 5 avatars như trong response mẫu

                if ($trip->members && $trip->members->count() > 0) {
                    $memberCount = min($trip->members->count(), $maxAvatars);

                    for ($i = 0; $i < $maxAvatars; $i++) {
                        if ($i < $memberCount) {
                            $member = $trip->members[$i];
                            $avatarUrls[] = $member->avatar ? url('storage/' . $member->avatar) : null;
                        } else {
                            $avatarUrls[] = null;
                        }
                    }
                } else {
                    // Nếu không có members, tạo array với null values
                    $avatarUrls = array_fill(0, $maxAvatars, null);
                }

                $groupedData[] = [
                    'id' => $trip->id ? (int) $trip->id : null,
                    'name' => $trip->name,
                    'groupChatId' => $trip->group_chat_id ? (int) $trip->group_chat_id : null,
                    'status' => $trip->status,
                    'createdBy' => $trip->created_by ? (int) $trip->created_by : null,
                    'keyMemberId' => $trip->key_member_id ? (int) $trip->key_member_id : null,
                    'createdAt' => $trip->created_at->format('Y-m-d\TH:i:s'),
                    'updatedAt' => $trip->updated_at ? $trip->updated_at->format('Y-m-d\TH:i:s') : $trip->created_at->format('Y-m-d\TH:i:s'),
                    'avatarUrl' => $avatarUrls,
                ];
            }

            return $this->success("Get list successfully", $groupedData);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function getById($id)
    {
        try {
            // Validate that $id is numeric and positive
            if (!is_numeric($id) || $id <= 0) {
                return $this->error("Invalid trip ID: $id. ID must be a positive number.", 400);
            }

            $trip = Trip::with(['creator'])->find($id);

            if(!$trip) return $this->error("No trip with ID $id", 404);

            return $this->success("Trip Detail", $trip);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get trip detail with members and activities
     */
    public function getDetail($id)
    {
        try {
            // Validate that $id is numeric and positive
            if (!is_numeric($id) || $id <= 0) {
                return $this->error("Invalid trip ID: $id. ID must be a positive number.", 400);
            }

            $trip = Trip::with(['creator', 'keyMember', 'spendingHistory', 'members'])->find($id);

            if(!$trip) return $this->error("No trip with ID $id", 404);

            // Format keyMember
            $keyMember = null;
            if ($trip->keyMember) {
                try {
                    $keyMember = [
                        'id' => is_numeric($trip->keyMember->id) ? (int) $trip->keyMember->id : null,
                        'fullName' => $trip->keyMember->full_name ?? null,
                        'email' => $trip->keyMember->email ?? null,
                        'phone' => $trip->keyMember->phone ?? null,
                        'createdAt' => $trip->keyMember->created_at ? $trip->keyMember->created_at->format('Y-m-d\TH:i:s') : null,
                        'avatar' => $trip->keyMember->avatar ? url('storage/' . $trip->keyMember->avatar) : null,
                        'tokenFcm' => $trip->keyMember->token_fcm ?? null,
                        'isOnline' => (bool) ($trip->keyMember->is_online ?? false),
                        'lastOnlineAt' => $trip->keyMember->last_online_at ? $trip->keyMember->last_online_at->format('Y-m-d\TH:i:s.u') : null,
                    ];
                } catch(\Exception $e) {
                    return $this->error("Error formatting keyMember: " . $e->getMessage(), 500);
                }
            }

            // Format groupUsers từ trip members
            $groupUsers = [];
            if ($trip->members) {
                try {
                    foreach ($trip->members as $member) {
                        $groupUsers[] = [
                            'id' => is_numeric($member->id) ? (int) $member->id : null,
                            'fullName' => $member->full_name ?? null,
                            'email' => $member->email ?? null,
                            'phone' => $member->phone ?? null,
                            'createdAt' => $member->created_at ? $member->created_at->format('Y-m-d\TH:i:s') : null,
                            'avatar' => $member->avatar ? url('storage/' . $member->avatar) : null,
                            'tokenFcm' => $member->token_fcm ?? null,
                            'isOnline' => (bool) ($member->is_online ?? false),
                            'lastOnlineAt' => $member->last_online_at ? $member->last_online_at->format('Y-m-d\TH:i:s.u') : null,
                        ];
                    }
                } catch(\Exception $e) {
                    return $this->error("Error formatting groupUsers: " . $e->getMessage(), 500);
                }
            }

            // Format groupActivities từ trip_spending_history
            $groupActivities = [];
            if ($trip->spendingHistory) {
                try {
                    foreach ($trip->spendingHistory as $activity) {
                        $groupActivities[] = [
                            'id' => is_numeric($activity->id) ? (int) $activity->id : null,
                            'groupId' => is_numeric($trip->id) ? (int) $trip->id : null,
                            'name' => $activity->name ?? null,
                            'totalAmount' => is_numeric($activity->total_amount) ? (float) $activity->total_amount : 0.0,
                            'isBalance' => (bool) ($activity->is_balance ?? false),
                            'note' => $activity->note ?? null,
                            'createdBy' => is_numeric($activity->created_by) ? (int) $activity->created_by : null,
                            'createdAt' => $activity->created_at ? $activity->created_at->format('Y-m-d\TH:i:s') : null,
                            'updatedAt' => $activity->updated_at ? $activity->updated_at->format('Y-m-d\TH:i:s') : null,
                        ];
                    }
                } catch(\Exception $e) {
                    return $this->error("Error formatting groupActivities: " . $e->getMessage(), 500);
                }
            }

            // Format response theo yêu cầu
            try {
                $responseData = [
                    'id' => is_numeric($trip->id) ? (int) $trip->id : null,
                    'name' => $trip->name ?? null,
                    'groupChatId' => is_numeric($trip->group_chat_id) ? (int) $trip->group_chat_id : null,
                    'status' => $trip->status ?? null,
                    'createdBy' => is_numeric($trip->created_by) ? (int) $trip->created_by : null,
                    'keyMemberId' => is_numeric($trip->key_member_id) ? (int) $trip->key_member_id : null,
                    'createdAt' => $trip->created_at ? $trip->created_at->format('Y-m-d\TH:i:s') : null,
                    'updatedAt' => $trip->updated_at ? $trip->updated_at->format('Y-m-d\TH:i:s') : null,
                    'keyMember' => $keyMember,
                    'groupUsers' => $groupUsers,
                    'groupActivities' => $groupActivities,
                ];
            } catch(\Exception $e) {
                return $this->error("Error formatting responseData: " . $e->getMessage(), 500);
            }

            return $this->success("Get detail group successfully", $responseData);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function create($data)
    {
        DB::beginTransaction();
        try {
            $trip = Trip::create($data);

            DB::commit();
            return $this->success("Trip created", $trip, 201);
        } catch(\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function update($id, $data)
    {
        DB::beginTransaction();
        try {
            // Validate that $id is numeric and positive
            if (!is_numeric($id) || $id <= 0) {
                return $this->error("Invalid trip ID: $id. ID must be a positive number.", 400);
            }

            $trip = Trip::find($id);

            if(!$trip) return $this->error("No trip with ID $id", 404);

            $trip->update($data);

            DB::commit();
            return $this->success("Trip updated", $trip);
        } catch(\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();
        try {
            // Validate that $id is numeric and positive
            if (!is_numeric($id) || $id <= 0) {
                return $this->error("Invalid trip ID: $id. ID must be a positive number.", 400);
            }

            $trip = Trip::find($id);

            if(!$trip) return $this->error("No trip with ID $id", 404);

            $trip->delete();

            DB::commit();
            return $this->success("Trip deleted", $trip);
        } catch(\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Update group name only
     */
    public function updateGroupName($groupId, $name)
    {
        DB::beginTransaction();
        try {
            // Validate that $groupId is numeric and positive
            if (!is_numeric($groupId) || $groupId <= 0) {
                return $this->error("Invalid group ID: $groupId. ID must be a positive number.", 400);
            }

            $trip = Trip::find($groupId);

            if(!$trip) return $this->error("No group with ID $groupId", 404);

            // Update only the name field
            $trip->name = $name;
            $trip->save();

            DB::commit();
            return $this->success("Group name updated successfully", [
                'id' => $trip->id,
                'name' => $trip->name,
                'updated_at' => $trip->updated_at->format('Y-m-d\TH:i:s')
            ]);
        } catch(\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

}
