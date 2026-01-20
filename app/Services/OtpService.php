<?php
 
namespace App\Services;
 
use Illuminate\Support\Facades\Http;
use Throwable;
 
class OtpService
{
    protected $http;
    protected $url;
 
    public function __construct()
    {
        $this->url = config('otp.url');
        $this->http = Http::withoutVerifying()->withHeaders([
            'ID' => config('otp.app_id'),
            'username' => config('otp.username'),
            'password' => config('otp.password'),
        ]);
    }
 
    public function generateOtp($user)
    {
        try {
            $response = $this->http->post($this->url . '/generator', [
                "appID" => config('otp.app_id'),
                "username" => $user->email,
                "name" => "$user->first_name $user->last_name",
            ]);
 
            if (!$response->ok()) {
                $response->throw();
            }
 
            return $response->json();
 
        } catch (Throwable $th) {
            logger($th);
            return [];
        }
    }
 
    public function verifyOtp($user, $otp)
    {
        try {
            $response = $this->http->post($this->url . '/validator', [
                "appID" => config('otp.app_id'),
                "username" => $user->email,
                "otp" => $otp,
            ]);
 
            if (!$response->ok()) {
                $response->throw();
            }
 
            return $response->json();
 
        } catch (Throwable $th) {
            logger($th);
            return [];
        }
    }
 
}
