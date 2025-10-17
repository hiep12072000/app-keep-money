<?php

namespace App\Http\Controllers\APP_KEEP_MONEY;

use App\Http\Controllers\Controller;
use App\Http\Requests\APP_KEEP_MONEY\UserRequest;
use App\Interfaces\APP_KEEP_MONEY\UserInterface;

class UserController extends Controller
{
    protected $userInterface;

    /**
     * Create a new constructor for this controller
     */
    public function __construct(UserInterface $userInterface)
    {
        $this->userInterface = $userInterface;
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(\Illuminate\Http\Request $request)
    {
        try {
            // Validate và convert page
            $page = $request->get('page', 1);
            $page = is_numeric($page) ? (int) $page : 1;
            $page = max(1, $page); // Đảm bảo page >= 1

            // Validate và convert per_page
            $perPage = $request->get('per_page', 10);
            $perPage = is_numeric($perPage) ? (int) $perPage : 10;
            $perPage = max(1, min(100, $perPage)); // Đảm bảo 1 <= per_page <= 100

            return $this->userInterface->getAllUsers($page, $perPage);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\APP_KEEP_MONEY\UserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        return $this->userInterface->requestUser($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->userInterface->getUserById($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\APP_KEEP_MONEY\UserRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        return $this->userInterface->requestUser($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->userInterface->deleteUser($id);
    }
}
