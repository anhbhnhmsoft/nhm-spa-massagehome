export enum _ChatConstant {
    CHAT_MESSAGE_NEW = 'message:new',
    CHAT_CONVERSATION_UPDATE = "conversation:update",
    SUPPORT_TICKET_CREATED = 'support:ticket:created',
    SUPPORT_TICKET_CLAIMED = 'support:ticket:claimed',
    SUPPORT_TICKET_CLOSED = 'support:ticket:closed',
    SUPPORT_MESSAGE_NEW = 'support:message:new',
    SUPPORT_TICKET_REASSIGNED = 'support:ticket:reassigned',
    SUPPORT_TICKET_REOPENED = 'support:ticket:reopened',
    SUPPORT_SLA_WARNING = 'support:sla:warning',
    SUPPORT_SLA_BREACHED = 'support:sla:breached',
}

export type SessionKind = 'user' | 'admin';

export type UserSession = {
    id: string;
    name: string;
    kind: SessionKind;
};

export type AdminSession = {
    id: string;
    name: string;
    kind: SessionKind;
};

export type PayloadNewMessage = {
    id: string; // ID tin nhắn
    room_id: string; // ID phòng chat
    content: string; // Nội dung tin nhắn
    sender_id: string; // ID người gửi
    sender_name: string; // Tên người gửi
    created_at: string; // Thời gian tạo tin nhắn (ISO string)
    receiver_id: string; // ID người nhận
    temp_id?: string; // ID tạm thời (nếu có)
};
