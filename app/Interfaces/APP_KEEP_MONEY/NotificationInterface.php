<?php

namespace App\Interfaces\APP_KEEP_MONEY;

interface NotificationInterface
{
    /**
     * Get list of notifications for authenticated user
     *
     * @param int $userId
     * @param string|null $type
     * @param int $page
     * @param int $perPage
     * @return mixed
     */
    public function getList($userId, $type = null, $page = 1, $perPage = 10);

    /**
     * Mark notification as seen
     *
     * @param int $notificationId
     * @param int $userId
     * @return mixed
     */
    public function seen($notificationId, $userId);
}
