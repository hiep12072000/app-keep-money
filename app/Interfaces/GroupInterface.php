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

    /**
     * Get group activity detail
     * 
     * @param   integer     $activityId
     * 
     * @method  GET api/group/activity/detail/{activityId}
     * @access  public
     */
    public function getActivityDetail($activityId);

    /**
     * Create group activity
     * 
     * @param   array       $data
     * 
     * @method  POST api/group/activity
     * @access  public
     */
    public function createActivity($data);

    /**
     * Update group activity
     * 
     * @param   integer     $activityId
     * @param   array       $data
     * 
     * @method  PATCH api/group/update-activity/{activityId}
     * @access  public
     */
    public function updateActivity($activityId, $data);

    /**
     * Add member to group
     * 
     * @param   integer     $groupId
     * @param   array       $data
     * 
     * @method  POST api/group/add-member/{groupId}
     * @access  public
     */
    public function addMember($groupId, $data);

    /**
     * Finish group (update status to done)
     * 
     * @param   integer     $groupId
     * 
     * @method  PATCH api/group/finish-group/{groupId}
     * @access  public
     */
    public function finishGroup($groupId);

    /**
     * Create group
     * 
     * @param   array       $data
     * 
     * @method  POST api/group
     * @access  public
     */
    public function createGroup($data);

    /**
     * Get group report
     * 
     * @param   integer     $groupId
     * 
     * @method  GET api/group/get-group-report/{groupId}
     * @access  public
     */
    public function getGroupReport($groupId);
}
