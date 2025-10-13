<?php

namespace App\Interfaces;

interface GroupInterface
{
    /**
     * Get all groups with pagination and search
     * 
     * @param   string      $keyword
     * @param   integer     $page
     * @param   integer     $perPage
     * 
     * @method  GET api/group?keyword=$keyword
     * @access  public
     */
    public function getList($keyword = null, $page = 1, $perPage = 10);

    /**
     * Get group by ID
     * 
     * @param   integer     $id
     * 
     * @method  GET api/group/{id}
     * @access  public
     */
    public function getById($id);

    /**
     * Get group detail with members and activities
     * 
     * @param   integer     $id
     * 
     * @method  GET api/group/detail/{id}
     * @access  public
     */
    public function getDetail($id);

    /**
     * Create new group
     * 
     * @param   array       $data
     * 
     * @method  POST api/group
     * @access  public
     */
    public function create($data);

    /**
     * Update group
     * 
     * @param   integer     $id
     * @param   array       $data
     * 
     * @method  PUT api/group/{id}
     * @access  public
     */
    public function update($id, $data);

    /**
     * Delete group
     * 
     * @param   integer     $id
     * 
     * @method  DELETE api/group/{id}
     * @access  public
     */
    public function delete($id);

    /**
     * Update group name only
     * 
     * @param   integer     $groupId
     * @param   string      $name
     * 
     * @method  PATCH api/group/update-group/{groupId}
     * @access  public
     */
    public function updateGroupName($groupId, $name);
}
