<?php

namespace App\Interfaces\APP_KEEP_MONEY;

interface FriendInterface
{
    /**
     * Get list of friends for authenticated user
     *
     * @param int $userId
     * @param string|null $keyword
     * @param string|null $status
     * @param int $page
     * @param int $perPage
     * @return mixed
     */
    public function getList($userId, $keyword = null, $status = null, $page = 1, $perPage = 10);

    /**
     * Create friend request
     *
     * @param int $senderId
     * @param string $phone
     * @return mixed
     */
    public function create($senderId, $phone);

    /**
     * Update friend request status
     *
     * @param int $friendId
     * @param string $status
     * @param int $userId
     * @return mixed
     */
    public function updateStatus($friendId, $status, $userId);
}
