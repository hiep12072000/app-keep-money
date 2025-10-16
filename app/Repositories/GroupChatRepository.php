<?php

namespace App\Repositories;

use App\Models\GroupChat;
use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Support\Facades\DB;

class GroupChatRepository
{
    use ResponseAPI;

    /**
     * Create link to join group chat
     */
    public function createJoinLink($groupId)
    {
        try {
            // Validate that group chat exists
            $groupChat = GroupChat::find($groupId);
            if (!$groupChat) {
                return $this->error("Group chat with ID $groupId not found", 404);
            }

            // Generate a unique code
            $code = $this->generateUniqueCode();

            // Insert new link share record
            DB::table('link_shares')->insert([
                'group_id' => $groupId,
                'code' => $code,
                'created_at' => now(),
            ]);

            // Generate the join link
            $joinLink = "/api/group-chat/join-group/{$groupId}/{$code}";

            return $this->success("Create link join group successfully!", $joinLink, 201);
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
     * Generate unique code for join link
     */
    private function generateUniqueCode()
    {
        do {
            $code = \Illuminate\Support\Str::random(8); // Generate 8 character random string
        } while (DB::table('link_shares')->where('code', $code)->exists());

        return $code;
    }

    /**
     * Join group using share link
     */
    public function joinGroup($groupId, $code)
    {
        try {
            // Find the link share with groupId and code
            $linkShare = DB::table('link_shares')
                ->where('group_id', $groupId)
                ->where('code', $code)
                ->first();

            if (!$linkShare) {
                return $this->error("Invalid group ID or code", 404);
            }

            // Check if the link has expired (1 day = 24 hours from created_at)
            $createdAt = \Carbon\Carbon::parse($linkShare->created_at);
            $expiresAt = $createdAt->copy()->addDay(); // 1 day from creation
            $now = \Carbon\Carbon::now();

            if ($now->isAfter($expiresAt)) {
                return $this->error("Share link has expired", 410); // 410 Gone
            }

            // Link is valid and not expired
            return $this->success("Join group link is valid", null);
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
     * Update seen status of group chat
     */
    public function updateSeenStatus($conversationId, $isSeen)
    {
        try {
            // Find the group chat
            $groupChat = GroupChat::find($conversationId);
            if (!$groupChat) {
                return $this->error("Group chat with ID $conversationId not found", 404);
            }

            // Update the is_seen field
            $groupChat->update([
                'is_seen' => $isSeen
            ]);

            return $this->success("Seen status updated successfully", [
                'conversationId' => $conversationId,
                'isSeen' => $isSeen,
                'groupChatName' => $groupChat->name ?? null,
            ]);
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
     * Get chat detail by type and id
     */
    public function getChatDetail($type, $id)
    {
        try {
            // Find group chat by type and id
            $groupChat = GroupChat::where('type', $type)
                ->where('id', $id)
                ->with(['creator', 'members'])
                ->first();

            if (!$groupChat) {
                return $this->error("Chat with type '$type' and ID $id not found", 404);
            }

            // Get messages for this group chat
            $messages = DB::table('messages')
                ->where('group_chat_id', $id)
                ->orderBy('created_at', 'asc')
                ->get();

            // Format users data
            $users = [];
            foreach ($groupChat->members as $member) {
                $users[] = [
                    'id' => $member->id,
                    'fullName' => $member->full_name,
                    'email' => $member->email,
                    'phone' => $member->phone,
                    'createdAt' => $member->created_at ? $member->created_at->format('Y-m-d\TH:i:s') : null,
                    'avatar' => $member->avatar,
                    'tokenFcm' => $member->token_fcm,
                    'isOnline' => (bool) $member->is_online,
                    'lastOnlineAt' => $member->last_online_at ? $member->last_online_at->format('Y-m-d\TH:i:s.u') : null,
                ];
            }

            // Format messages data
            $formattedMessages = [];
            foreach ($messages as $message) {
                $formattedMessages[] = [
                    'id' => $message->id,
                    'userId' => $message->user_id,
                    'groupChatId' => $message->group_chat_id,
                    'content' => $message->content,
                    'createdAt' => $message->created_at ? \Carbon\Carbon::parse($message->created_at)->format('Y-m-d\TH:i:s') : null,
                    'type' => $message->type ?? 'TEXT',
                    'status' => $message->status ?? 'DEFAULT',
                ];
            }

            // Format response data
            $responseData = [
                'id' => $groupChat->id,
                'name' => $groupChat->name,
                'type' => $groupChat->type,
                'isPrivate' => (bool) $groupChat->is_private,
                'avatarGroupChat' => $groupChat->avatar,
                'isSeen' => (bool) $groupChat->is_seen,
                'createdBy' => $groupChat->created_by,
                'createdAt' => $groupChat->created_at ? $groupChat->created_at->format('Y-m-d\TH:i:s') : null,
                'updatedAt' => $groupChat->updated_at ? $groupChat->updated_at->format('Y-m-d\TH:i:s') : null,
                'deletedAt' => $groupChat->deleted_at ? $groupChat->deleted_at->format('Y-m-d\TH:i:s') : null,
                'users' => $users,
                'currentUserId' => auth()->id() ?? 1, // Get current user ID or default to 1
                'messages' => $formattedMessages,
            ];

            return $this->success("Get detail chat successfully", $responseData);
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
     * Search group chat by keyword
     */
    public function searchGroupChat($keyword)
    {
        try {
            // Search group chats by name (case insensitive)
            $groupChats = GroupChat::where('name', 'LIKE', '%' . $keyword . '%')
                ->orderBy('created_at', 'desc')
                ->get();

            // Format response data according to the required format
            $formattedData = [];
            foreach ($groupChats as $groupChat) {
                $formattedData[] = [
                    'groupChatId' => $groupChat->id,
                    'groupChatName' => $groupChat->name,
                    'userId' => null, // As per the sample response
                    'type' => $groupChat->type,
                ];
            }

            return $this->success("Get list group chat successfully", $formattedData);
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
     * Get list group chat with pagination
     */
    public function getListGroupChat($pageNumber)
    {
        try {
            $perPage = 10; // Số item per page
            $offset = ($pageNumber - 1) * $perPage;

            // Get group chats with pagination
            $groupChats = GroupChat::with(['creator', 'members'])
                ->orderBy('created_at', 'desc')
                ->skip($offset)
                ->take($perPage)
                ->get();

            // Get total count for pagination
            $totalCount = GroupChat::count();
            $totalPages = ceil($totalCount / $perPage);

            // Format response data
            $formattedData = [];
            foreach ($groupChats as $groupChat) {
                $members = [];
                foreach ($groupChat->members as $member) {
                    $members[] = [
                        'id' => $member->id,
                        'fullName' => $member->full_name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'avatar' => $member->avatar,
                        'isOnline' => (bool) $member->is_online,
                    ];
                }

                $formattedData[] = [
                    'id' => $groupChat->id,
                    'name' => $groupChat->name,
                    'type' => $groupChat->type,
                    'isPrivate' => (bool) $groupChat->is_private,
                    'avatar' => $groupChat->avatar,
                    'isSeen' => (bool) $groupChat->is_seen,
                    'createdBy' => $groupChat->creator->id,
                    'members' => $members,
                    'createdAt' => $groupChat->created_at->format('Y-m-d\TH:i:s'),
                    'updatedAt' => $groupChat->updated_at ? $groupChat->updated_at->format('Y-m-d\TH:i:s') : null,
                ];
            }

            return $this->success("Get list group chat successfully", [
                'data' => $formattedData,
                'pagination' => [
                    'currentPage' => (int) $pageNumber,
                    'totalPages' => $totalPages,
                    'totalItems' => $totalCount,
                    'perPage' => $perPage,
                ]
            ]);
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
     * Update group chat
     */
    public function updateGroupChat($groupChatId, $data)
    {
        DB::beginTransaction();
        try {
            // Validate that groupChatId exists
            $groupChat = GroupChat::find($groupChatId);
            if (!$groupChat) {
                return $this->error("Group chat with ID $groupChatId not found", 404);
            }

            // Verify all users exist
            $userIds = $data['userIds'];
            $existingUsers = User::whereIn('id', $userIds)->get();
            if ($existingUsers->count() !== count($userIds)) {
                $foundIds = $existingUsers->pluck('id')->toArray();
                $missingIds = array_diff($userIds, $foundIds);
                return $this->error("Users with IDs " . implode(', ', $missingIds) . " not found", 404);
            }

            // Update group chat
            $groupChat->update([
                'name' => $data['name'] ?? $groupChat->name,
                'type' => $data['type'],
                'is_private' => $data['isPrivate'],
                'avatar' => $data['avatar'] ?? $groupChat->avatar,
            ]);

            // Delete existing members and add new ones
            DB::table('group_chat_user')->where('group_chat_id', $groupChatId)->delete();

            $members = [];
            foreach ($userIds as $userId) {
                DB::table('group_chat_user')->insert([
                    'group_chat_id' => $groupChatId,
                    'user_id' => $userId,
                ]);

                $user = User::find($userId);
                $members[] = [
                    'userId' => $userId,
                    'userName' => $user->full_name ?? null,
                ];
            }

            DB::commit();

            return $this->success("Group chat updated successfully", [
                'id' => $groupChat->id,
                'name' => $groupChat->name,
                'type' => $groupChat->type,
                'createdBy' => $groupChat->created_by,
                'isPrivate' => $groupChat->is_private,
                'avatar' => $groupChat->avatar,
                'isSeen' => $groupChat->is_seen,
                'members' => $members,
                'createdAt' => $groupChat->created_at->format('Y-m-d\TH:i:s'),
                'updatedAt' => $groupChat->updated_at ? $groupChat->updated_at->format('Y-m-d\TH:i:s') : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorCode = $e->getCode();
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Invite member to group chat
     */
    public function inviteMember($groupChatId, $userId)
    {
        DB::beginTransaction();
        try {
            // Validate that group chat exists
            $groupChat = GroupChat::find($groupChatId);
            if (!$groupChat) {
                return $this->error("Group chat with ID $groupChatId not found", 404);
            }

            // Validate that user exists
            $user = User::find($userId);
            if (!$user) {
                return $this->error("User with ID $userId not found", 404);
            }

            // Check if user is already a member
            $existingMember = DB::table('group_chat_user')
                ->where('group_chat_id', $groupChatId)
                ->where('user_id', $userId)
                ->first();

            if ($existingMember) {
                return $this->error("User is already a member of this group chat", 409);
            }

            // Add user to group chat
            DB::table('group_chat_user')->insert([
                'group_chat_id' => $groupChatId,
                'user_id' => $userId,
            ]);

            DB::commit();

            return $this->success("Member invited successfully", [
                'groupId' => $groupChatId,
                'userId' => $userId,
                'userName' => $user->full_name ?? null,
                'groupName' => $groupChat->name ?? null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorCode = $e->getCode();
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Remove member from group chat
     */
    public function removeMember($groupChatId, $userId)
    {
        DB::beginTransaction();
        try {
            // Validate that group chat exists
            $groupChat = GroupChat::find($groupChatId);
            if (!$groupChat) {
                return $this->error("Group chat with ID $groupChatId not found", 404);
            }

            // Validate that user exists
            $user = User::find($userId);
            if (!$user) {
                return $this->error("User with ID $userId not found", 404);
            }

            // Check if user is a member
            $existingMember = DB::table('group_chat_user')
                ->where('group_chat_id', $groupChatId)
                ->where('user_id', $userId)
                ->first();

            if (!$existingMember) {
                return $this->error("User is not a member of this group chat", 404);
            }

            // Remove user from group chat
            DB::table('group_chat_user')
                ->where('group_chat_id', $groupChatId)
                ->where('user_id', $userId)
                ->delete();

            DB::commit();

            return $this->success("Member removed successfully", [
                'groupId' => $groupChatId,
                'userId' => $userId,
                'userName' => $user->full_name ?? null,
                'groupName' => $groupChat->name ?? null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorCode = $e->getCode();
            if ($errorCode < 100 || $errorCode > 599) {
                $errorCode = 500;
            }
            return $this->error($e->getMessage(), $errorCode);
        }
    }

    /**
     * Create group chat
     */
    public function createGroupChat($data)
    {
        DB::beginTransaction();
        try {
            // Verify all users exist
            $userIds = $data['userIds'];
            $existingUsers = User::whereIn('id', $userIds)->get();
            
            if ($existingUsers->count() !== count($userIds)) {
                $foundIds = $existingUsers->pluck('id')->toArray();
                $missingIds = array_diff($userIds, $foundIds);
                return $this->error("Users with IDs " . implode(', ', $missingIds) . " not found", 404);
            }

            // Create the group chat
            $groupChat = GroupChat::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'created_by' => auth()->id() ?? 1, // Use user ID 1 as default if not authenticated
                'is_private' => $data['isPrivate'],
                'avatar' => $data['avatar'] ?? null,
                'is_seen' => false,
            ]);

            // Add users to the group chat
            $members = [];
            foreach ($userIds as $userId) {
                // Add user to group chat
                DB::table('group_chat_user')->insert([
                    'group_chat_id' => $groupChat->id,
                    'user_id' => $userId,
                ]);

                $user = User::find($userId);
                $members[] = [
                    'userId' => $userId,
                    'userName' => $user->full_name ?? null,
                    'email' => $user->email ?? null,
                ];
            }

            DB::commit();

            return $this->success("Group chat created successfully", [
                'id' => $groupChat->id,
                'name' => $groupChat->name,
                'type' => $groupChat->type,
                'isPrivate' => $groupChat->is_private,
                'avatar' => $groupChat->avatar,
                'createdBy' => $groupChat->created_by,
                'members' => $members,
                'createdAt' => $groupChat->created_at->format('Y-m-d\TH:i:s'),
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
}
