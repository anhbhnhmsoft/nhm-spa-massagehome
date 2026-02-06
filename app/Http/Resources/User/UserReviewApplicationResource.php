<?php

namespace App\Http\Resources\User;

use App\Core\Helper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserReviewApplicationResource extends JsonResource
{

    public function toArray($request)
    {
        $bio = $this->getTranslations('bio');
        $user = $this->user;

        $cccdFront = $user->cccdFront;
        $cccdBack = $user->cccdBack;
        $faceWithIdentityCard = $user->faceWithIdentityCard;

        $certificate = $user->certificate;
        $gallery = $user->gallery;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nickname' => $this->nickname,
            'referrer_id' => $this->referrer_id,
            'status' => $this->status,
            'role' => $this->role,
            'reason_cancel' => (string)$this->note,
            'bio' => $bio,
            'is_leader' => $this->is_leader,
            'application_date' => $this->application_date,
            'experience' => $this->experience,

            'gallery' => $gallery ? $gallery->map(function ($item) {
                if ($item->file_path) {
                    return Helper::getPublicUrl($item->file_path);
                }
                return null;
            }) : null,
            'cccd_front' => $cccdFront ? Helper::getPrivateUrl($cccdFront->id) : null,
            'cccd_back' => $cccdBack ? Helper::getPrivateUrl($cccdBack->id) : null,
            'face_with_identity_card' => $faceWithIdentityCard ? Helper::getPrivateUrl($faceWithIdentityCard->id) : null,
            'certificate' => $certificate ? Helper::getPrivateUrl($certificate->id) : null,
        ];
    }

}
