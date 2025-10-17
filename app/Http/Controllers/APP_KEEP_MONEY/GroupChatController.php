<?php

namespace App\Http\Controllers\APP_KEEP_MONEY;

use App\Http\Controllers\Controller;
use App\Repositories\APP_KEEP_MONEY\GroupChatRepository;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class GroupChatController extends Controller
{
    use ResponseAPI;

    protected $groupChatRepository;

    public function __construct(GroupChatRepository $groupChatRepository)
    {
        $this->groupChatRepository = $groupChatRepository;
    }

    /**
     * Create link to join group chat (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createJoinLink(Request $request)
    {
        try {
            // Validate request data
            $data = $request->validate([
                'groupId' => 'required|integer|min:1',
            ]);

            return $this->groupChatRepository->createJoinLink($data['groupId']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Join group using share link (requires authentication)
     *
     * @param int $groupId
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function joinGroup($groupId, $code)
    {
        try {
            // Validate that $groupId is numeric
            if (!is_numeric($groupId)) {
                return $this->error('Invalid group ID format. Group ID must be numeric.', 400);
            }

            $groupId = (int) $groupId;

            // Validate code
            if (empty($code) || !is_string($code)) {
                return $this->error('Invalid code format. Code must be a non-empty string.', 400);
            }

            return $this->groupChatRepository->joinGroup($groupId, $code);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update seen status of group chat (requires authentication)
     *
     * @param Request $request
     * @param int $conversationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSeenStatus(Request $request, $conversationId)
    {
        try {
            // Validate that $conversationId is numeric
            if (!is_numeric($conversationId)) {
                return $this->error('ID nhóm chat phải là số', 400);
            }

            $conversationId = (int) $conversationId;

            // Validate request data
            $data = $request->validate([
                'isSeen' => 'required|boolean',
            ]);

            return $this->groupChatRepository->updateSeenStatus($conversationId, $data['isSeen']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get chat detail (requires authentication)
     *
     * @param string $type
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChatDetail($type, $id)
    {
        try {
            // Validate that $id is numeric
            if (!is_numeric($id)) {
                return $this->error('Id nhóm chat phải là số', 400);
            }

            $id = (int) $id;

            // Validate type parameter
            if (!in_array($type, ['GROUP', 'PRIVATE'])) {
                return $this->error('Chỉ có thể chọn type GROUP hoặc PRIVATE ', 400);
            }

            return $this->groupChatRepository->getChatDetail($type, $id);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search group chat (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchGroupChat(Request $request)
    {
        try {
            // Validate request data
            $data = $request->validate([
                'keyword' => 'required|string|min:1|max:255',
            ]);

            return $this->groupChatRepository->searchGroupChat($data['keyword']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get list group chat (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListGroupChat(Request $request)
    {
        try {
            // Validate request data
            $data = $request->validate([
                'pageNumber' => 'required|integer|min:1',
            ]);

            return $this->groupChatRepository->getListGroupChat($data['pageNumber']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update group chat (requires authentication)
     *
     * @param Request $request
     * @param int $groupChatId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGroupChat(Request $request, $groupChatId)
    {
        try {
            // Validate that $groupChatId is numeric
            if (!is_numeric($groupChatId)) {
                return $this->error('Id nhóm chat phải là số', 400);
            }

            $groupChatId = (int) $groupChatId;

            // Validate request data
            $data = $request->validate([
                'name' => 'nullable|string|max:255',
                'type' => 'required|string|in:GROUP,PRIVATE',
                'userIds' => 'required|array|min:1',
                'userIds.*' => 'integer|min:1',
                'isPrivate' => 'required|boolean',
                'avatar' => 'nullable|string|max:255',
            ]);

            return $this->groupChatRepository->updateGroupChat($groupChatId, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Invite member to group chat (requires authentication)
     *
     * @param int $groupChatId
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteMember($groupChatId, $userId)
    {
        try {
            // Validate that both IDs are numeric
            if (!is_numeric($groupChatId)) {
                return $this->error('Id nhóm chat phải là số', 400);
            }
            if (!is_numeric($userId)) {
                return $this->error('Id người dùng phải là số', 400);
            }

            $groupChatId = (int) $groupChatId;
            $userId = (int) $userId;

            return $this->groupChatRepository->inviteMember($groupChatId, $userId);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove member from group chat (requires authentication)
     *
     * @param int $groupChatId
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember($groupChatId, $userId)
    {
        try {
            // Validate that both IDs are numeric
            if (!is_numeric($groupChatId)) {
                return $this->error('Id nhóm chat phải là số', 400);
            }
            if (!is_numeric($userId)) {
                return $this->error('Id người dùng phải là số', 400);
            }

            $groupChatId = (int) $groupChatId;
            $userId = (int) $userId;

            return $this->groupChatRepository->removeMember($groupChatId, $userId);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create group chat (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createGroupChat(Request $request)
    {
        try {
            // Validate request data
            $data = $request->validate([
                'name' => 'required|string|max:255|min:1',
                'type' => 'required|string|in:GROUP,PRIVATE',
                'userIds' => 'required|array|min:1',
                'userIds.*' => 'integer|min:1',
                'isPrivate' => 'required|boolean',
                'avatar' => 'nullable|string|max:255',
            ]);

            return $this->groupChatRepository->createGroupChat($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
