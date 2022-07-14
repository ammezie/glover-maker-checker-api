<?php

namespace App\Http\Controllers;

use App\Events\RequestCreated;
use App\Models\Request;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    /**
     * Retrieve pending requests.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function index(HttpRequest $httpRequest)
    {
        // Fetch only requests by other admins
        $requests = Request::query()
            ->where('requested_by', '<>', $httpRequest->user()->id)
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Requests retrieved.',
            'data' => $requests,
        ]);
    }

    /**
     * Create a new request.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function store(HttpRequest $httpRequest)
    {
        $validatedData = $httpRequest->validate([
            'type' => [
                'required',
                'string',
                Rule::in(['create', 'update', 'delete']),
            ],
            'data' => 'array'
        ]);

        $request = Request::create([
            'type' => $validatedData['type'],
            'status' => 'pending',
            'data' => $validatedData['data'],
            'requested_by' => $httpRequest->user()->id,
        ]);

        // Fire event which will be in turn used to send request emails to other admins
        event(new RequestCreated($request));

        return response()->json([
            'status' => true,
            'message' => 'Request created.',
            'data' => $request,
        ], 201);
    }

    /**
     * Approve a request.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function approve(HttpRequest $httpRequest, Request $request)
    {
        // Can only approve a pending request
        if ($request->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Request already approved.',
                'data' => null,
            ], 403);
        }

        // Ensure admins cannot approve their requests
        if ($request->requested_by === $httpRequest->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Request can only be approved by other administrators.',
                'data' => null,
            ], 403);
        }

        // Perform the requested action
        switch ($request->type) {
            case 'create':
                User::create([
                    'first_name' => $request->data['first_name'],
                    'last_name' => $request->data['last_name'],
                    'email' => $request->data['email'],
                    'password' => bcrypt($request->data['password']),
                ]);

                break;

            case 'update':
                $user = User::findOrFail($request->data['user_id']);

                $user->first_name = $request->data['first_name'];
                $user->last_name = $request->data['last_name'];
                $user->email = $request->data['email'];

                $user->save();

                break;

            default:
                $user = User::findOrFail($request->data['user_id']);

                $user->delete();

                break;
        }

        // Mark request as approved
        $request->status = 'approved';
        $request->approved_by = $httpRequest->user()->id;

        $request->save();

        return response()->json([
            'status' => true,
            'message' => 'Request approved.',
            'data' => $request,
        ]);
    }

    /**
     * Decline a request, thus deleting that request.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function decline(HttpRequest $httpRequest, Request $request)
    {
        // Can only decline a pending request
        if ($request->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Request already approved.',
                'data' => null,
            ], 403);
        }

        // Ensure admins cannot decline their requests
        if ($request->requested_by === $httpRequest->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Request can only be declined by other administrators.',
                'data' => null,
            ], 403);
        }

        // Declining a request simply means deleting it
        $request->delete();

        return response()->json([
            'status' => true,
            'message' => 'Request declined.',
            'data' => null,
        ]);
    }
}
