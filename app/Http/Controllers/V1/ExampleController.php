<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

/**
 * @group Example
 */
class ExampleController extends ApiController
{
    /**
     * <title>
     *
     * <description>
     *
     * @authenticated / @unauthenticated
     *
     * @header <name> <value>
     *
     * @urlParam <name> <type?string,integer,number> required? <description?> Enum: <list of values?> Example: <example?>
     *
     * @queryParam <name> <type?string?[],integer?[],number?[],boolean?[],object?[],file?[]> required? <description?> Example: <example?>
     *
     * @apiResourceCollection scenario=Success App\Http\Resources\V1\ApiResource
     *
     * @apiResource scenario=Success App\Http\Resources\V1\ApiResource
     *
     * @apiResourceModel App\Models\Model with=relation1.relation11,relation2,... paginate=10
     *
     * @response 200 scenario=Success {
     *       "message": "message",
     *       "status": 200
     * }
     * @response 200 scenario=Success {
     *       "message": "message",
     *       "data": [
     *           "field": "value"
     *       ]
     *       "status": 200
     * }
     * @response 400 scenario=Error {
     *       "message": "message",
     *       "status": 400
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "message",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "message",
     *       "status": 404
     * }
     * @response 429 scenario="Rate Limit Exceeded" {
     *       "message": "message",
     *       "status": 429
     * }
     */
    public function fn(Request $request) {}
}
