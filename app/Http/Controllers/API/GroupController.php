<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Interfaces\GroupInterface;
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
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'is_private' => 'boolean',
                'avatar' => 'nullable|string|max:255',
            ]);

            // Thêm created_by từ user hiện tại
            $data['created_by'] = auth()->id();

            return $this->groupRepository->create($data);
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
                return $this->error('Invalid group ID format. ID must be numeric.', 400);
            }
            
            $groupId = (int) $groupId;
            
            // Validate request data
            $data = $request->validate([
                'name' => 'required|string|max:255|min:1',
            ]);

            return $this->groupRepository->updateGroupName($groupId, $data['name']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed: ' . implode(', ', $e->validator->errors()->all()), 422);
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
                return $this->error('Invalid ID format. ID must be numeric.', 400);
            }
            
            $id = (int) $id;
            return $this->groupRepository->delete($id);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
