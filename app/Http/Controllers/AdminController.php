<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function dashboard()//لوحة التحكم
    {
        return response()->json([
            'organizers_count'=>User::where('role','organizer')->count(),
            'investors_count'=>User::where('role','investor')->count(),
            'visitors_count'=>User::where('role','visitor')->count(),
            'staffs_count'=>User::where('role','staff')->count(),
            'exhibitions_count'=>Exhibition::count(),
            ]);
    }
    //______________________________________________________________________
    public function pendingUsers()//المستخدمين بانتظار الموافقة
    {
        $user=User::with(['organizer','investor' ,'visitor' ,'staff'])
        ->where('status','pending')
        ->orderByDesc('created_at')
        ->get();

        return response()->json([
        'user'=>$user
        ],200);
    }
    //______________________________________________________________________
    public function approvedUsers()//عرض المستخدمين الموافق عليهم
    {
        $user=User::with('profile')
        ->where('status','approved')
        ->orderByDesc('created_at')
        ->get();

        return response()->json([
        'user'=>$user
        ],200);
    }
    //______________________________________________________________________
    public function approveUser(int $user_id)//الموافقة على المستخدم
    {
        try {
            $user=User::with('profile')
            ->where('status','pending')
            ->findOrFail($user_id);

            $user->update([
            'status'=>'approved',
            ]);

            // //ارسال اشعار للمستخدم
            // $fcm_token=$user->fcmToken->fcm_token;
            // if (!$fcm_token)
            // {
            //     Log::warning("User $user_id approved but has no FCM token.");//
            //     return response()->json(['message' => 'User approved, but no token found.']);
            // }

            // $messaging = app('firebase.messaging');
            // $name=$user->profile->first_name;
            // $verfiycode =rand(100000,999999);

            // $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$fcm_token)
            //     ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Success Apply\nWelcome $name", "$verfiycode"));

            // $response = $messaging->send($message);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
            return response()->json([
            'message'=>'User approved successfully',
            ],200);
    }
    //______________________________________________________________________
    public function rejecteUser(int $user_id)//رفض المستخدم
    {
        try
        {
            $user=User::with('profile')
            ->where('status','pending')
            ->findOrFail($user_id);

            $user->update([
            'status'=>'rejected',
            ]);

            // //ارسال اشعار للمستخدم
            // $token_fcm=$user->profile->token_fcm;
            // if (!$token_fcm)
            // {
            //     Log::warning("User $user_id rejected but has no FCM token.");
            //     return response()->json(['message' => 'User rejected, but no token found.']);
            // }

            //     $messaging = app('firebase.messaging');
            //     $name=$user->profile->first_name;

            //     $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //         ->withNotification(\Kreait\Firebase\Messaging\Notification::create("Faild Apply", "Your application has been rejected."));

            //     $response = $messaging->send($message);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        $user->delete();
        return response()->json([
        'message'=>'User rejected successfully and deleted',
        ],200);
    }
    //______________________________________________________________________
    public function deleteUser(int $user_id)//حذف المستخدم
    {
        try
        {
            $user=User::findorFail($user_id);
            if($user->role==='admin')
            {
                return response()->json([
                'message'=>'Cannot delete admin user',
                ],403);
            }

            $user->tokens()->delete();
            $token_fcm = $user->profile->token_fcm;
            FcmToken::create([
                'fcm_token' => $token_fcm
            ]);

            // //ارسال اشعار للمستخدم
            // if (!$token_fcm)
            // {
            //     Log::warning("User $user_id deleted but has no FCM token.");
            //     return response()->json(['message' => 'User deleted, but no token found.']);
            // }

            //     $messaging = app('firebase.messaging');
            //     $name=$user->profile->first_name;
            //     $verfiycode =rand(100000,999999);

            //     $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token',$token_fcm)
            //         ->withNotification(\Kreait\Firebase\Messaging\Notification::create("You have been blocked", "because of violating the terms of service."));

            //     $response = $messaging->send($message);

            $user->delete();
            return response()->json([
            'message'=>'User delete successfully',
            ],200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    //_____________________________________________________

}

