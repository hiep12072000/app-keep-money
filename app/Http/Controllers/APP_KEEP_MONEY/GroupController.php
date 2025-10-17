<?php

namespace App\Http\Controllers\APP_KEEP_MONEY;

use App\Http\Controllers\Controller;
use App\Interfaces\APP_KEEP_MONEY\GroupInterface;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    use ResponseAPI;

    protected $groupRepository;

    public function __construct(GroupInterface $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    /**
     * Get list of groups (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        try {
            // Lấy parameters từ request với validation
            $keyword = $request->get('keyword');

            // Validate và convert page
            $page = $request->get('page', 1);
            $page = is_numeric($page) ? (int) $page : 1;
            $page = max(1, $page); // Đảm bảo page >= 1

            // Validate và convert per_page
            $perPage = $request->get('per_page', 10);
            $perPage = is_numeric($perPage) ? (int) $perPage : 10;
            $perPage = max(1, min(100, $perPage)); // Đảm bảo 1 <= per_page <= 100

            // Sử dụng repository để lấy danh sách groups
            return $this->groupRepository->getList($keyword, $page, $perPage);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get group by ID (requires authentication)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getById(Request $request, $id)
    {
        try {
            // Validate that $id is numeric
            if (!is_numeric($id)) {
                return $this->error('Invalid ID format. ID must be numeric.', 400);
            }

            $id = (int) $id;
            return $this->groupRepository->getById($id);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get group detail with members and activities (requires authentication)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail(Request $request, $id)
    {
        try {
            // Validate that $id is numeric
            if (!is_numeric($id)) {
                return $this->error('Invalid ID format. ID must be numeric.', 400);
            }

            $id = (int) $id;
            return $this->groupRepository->getDetail($id);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create new group (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            // Validate request data
            $data = $request->validate([
                'name' => 'required|string|max:255|min:1',
                'userIds' => 'array',
                'userIds.*' => 'integer',
                'userNames' => 'array',
                'userNames.*' => 'string',
                'groupChatId' => 'nullable|integer|min:1',
            ]);

            // Validate that userIds and userNames arrays have the same length
            // if (count($data['userIds']) !== count($data['userNames'])) {
            //     return $this->error('userIds and userNames arrays must have the same length', 422);
            // }

            return $this->groupRepository->createGroup($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực:: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update group (requires authentication)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Validate that $id is numeric
            if (!is_numeric($id)) {
                return $this->error('Invalid ID format. ID must be numeric.', 400);
            }

            $id = (int) $id;

            $data = $request->validate([
                'name' => 'sometimes|string|max:255',
                'type' => 'sometimes|string|max:255',
                'is_private' => 'sometimes|boolean',
                'avatar' => 'nullable|string|max:255',
            ]);

            return $this->groupRepository->update($id, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực:: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update group name (requires authentication)
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGroup(Request $request, $groupId)
    {
        try {
            // Validate that $groupId is numeric
            if (!is_numeric($groupId)) {
                return $this->error('ID nhóm phải là số.', 400);
            }

            $groupId = (int) $groupId;

            // Validate request data
            $data = $request->validate([
                'name' => 'required|string|max:255|min:1',
            ]);

            return $this->groupRepository->updateGroupName($groupId, $data['name']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực:: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get group activity detail (requires authentication)
     *
     * @param Request $request
     * @param int $activityId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivityDetail(Request $request, $activityId)
    {
        try {
            // Validate that $activityId is numeric
            if (!is_numeric($activityId)) {
                return $this->error('Id hoạt động phải là số.', 400);
            }

            $activityId = (int) $activityId;
            return $this->groupRepository->getActivityDetail($activityId);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create group activity (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createActivity(Request $request)
    {
        try {
            // Validate request data
            $data = $request->validate([
                'tripId' => 'required|integer|min:1',
                'name' => 'required|string|max:255|min:1',
                'isBalance' => 'required|boolean',
                'note' => 'nullable|string|max:1000',
                'payers' => 'array',
                'payers.*.userId' => 'integer',
                'payers.*.paymentAmount' => 'numeric',
                'senders' => 'required|array|min:1',
                'senders.*.userId' => 'required|integer|min:1',
                'senders.*.amount' => 'required|numeric|min:0',
            ]);

            // Add created_by from current user
            $data['createdBy'] = auth()->id();
            // Map tripId to groupId for repository
            $data['groupId'] = $data['tripId'];

            return $this->groupRepository->createActivity($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực:: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update group activity (requires authentication)
     *
     * @param Request $request
     * @param int $activityId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActivity(Request $request, $activityId)
    {
        try {
            // Validate that $activityId is numeric
            if (!is_numeric($activityId)) {
                return $this->error('Id hoạt động phải là số.', 400);
            }

            $activityId = (int) $activityId;

            // Validate request data
            $data = $request->validate([
                'tripId' => 'required|integer|min:1',
                'name' => 'required|string|max:255|min:1',
                'isBalance' => 'required|boolean',
                'note' => 'nullable|string|max:1000',
                'payers' => 'nullable|array',
                'payers.*.userId' => 'required_with:payers|integer|min:1',
                'payers.*.paymentAmount' => 'required_with:payers|numeric|min:0',
                'senders' => 'required|array|min:1',
                'senders.*.userId' => 'required|integer|min:1',
                'senders.*.amount' => 'required|numeric|min:0',
            ]);

            // Map tripId to groupId for repository
            $data['groupId'] = $data['tripId'];

            return $this->groupRepository->updateActivity($activityId, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực:: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add member to group (requires authentication)
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMember(Request $request, $groupId)
    {
        try {
            // Validate that $groupId is numeric
            if (!is_numeric($groupId)) {
                return $this->error('ID nhóm phải là số.', 400);
            }

            $groupId = (int) $groupId;

            // Validate request data
            $data = $request->validate([
                'userName' => 'nullable|string|max:255',
                'userId' => 'nullable|integer|min:1',
                'groupActivities' => 'required|array|min:1',
                'groupActivities.*.groupActivityId' => 'required|integer|min:1',
                'groupActivities.*.senders' => 'nullable|array',
                'groupActivities.*.senders.*.userId' => 'required_with:groupActivities.*.senders|integer|min:1',
                'groupActivities.*.senders.*.amount' => 'required_with:groupActivities.*.senders|numeric|min:0',
            ]);

            return $this->groupRepository->addMember($groupId, $data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Không thể xác thực:: ' . implode(', ', $e->validator->errors()->all()), 422);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Get group report (requires authentication)
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupReport(Request $request, $groupId)
    {
        try {
            // Validate that $groupId is numeric
            if (!is_numeric($groupId)) {
                return $this->error('ID nhóm phải là số.', 400);
            }

            $groupId = (int) $groupId;

            // Get date filters from request
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');

            // Validate và convert page
            $page = $request->get('page', 1);
            $page = is_numeric($page) ? (int) $page : 1;
            $page = max(1, $page); // Đảm bảo page >= 1

            // Validate và convert per_page
            $perPage = $request->get('per_page', 10);
            $perPage = is_numeric($perPage) ? (int) $perPage : 10;
            $perPage = max(1, min(100, $perPage)); // Đảm bảo 1 <= per_page <= 100

            return $this->groupRepository->getGroupReport($groupId, $startDate, $endDate, $page, $perPage);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Finish group (requires authentication)
     *
     * @param Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function finishGroup(Request $request, $groupId)
    {
        try {
            // Validate that $groupId is numeric
            if (!is_numeric($groupId)) {
                return $this->error('ID nhóm phải là số.', 400);
            }

            $groupId = (int) $groupId;

            return $this->groupRepository->finishGroup($groupId);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete group (requires authentication)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        try {
            // Validate that $id is numeric
            if (!is_numeric($id)) {
                return $this->error('ID phải là số', 400);
            }

            $id = (int) $id;
            return $this->groupRepository->delete($id);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    public function updateAdvance(Request $request, $groupId){
        try {
            // Validate that $id is numeric
            if (!is_numeric($groupId)) {
                return $this->error('ID phải là số', 400);
            }

            $groupId = (int) $groupId;

            $userIds = $request->userIds;

            if(count($userIds) < 1){
                return $this->error('Bạn chưa chọn thành viên', 400);
            }

            if(!isset($request->advance) && is_numeric($request->advance) && $request->advance < 0){
                return $this->error('Số tiền không hợp lệ', 400);
            }
            return $this->groupRepository->updateAdvance($request, $groupId);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
