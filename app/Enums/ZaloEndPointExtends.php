<?php

namespace App\Enums;

use Zalo\ZaloEndPoint;

class ZaloEndPointExtends extends ZaloEndPoint
{
    const API_OA_SEND_ZNS = 'https://business.openapi.zalo.me/message/template';
    const API_REFRESH_TOKEN = 'https://oauth.zaloapp.com/v4/access_token'; // for user
    const API_OA_ACCESS_TOKEN = 'https://oauth.zaloapp.com/v4/oa/access_token'; // for oa
    const REDIRECT_URL_PERMISSION_OA = 'https://oauth.zaloapp.com/oa/permission';
    const REDIRECT_URL_PERMISSION_USER = 'https://oauth.zaloapp.com/permission';

}
