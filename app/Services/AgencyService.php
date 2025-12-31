<?php

namespace App\Services;

use App\Core\Controller\FilterDTO;
use App\Core\LogHelper;
use App\Core\Service\BaseService;
use App\Core\Service\ServiceReturn;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

class AgencyService extends BaseService
{
    public function __construct(
        protected UserRepository $userRepository,
    )
    {
    }

    /**
     * Danh sách KTV của đại lý đang quản lý
     */
    public function manageKtv(FilterDTO $filterDTO): ServiceReturn
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $query = $this->userRepository->query()->whereHas('ktvsUnderAgency', function ($query) use ($user) {
                $query->where('agency_id', $user->id);
            });

            // Filter
            $this->userRepository->filterQuery($query, $filterDTO->filters);

            // Sort
            $this->userRepository->sortQuery($query, $filterDTO->sortBy, $filterDTO->direction);

            // Paginate
            $ktvs = $query->paginate(perPage: $filterDTO->perPage, page: $filterDTO->page);
            return ServiceReturn::success($ktvs);
        } catch (\Exception $exception) {
            LogHelper::error(
                message: 'AgencyService@manageKtv'.$exception->getMessage(),
                ex: $exception,
            );
            return ServiceReturn::error($exception->getMessage());
        }
    }
}
