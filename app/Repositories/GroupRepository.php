<?php

namespace App\Repositories;

use App\Interfaces\GroupInterface;
use App\Models\Trip;
use App\Models\TripSpendingHistory;
use App\Models\TripPayer;
use App\Models\TripSpendingHistoryUser;
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
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
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
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
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
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
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
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
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
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
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
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
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
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Get group activity detail
     */
    public function getActivityDetail($activityId)
    {
        try {
            // Validate that $activityId is numeric and positive
            if (!is_numeric($activityId) || $activityId <= 0) {
                return $this->error("Invalid activity ID: $activityId. ID must be a positive number.", 400);
            }

            $activity = TripSpendingHistory::with([
                'trip',
                'creator',
                'payers.user',
                'spendingUsers.user'
            ])->find($activityId);

            if(!$activity) return $this->error("No activity with ID $activityId", 404);

            // Format userCreated
            $userCreated = null;
            if ($activity->creator) {
                $userCreated = [
                    'id' => is_numeric($activity->creator->id) ? (int) $activity->creator->id : null,
                    'fullName' => $activity->creator->full_name ?? null,
                    'email' => $activity->creator->email ?? null,
                    'phone' => $activity->creator->phone ?? null,
                    'createdAt' => $activity->creator->created_at ? $activity->creator->created_at->format('Y-m-d\TH:i:s') : null,
                    'avatar' => $activity->creator->avatar ? url('storage/' . $activity->creator->avatar) : null,
                    'tokenFcm' => $activity->creator->token_fcm ?? null,
                    'isOnline' => (bool) ($activity->creator->is_online ?? false),
                    'lastOnlineAt' => $activity->creator->last_online_at ? $activity->creator->last_online_at->format('Y-m-d\TH:i:s.u') : null,
                ];
            }

            // Format payers
            $payers = [];
            if ($activity->payers) {
                foreach ($activity->payers as $payer) {
                    $payers[] = [
                        'id' => is_numeric($payer->id) ? (int) $payer->id : null,
                        'groupActivityId' => is_numeric($activity->id) ? (int) $activity->id : null,
                        'userId' => is_numeric($payer->user_id) ? (int) $payer->user_id : null,
                        'userName' => $payer->user->full_name ?? null,
                        'amount' => is_numeric($payer->payment_amount) ? (float) $payer->payment_amount : 0.0,
                    ];
                }
            }

            // Format senders (spendingUsers)
            $senders = [];
            if ($activity->spendingUsers) {
                foreach ($activity->spendingUsers as $spendingUser) {
                    $senders[] = [
                        'id' => is_numeric($spendingUser->id) ? (int) $spendingUser->id : null,
                        'groupActivityId' => is_numeric($activity->id) ? (int) $activity->id : null,
                        'userId' => is_numeric($spendingUser->user_id) ? (int) $spendingUser->user_id : null,
                        'userName' => $spendingUser->user->full_name ?? null,
                        'amount' => is_numeric($spendingUser->amount) ? (float) $spendingUser->amount : 0.0,
                    ];
                }
            }

            // Format response data
            $responseData = [
                'id' => is_numeric($activity->id) ? (int) $activity->id : null,
                'groupId' => is_numeric($activity->trip_id) ? (int) $activity->trip_id : null,
                'name' => $activity->name ?? null,
                'totalAmount' => is_numeric($activity->total_amount) ? (float) $activity->total_amount : 0.0,
                'isBalance' => (bool) ($activity->is_balance ?? false),
                'note' => $activity->note ?? null,
                'createdBy' => is_numeric($activity->created_by) ? (int) $activity->created_by : null,
                'createdAt' => $activity->created_at ? $activity->created_at->format('Y-m-d\TH:i:s') : null,
                'updatedAt' => $activity->updated_at ? $activity->updated_at->format('Y-m-d\TH:i:s') : null,
                'userCreated' => $userCreated,
                'payers' => $payers,
                'senders' => $senders,
            ];

            return $this->success("Get activity detail successfully", $responseData);
        } catch(\Exception $e) {
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Create group activity
     */
    public function createActivity($data)
    {
        DB::beginTransaction();
        try {
            // Validate that groupId exists
            $trip = Trip::find($data['groupId']);
            if (!$trip) {
                return $this->error("Group with ID {$data['groupId']} not found", 404);
            }

            // Calculate total amount from payers (array)
            $totalAmount = 0;
            foreach ($data['payers'] as $payer) {
                $totalAmount += $payer['paymentAmount'];
            }

            // Create the spending history record
            $spendingHistory = TripSpendingHistory::create([
                'trip_id' => $data['groupId'],
                'name' => $data['name'],
                'total_amount' => $totalAmount,
                'is_balance' => $data['isBalance'],
                'note' => $data['note'] ?? null,
                'created_by' => $data['createdBy'],
            ]);

            // Create payers (array)
            foreach ($data['payers'] as $payer) {
                TripPayer::create([
                    'trip_spending_history_id' => $spendingHistory->id,
                    'user_id' => $payer['userId'],
                    'payment_amount' => $payer['paymentAmount'],
                ]);
            }

            // Create senders (array)
            foreach ($data['senders'] as $sender) {
                TripSpendingHistoryUser::create([
                    'trip_spending_history_id' => $spendingHistory->id,
                    'user_id' => $sender['userId'],
                    'amount' => $sender['amount'],
                    'is_balance' => $data['isBalance'],
                ]);
            }

            DB::commit();

            // Return the created activity with basic details
            return $this->success("Activity created successfully", [
                'id' => is_numeric($spendingHistory->id) ? (int) $spendingHistory->id : null,
                'groupId' => is_numeric($spendingHistory->trip_id) ? (int) $spendingHistory->trip_id : null,
                'name' => $spendingHistory->name ?? null,
                'totalAmount' => is_numeric($spendingHistory->total_amount) ? (float) $spendingHistory->total_amount : 0.0,
                'isBalance' => (bool) ($spendingHistory->is_balance ?? false),
                'note' => $spendingHistory->note ?? null,
                'createdBy' => is_numeric($spendingHistory->created_by) ? (int) $spendingHistory->created_by : null,
                'createdAt' => $spendingHistory->created_at ? $spendingHistory->created_at->format('Y-m-d\TH:i:s') : null,
                'updatedAt' => $spendingHistory->updated_at ? $spendingHistory->updated_at->format('Y-m-d\TH:i:s') : null,
            ]);
        } catch(\Exception $e) {
            DB::rollBack();
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Update group activity
     */
    public function updateActivity($activityId, $data)
    {
        DB::beginTransaction();
        try {
            // Validate that $activityId is numeric and positive
            if (!is_numeric($activityId) || $activityId <= 0) {
                return $this->error("Invalid activity ID: $activityId. ID must be a positive number.", 400);
            }

            // Find the activity
            $spendingHistory = TripSpendingHistory::find($activityId);
            if (!$spendingHistory) {
                return $this->error("Activity with ID $activityId not found", 404);
            }

            // Validate that groupId exists
            $trip = Trip::find($data['groupId']);
            if (!$trip) {
                return $this->error("Group with ID {$data['groupId']} not found", 404);
            }

            // Calculate total amount from payers (array)
            $totalAmount = 0;
            foreach ($data['payers'] as $payer) {
                $totalAmount += $payer['paymentAmount'];
            }

            // Update the spending history record
            $spendingHistory->update([
                'trip_id' => $data['groupId'],
                'name' => $data['name'],
                'total_amount' => $totalAmount,
                'is_balance' => $data['isBalance'],
                'note' => $data['note'] ?? null,
            ]);

            // Delete existing payers and create new ones
            TripPayer::where('trip_spending_history_id', $spendingHistory->id)->delete();
            foreach ($data['payers'] as $payer) {
                TripPayer::create([
                    'trip_spending_history_id' => $spendingHistory->id,
                    'user_id' => $payer['userId'],
                    'payment_amount' => $payer['paymentAmount'],
                ]);
            }

            // Delete existing senders and create new ones
            TripSpendingHistoryUser::where('trip_spending_history_id', $spendingHistory->id)->delete();
            foreach ($data['senders'] as $sender) {
                TripSpendingHistoryUser::create([
                    'trip_spending_history_id' => $spendingHistory->id,
                    'user_id' => $sender['userId'],
                    'amount' => $sender['amount'],
                    'is_balance' => $data['isBalance'],
                ]);
            }

            DB::commit();

            // Return the updated activity with basic details
            return $this->success("Activity updated successfully", [
                'id' => is_numeric($spendingHistory->id) ? (int) $spendingHistory->id : null,
                'groupId' => is_numeric($spendingHistory->trip_id) ? (int) $spendingHistory->trip_id : null,
                'name' => $spendingHistory->name ?? null,
                'totalAmount' => is_numeric($spendingHistory->total_amount) ? (float) $spendingHistory->total_amount : 0.0,
                'isBalance' => (bool) ($spendingHistory->is_balance ?? false),
                'note' => $spendingHistory->note ?? null,
                'createdBy' => is_numeric($spendingHistory->created_by) ? (int) $spendingHistory->created_by : null,
                'createdAt' => $spendingHistory->created_at ? $spendingHistory->created_at->format('Y-m-d\TH:i:s') : null,
                'updatedAt' => $spendingHistory->updated_at ? $spendingHistory->updated_at->format('Y-m-d\TH:i:s') : null,
            ]);
        } catch(\Exception $e) {
            DB::rollBack();
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Add member to group
     */
    public function addMember($groupId, $data)
    {
        DB::beginTransaction();
        try {
            // Validate that $groupId is numeric and positive
            if (!is_numeric($groupId) || $groupId <= 0) {
                return $this->error("Invalid group ID: $groupId. ID must be a positive number.", 400);
            }

            // Find the group
            $trip = Trip::find($groupId);
            if (!$trip) {
                return $this->error("Group with ID $groupId not found", 404);
            }

            // Determine user ID - either from request or find by userName
            $userId = null;
            if (isset($data['userId']) && $data['userId']) {
                $userId = $data['userId'];
                // Verify user exists
                $user = User::find($userId);
                if (!$user) {
                    return $this->error("User with ID $userId not found", 404);
                }
            } elseif (isset($data['userName']) && $data['userName']) {
                // Find user by userName (assuming it's full_name field)
                $user = User::where('full_name', $data['userName'])->first();
                if (!$user) {
                    return $this->error("User with name '{$data['userName']}' not found", 404);
                }
                $userId = $user->id;
            } else {
                return $this->error("Either userId or userName must be provided", 400);
            }

            // Check if user is already a member of the group
            $existingMember = DB::table('trip_users')
                ->where('trip_id', $groupId)
                ->where('user_id', $userId)
                ->first();

            if ($existingMember) {
                return $this->error("User is already a member of this group", 409);
            }

            // Add user to group
            DB::table('trip_users')->insert([
                'trip_id' => $groupId,
                'user_id' => $userId,
                'advance' => null,
                'created_at' => now(),
            ]);

            // Process group activities
            $processedActivities = [];
            foreach ($data['groupActivities'] as $activityData) {
                $activityId = $activityData['groupActivityId'];
                
                // Verify activity exists and belongs to the group
                $activity = TripSpendingHistory::where('id', $activityId)
                    ->where('trip_id', $groupId)
                    ->first();
                
                if (!$activity) {
                    return $this->error("Activity with ID $activityId not found in group $groupId", 404);
                }

                // If senders are provided, add them to the activity
                if (isset($activityData['senders']) && !empty($activityData['senders'])) {
                    foreach ($activityData['senders'] as $senderData) {
                        // Check if sender already exists for this activity
                        $existingSender = TripSpendingHistoryUser::where('trip_spending_history_id', $activityId)
                            ->where('user_id', $senderData['userId'])
                            ->first();

                        if (!$existingSender) {
                            TripSpendingHistoryUser::create([
                                'trip_spending_history_id' => $activityId,
                                'user_id' => $senderData['userId'],
                                'amount' => $senderData['amount'],
                                'is_balance' => $activity->is_balance,
                            ]);
                        }
                    }
                }

                $processedActivities[] = [
                    'groupActivityId' => $activityId,
                    'name' => $activity->name,
                    'totalAmount' => (float) $activity->total_amount,
                    'isBalance' => (bool) $activity->is_balance,
                ];
            }

            DB::commit();

            return $this->success("Member added to group successfully", [
                'groupId' => $groupId,
                'userId' => $userId,
                'userName' => $user->full_name ?? null,
                'processedActivities' => $processedActivities,
                'addedAt' => now()->format('Y-m-d\TH:i:s'),
            ]);
        } catch(\Exception $e) {
            DB::rollBack();
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Finish group (update status to done)
     */
    public function finishGroup($groupId)
    {
        DB::beginTransaction();
        try {
            // Validate that $groupId is numeric and positive
            if (!is_numeric($groupId) || $groupId <= 0) {
                return $this->error("Invalid group ID: $groupId. ID must be a positive number.", 400);
            }

            // Find the group
            $trip = Trip::find($groupId);
            if (!$trip) {
                return $this->error("Group with ID $groupId not found", 404);
            }

            // Check if group is already finished
            if ($trip->status === 'done') {
                return $this->error("Group is already finished", 409);
            }

            // Update group status to 'done'
            $trip->update([
                'status' => 'done'
            ]);

            DB::commit();

            return $this->success("Group finished successfully", [
                'groupId' => $groupId,
                'name' => $trip->name,
                'status' => $trip->status,
                'finishedAt' => $trip->updated_at->format('Y-m-d\TH:i:s'),
            ]);
        } catch(\Exception $e) {
            DB::rollBack();
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Create group
     */
    public function createGroup($data)
    {
        DB::beginTransaction();
        try {
            // Validate that userIds and userNames arrays have the same length
            if (count($data['userIds']) !== count($data['userNames'])) {
                return $this->error("userIds and userNames arrays must have the same length", 422);
            }

            // Verify all users exist
            $userIds = $data['userIds'];
            $existingUsers = User::whereIn('id', $userIds)->get();
            
            if ($existingUsers->count() !== count($userIds)) {
                $foundIds = $existingUsers->pluck('id')->toArray();
                $missingIds = array_diff($userIds, $foundIds);
                return $this->error("Users with IDs " . implode(', ', $missingIds) . " not found", 404);
            }

            // Create the group
            $trip = Trip::create([
                'name' => $data['name'],
                'status' => 'active',
                'created_by' => auth()->id() ?? 1, // Use user ID 1 as default if not authenticated
                'key_member_id' => $userIds[0], // Use first user as key member
                'group_chat_id' => $data['groupChatId'] ?? null,
            ]);

            // Add users to the group
            $members = [];
            for ($i = 0; $i < count($userIds); $i++) {
                $userId = $userIds[$i];
                $userName = $data['userNames'][$i];
                
                // Add user to group
                DB::table('trip_users')->insert([
                    'trip_id' => $trip->id,
                    'user_id' => $userId,
                    'advance' => null,
                    'created_at' => now(),
                ]);

                $members[] = [
                    'userId' => $userId,
                    'userName' => $userName,
                ];
            }

            DB::commit();

            return $this->success("Group created successfully", [
                'id' => $trip->id,
                'name' => $trip->name,
                'status' => $trip->status,
                'groupChatId' => $trip->group_chat_id,
                'createdBy' => $trip->created_by,
                'members' => $members,
                'createdAt' => $trip->created_at->format('Y-m-d\TH:i:s'),
            ]);
        } catch(\Exception $e) {
            DB::rollBack();
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Get group report
     */
    public function getGroupReport($groupId)
    {
        try {
            // Validate that $groupId is numeric and positive
            if (!is_numeric($groupId) || $groupId <= 0) {
                return $this->error("Invalid group ID: $groupId. ID must be a positive number.", 400);
            }

            // Find the group
            $trip = Trip::find($groupId);
            if (!$trip) {
                return $this->error("Group with ID $groupId not found", 404);
            }

            // Get all members of the group
            $members = DB::table('trip_users')
                ->where('trip_id', $groupId)
                ->pluck('user_id')
                ->toArray();

            if (empty($members)) {
                return $this->success("Get trip report success", []);
            }

            // Get spending history for this group
            $spendingHistory = TripSpendingHistory::where('trip_id', $groupId)->get();

            // Calculate amount spent and paid for each user
            $reportData = [];
            foreach ($members as $userId) {
                $user = User::find($userId);
                if (!$user) continue;

                $amountSpent = 0;
                $amountPaid = 0;

                // Calculate amount spent (from senders)
                $spentAmount = TripSpendingHistoryUser::whereIn('trip_spending_history_id', $spendingHistory->pluck('id'))
                    ->where('user_id', $userId)
                    ->sum('amount');
                $amountSpent = (float) $spentAmount;

                // Calculate amount paid (from payers)
                $paidAmount = TripPayer::whereIn('trip_spending_history_id', $spendingHistory->pluck('id'))
                    ->where('user_id', $userId)
                    ->sum('payment_amount');
                $amountPaid = (float) $paidAmount;

                $reportData[] = [
                    'userDTO' => [
                        'id' => $user->id,
                        'fullName' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'createdAt' => $user->created_at ? $user->created_at->format('Y-m-d\TH:i:s') : null,
                        'avatar' => $user->avatar,
                        'tokenFcm' => $user->token_fcm,
                        'isOnline' => (bool) $user->is_online,
                        'lastOnlineAt' => $user->last_online_at ? $user->last_online_at->format('Y-m-d\TH:i:s.u') : null,
                    ],
                    'amountSpent' => $amountSpent,
                    'amountPaid' => $amountPaid,
                ];
            }

            return $this->success("Get trip report success", $reportData);
        } catch(\Exception $e) {
            $errorCode = $e->getCode();
            // Ensure error code is a valid HTTP status code
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

}
