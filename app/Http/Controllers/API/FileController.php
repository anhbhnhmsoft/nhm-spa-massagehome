<?php

namespace App\Http\Controllers\API;

use App\Core\Controller\BaseController;
use App\Enums\ContractFileType;
use App\Http\Resources\Commercial\ContractResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FileController extends BaseController
{
    public function __construct(
        protected UserService $userService
    )
    {
    }


    public function getContract(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'integer', Rule::in(ContractFileType::values())],
        ], [
            'type.required' => __('validation.type_contract.required'),
            'type.integer' => __('validation.type_contract.integer'),
            'type.in' => __('validation.type_contract.in'),
        ]);
        $type = ContractFileType::from($data['type']);
        $result = $this->userService->getContractFile($type);

        if ($result->isError()) {
            return $this->sendError($result->getMessage());
        }
        $data = $result->getData();
        return $this->sendSuccess(data: new ContractResource($data));
    }
}
