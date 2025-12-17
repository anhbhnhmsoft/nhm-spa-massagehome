import type { Server, Socket } from 'socket.io';
import { redisSub } from '../core/app.redis.ts';
import { config } from '../core/app.config.ts';

interface SendMessagePayload {
    conversationId: string;
    text: string;
    tempId?: string;
}

interface SendMessageAck {
    ok: boolean;
    message?: any;
}

export class ChatService {
    constructor(private io: Server) {}

    public init() {
        this.io.on('connection', (socket) => this.handleConnection(socket));

        // Listen Redis pub/sub from Laravel (chat_messages)
        console.log(`💬 ChatService: Listening on channel "${config.redis.channels.chat}"`);
        redisSub.subscribe(config.redis.channels.chat);
        redisSub.on('message', (channel, message) => {
            if (channel !== config.redis.channels.chat) return;
            this.handleRedisMessage(message);
        });

        console.log('💬 ChatService initialized');
    }

    private handleRedisMessage(rawMessage: string) {
        try {
            const parsed = JSON.parse(rawMessage);
            const type = parsed?.type;
            const payload = parsed?.payload;

            if (!payload?.conversationId) {
                return;
            }

            // Laravel publish type: message:new
            if (type === 'message:new') {
                const room = this.getConversationRoom(String(payload.conversationId));
                this.io.to(room).emit('message:new', payload);
            }
        } catch (error) {
            console.error('❌ ChatService@handleRedisMessage error', error);
        }
    }

    private handleConnection(socket: Socket) {
        console.log('✨ Socket connected:', socket.id);

        // TODO: Thêm middleware auth (dùng socket.handshake.auth.token)

        // Join room theo conversation
        socket.on('join', ({ conversationId }: { conversationId: string }) => {
            if (!conversationId) return;
            const room = this.getConversationRoom(conversationId);
            socket.join(room);
        });

        // Gửi message
        socket.on('message:send', async (payload: SendMessagePayload, cb?: (ack: SendMessageAck) => void) => {
            try {
                if (!payload?.conversationId || !payload?.text) {
                    cb?.({ ok: false });
                    return;
                }

                // TODO: Gọi API Laravel để lưu DB, validate user quyền gửi message
                const message = {
                    id: Date.now().toString(), // tạm, sau sẽ dùng id từ DB
                    conversationId: payload.conversationId,
                    text: payload.text,
                    tempId: payload.tempId,
                    createdAt: new Date().toISOString(),
                };

                const room = this.getConversationRoom(payload.conversationId);

                // Broadcast tới tất cả client trong room
                this.io.to(room).emit('message:new', message);

                cb?.({ ok: true, message });
            } catch (error) {
                console.error('❌ ChatService@message:send error', error);
                cb?.({ ok: false });
            }
        });
    }

    private getConversationRoom(conversationId: string): string {
        return `conversation:${conversationId}`;
    }
}


