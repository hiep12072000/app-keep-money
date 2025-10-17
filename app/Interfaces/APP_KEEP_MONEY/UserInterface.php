<?php

namespace App\Interfaces\APP_KEEP_MONEY;

use App\Http\Requests\APP_KEEP_MONEY\UserRequest;
use App\Interfaces\DELETE;
use App\Interfaces\GET;
use App\Interfaces\POST;
use App\Interfaces\PUT;

interface UserInterface
{
    /**
     * Get all users
     *
     * @param   integer     $page
     * @param   integer     $perPage
     *
     * @method  GET api/users
     * @access  public
     */
    public function getAllUsers($page = 1, $perPage = 10);

    /**
     * Get User By ID
     *
     * @param   integer     $id
     *
     * @method  GET api/users/{id}
     * @access  public
     */
    public function getUserById($id);

    /**
     * Create | Update user
     *
     * @param   \App\Http\Requests\APP_KEEP_MONEY\UserRequest    $request
     * @param   integer                           $id
     *
     * @method  POST    api/users       For Create
     * @method  PUT     api/users/{id}  For Update
     * @access  public
     */
    public function requestUser(UserRequest $request, $id = null);

    /**
     * Delete user
     *
     * @param   integer     $id
     *
     * @method  DELETE  api/users/{id}
     * @access  public
     */
    public function deleteUser($id);

    /**
     * Find current user info
     *
     * @param   integer     $id
     *
     * @method  GET api/user/find-myself
     * @access  public
     */
    public function findMyself($id);

    /**
     * Find all users except current user
     *
     * @param   integer     $currentUserId
     * @param   string      $keyword
     * @param   integer     $page
     * @param   integer     $perPage
     *
     * @method  GET api/user/find-except-me?keyword=$keyword
     * @access  public
     */
    public function findExceptMe($currentUserId, $keyword = null, $page = 1, $perPage = 10);
}
