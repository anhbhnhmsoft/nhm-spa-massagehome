import { config } from '@/core/app.config.ts';
import { redisPub, redisSub } from '@/core/app.redis.ts';
import {
    _ChatConstant,
    PayloadNewMessage,
    UserSession,
} from '@/services/chat/types.ts';
import type { Server, Socket } from 'socket.io';

export class ChatService {
    constructor(private io: Server) {}

    public init() {
        // Middleware xác thực token
        this.middleware();

        // Xử lý khi client kết nối
        this.io.on('connection', (socket) => this.handleConnection(socket));

        // Listen Redis pub/sub from Laravel
        redisSub.subscribe(config.redis.channels.chat, (err) => {
            if (err) console.error('Redis Chat Subscribe Error:', err);
            else
                console.log(
                    `Subscribed Redis channel: ${config.redis.channels.chat}`,
                );
        });

        redisSub.on('message', (channel, message) => {
            if (channel !== config.redis.channels.chat) return;
            this.handleLaravelMessage(message);
        });
    }

    /**
     * Xử lý message từ Redis pub/sub từ Laravel
     */
    protected handleLaravelMessage(rawMessage: string) {
        try {
            const parsed = JSON.parse(rawMessage);
            const type = parsed?.type;
            // Xử lý message:new
            if (type === _ChatConstant.CHAT_MESSAGE_NEW && parsed?.payload) {
                const payload: PayloadNewMessage = parsed?.payload;
                if (payload.room_id) {
                    // Format tên phòng phải KHỚP với lúc join
                    const roomName = this.getConversationRoom(payload.room_id);
                    // Emit tới phòng
                    this.io
                        .to(roomName)
                        .emit(_ChatConstant.CHAT_MESSAGE_NEW, payload);

                    console.log(`Emitted to ${roomName}`);
                }
            }
        } catch (error) {
            console.error('ChatService@handleLaravelMessage error', error);
        }
    }

    /**
     * Xử lý khi client kết nối (QUAN TRỌNG: Đã thêm Callback)
     */
    protected handleConnection(socket: Socket) {
        const user = socket.data.user as UserSession;
        // Tự động join phòng cá nhân (để nhận noti riêng sau này)
        // --- JOIN ROOM CÓ CALLBACK ---
        this.updateUserOnlineStatus(user?.id || '', true);

        socket.on(
            'join',
            (
                { roomId },
                callback: (data: {
                    status: 'ok' | 'error';
                    message?: string;
                }) => void,
            ) => {
                try {
                    if (!roomId) {
                        callback({
                            status: 'error',
                            message: 'Missing roomId',
                        });
                        return;
                    }

                    const roomName = this.getConversationRoom(roomId);
                    socket.join(roomName);

                    // Báo lại cho Client biết là đã vào thành công
                    callback({ status: 'ok' });
                } catch (error) {
                    console.error('Join Error:', error);
                    callback({
                        status: 'error',
                        message: 'Internal Server Error',
                    });
                }
            },
        );

        // --- LEAVE ROOM CÓ CALLBACK ---
        socket.on(
            'leave',
            (
                { roomId },
                callback: (data: {
                    status: 'ok' | 'error';
                    message?: string;
                }) => void,
            ) => {
                try {
                    if (!roomId) {
                        callback({
                            status: 'error',
                            message: 'Missing roomId',
                        });
                        return;
                    }
                    const roomName = this.getConversationRoom(roomId);
                    socket.leave(roomName);
                    if (typeof callback === 'function') {
                        callback({ status: 'ok' });
                    }
                } catch (error) {
                    console.error('Leave Error:', error);
                    // Ignore error on leave
                    callback({
                        status: 'error',
                        message: 'Internal Server Error',
                    });
                }
            },
        );
        socket.on('disconnect', () => {
            console.log(`Socket Disconnected: ${user?.name}`);
            this.updateUserOnlineStatus(user?.id || '', false);
        });
    }

    /**
     * Tạo tên phòng chat từ roomId
     */
    protected getConversationRoom(roomId: string): string {
        return `conversation:${roomId}`;
    }

    /**
     * Xác thực token khi client kết nối
     */
    protected middleware() {
        this.io.use(async (socket, next) => {
            try {
                const token = socket.handshake.auth.token;
                if (!token)
                    return next(
                        new Error('Authentication error: Token missing'),
                    );
                // Key mà Node đang định tìm
                const rawKey = `${config.redis.channels.chat_auth}:${token}`;
                // --- DEBUG LOG ---
                const rawData = await redisPub.get(rawKey);
                if (!rawData) {
                    return next(
                        new Error(
                            'Authentication error: Session expired or invalid',
                        ),
                    );
                }
                const user: UserSession = JSON.parse(rawData);
                socket.data.user = user;
                next();
            } catch (err) {
                console.log('Middleware Error:', err);
                return next(new Error('Authentication error: Internal Error'));
            }
        });
    }

    /**
     * Cập nhật trạng thái online/offline của user
     */
    protected async updateUserOnlineStatus(userId: string, isOnline: boolean) {
        const statusKey = `${config.redis.prefix}user_online_status:${userId}`;

        if (isOnline) {
            // Set key online với TTL (ví dụ 60s) tương đương heartbeat
            await redisPub.setex(statusKey, 60, 'online');
        } else {
            // Khi disconnect chủ động thì xóa luôn key
            await redisPub.del(statusKey);
        }

        // 3. Phát sự kiện Realtime cho toàn hệ thống socket (hoặc chỉ những người liên quan)
        this.io.emit('user_presence_change', {
            userId: userId,
            status: isOnline ? 'online' : 'offline',
        });
    }
}
