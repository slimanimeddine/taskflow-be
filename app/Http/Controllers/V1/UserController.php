<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\DeleteUserRequest;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;

/**
 * @group Users
 */
class UserController extends ApiController
{
    /**
     * Get Authenticated User
     *
     * Retrieve the currently authenticated user
     *
     * @authenticated
     *
     * @apiResource scenario=Success App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User
     *
     * @response 401 scenario=Unauthenticated {
     *      "message": "Unauthenticated"
     * }
     */
    public function getAuthenticatedUser(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Delete User
     *
     * Deletes the authenticated user
     *
     * @authenticated
     *
     * @response 200 scenario=Success {
     *      "message": "User deleted successfully",
     *     "status": 200
     * }
     * @response 400 scenario="Incorrect password" {
     *      "message": "Incorrect password.",
     *      "status": 400
     * }
     * @response 401 scenario=Unauthenticated {
     *     "message": "Unauthenticated"
     * }
     */
    public function deleteUser(DeleteUserRequest $request)
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        $user->delete();

        return $this->successNoData('User deleted successfully');
    }
}
