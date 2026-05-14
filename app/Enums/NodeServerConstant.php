<?php

namespace App\Enums;

/**
 * Node Server Constant - Các hằng số sử dụng trong Node Server
 */
final class NodeServerConstant
{
    /**
     * Service Chat
     */
    const CHAT_MESSAGE_NEW = 'message:new'; // Type message:new - Tin nhắn mới
    const CHAT_CONVERSATION_UPDATE = 'conversation:update';
    const SUPPORT_TICKET_CREATED = 'support:ticket:created';
    const SUPPORT_TICKET_CLAIMED = 'support:ticket:claimed';
    const SUPPORT_TICKET_CLOSED = 'support:ticket:closed';
    const SUPPORT_MESSAGE_NEW = 'support:message:new';
}
