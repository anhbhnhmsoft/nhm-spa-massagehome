<?php

namespace App\Services;

use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

class AgencyService extends BaseService
{
    public function __construct(
        protected UserRepository $userRepository,
    ){}
    /**
     * Danh sách KTV của đại lý đang quản lý
     */
    public function manageKtv() : ServiceReturn
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $ktvs = $this->userRepository->query()->whereHas('ktvsUnderAgency', function($query) use ($user){
                $query->where('agency_id', $user->id);
            })->get();
            return ServiceReturn::success($ktvs);
        }catch (\Exception $exception){
            return ServiceReturn::error($exception->getMessage());
        }
    }
}
