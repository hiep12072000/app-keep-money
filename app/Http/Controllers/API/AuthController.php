<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\APP_KEEP_MONEY\ChangePasswordRequest;
use App\Http\Requests\APP_KEEP_MONEY\RegisterRequest;
use App\Http\Requests\APP_KEEP_MONEY\ResetPasswordRequest;
use App\Http\Requests\APP_KEEP_MONEY\SendEmailRequest;
use App\Http\Requests\APP_KEEP_MONEY\VerifyOtpRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Interfaces\APP_KEEP_MONEY\UserInterface;
use App\Mail\OtpMail;
use App\Models\APP_KEEP_MONEY\PasswordResetOtp;
use App\Models\APP_KEEP_MONEY\User;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use ResponseAPI;

    protected $userRepository;

    public function __construct(UserInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register new user
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            // Tạo user mới
            $user = User::create([
                'full_name' => $request->fullName, // Map fullName to full_name
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // Tạo JWT token cho user mới
            $credentials = [
                'phone' => $request->phone,
                'password' => $request->password
            ];

            $token = Auth::guard('api')->attempt($credentials);

            // Trả về thông tin user và JWT token
            return $this->success('Đăng ký thành công', [
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name, // Map full_name to fullName in response
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            ], 201);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Login user with phone and password
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            // Lấy credentials từ request
            $credentials = [
                'phone' => $request->phone,
                'password' => $request->password
            ];

            // Thử đăng nhập và tạo JWT token
            if (!$token = Auth::guard('api')->attempt($credentials)) {
                return $this->error('Số điện thoại hoặc mật khẩu không đúng', 401);
            }

            // Lấy thông tin user đã đăng nhập
            $user = Auth::guard('api')->user();

            // Trả về thông tin user và JWT token
            return $this->success('Đăng nhập thành công', [
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60, // TTL in seconds
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Logout user (revoke token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Invalidate JWT token
            Auth::guard('api')->logout();

            return $this->success('Đăng xuất thành công', null);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get current user info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();

            return $this->success('Lấy thông tin user thành công', [
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Refresh JWT token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $token = Auth::guard('api')->refresh();
            $user = Auth::guard('api')->user();

            return $this->success('Token đã được làm mới', [
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send OTP to email for password reset
     *
     * @param SendEmailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEmail(SendEmailRequest $request)
    {
        try {
            // Tìm user theo email
            $user = User::where('email', $request->email)->first();

            // Tạo OTP mới
            $otpRecord = PasswordResetOtp::createForEmail($request->email, 5); // 5 phút

            // Gửi email
            Mail::to($request->email)->send(new OtpMail($otpRecord->otp, $user->full_name));

            return $this->success('Mã OTP đã được gửi đến email của bạn', [
                'email' => $request->email,
                'expires_in' => 300, // 5 minutes in seconds
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra khi gửi email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify OTP and allow password reset
     *
     * @param VerifyOtpRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            // Tìm OTP hợp lệ
            $otpRecord = PasswordResetOtp::findValidOtp($request->email, $request->otp);

            if (!$otpRecord) {
                return $this->error('Mã OTP không đúng hoặc đã hết hạn', 400);
            }

            // Đánh dấu OTP đã sử dụng
            $otpRecord->markAsUsed();

            // Cập nhật can_change_password = 1 cho user
            $user = User::where('email', $request->email)->first();
            $user->update(['can_change_password' => 1]);

            return $this->success('Xác thực OTP thành công. Bạn có thể đổi mật khẩu ngay bây giờ', [
                'email' => $request->email,
                'can_change_password' => true,
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset password (only for users with can_change_password = 1)
     *
     * @param ResetPasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            // Tìm user theo email
            $user = User::where('email', $request->email)->first();

            // Kiểm tra quyền đổi mật khẩu
            if (!$user->can_change_password) {
                return $this->error('Bạn không có quyền đổi mật khẩu. Vui lòng xác thực OTP trước', 403);
            }

            // Cập nhật mật khẩu mới
            $user->update([
                'password' => Hash::make($request->password),
                'can_change_password' => 0, // Reset lại quyền
            ]);

            // Xóa tất cả OTP cũ của user này
            PasswordResetOtp::where('email', $request->email)->delete();

            return $this->success('Đổi mật khẩu thành công', [
                'email' => $request->email,
                'message' => 'Mật khẩu đã được cập nhật thành công',
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Change user password (requires authentication)
     *
     * @param ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::guard('api')->user();

            // Kiểm tra user có tồn tại không (token hợp lệ)
            if (!$user) {
                return $this->error('Token không hợp lệ hoặc đã hết hạn', 401);
            }

            // Kiểm tra mật khẩu cũ có đúng không
            if (!Hash::check($request->oldPassword, $user->password)) {
                return $this->error('Mật khẩu cũ không đúng', 400);
            }

            // Cập nhật mật khẩu mới
            $user->update([
                'password' => Hash::make($request->newPassword),
            ]);

            return $this->success('Đổi mật khẩu thành công', [
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                ],
                'message' => 'Mật khẩu đã được cập nhật thành công',
            ]);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->error('Token đã hết hạn', 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return $this->error('Token không hợp lệ', 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->error('Token không được cung cấp', 401);
        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user profile (requires authentication)
     *
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::guard('api')->user();

            // Chuẩn bị dữ liệu cập nhật
            $updateData = [];

            if ($request->has('fullName')) {
                $updateData['full_name'] = $request->fullName;
            }

            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }

            if ($request->has('avatar')) {
                $updateData['avatar'] = $request->avatar;
            }

            // Cập nhật thông tin user
            $user->update($updateData);

            // Refresh user data
            $user->refresh();

            return $this->success('Cập nhật thông tin thành công', [
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                ],
                'updated_fields' => array_keys($updateData),
                'updated_at' => $user->updated_at->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Find current user info (requires authentication)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function findMyself()
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::guard('api')->user();

            // Sử dụng repository để lấy thông tin user
            return $this->userRepository->findMyself($user->id);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Find all users except current user (requires authentication)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findExceptMe(Request $request)
    {
        try {
            // Lấy user hiện tại từ JWT token
            $user = Auth::guard('api')->user();

            // Lấy parameters từ request
            $keyword = $request->get('keyword');
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);

            // Sử dụng repository để lấy danh sách users
            return $this->userRepository->findExceptMe($user->id, $keyword, $page, $perPage);

        } catch (\Exception $e) {
            return $this->error('Có lỗi xảy ra: ' . $e->getMessage(), 500);
        }
    }
}
