<?php

namespace App\Enums;

enum AffiliateRegistrationStatus: int
{
    case PENDING = 1;
    case APPROVED = 2;
    case REJECTED = 3;
}
