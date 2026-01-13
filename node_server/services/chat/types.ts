export enum _ChatConstant {
    CHAT_MESSAGE_NEW = 'message:new',
    CHAT_CONVERSATION_UPDATE = "conversation:update"
}


export type UserSession = {
    id: string;
    name: string;
    room_id: string;
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
