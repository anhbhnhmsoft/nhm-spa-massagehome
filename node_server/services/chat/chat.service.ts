import crypto from 'crypto';
import type { Server, Socket } from 'socket.io';

import { config } from '#/core/app.config';
import { redisPub, redisSub } from '#/core/app.redis';
import { safeQuery } from '#/core/app.database';
import {
    _ChatConstant,
    PayloadNewMessage,
    SessionKind,
    UserSession,
} from '#/services/chat/types';

type SocketSession = UserSession;

export class ChatService {
    constructor(private io: Server) {}

    public init() {
        this.middleware();
        this.io.on('connection', (socket) => this.handleConnection(socket));

        redisSub.subscribe(config.redis.channels.chat, (err) => {
            if (err) console.error('Redis Chat Subscribe Error:', err);
            else console.log(`Subscribed Redis channel: ${config.redis.channels.chat}`);
        });
        redisSub.subscribe(config.redis.channels.support, (err) => {
            if (err) console.error('Redis Support Subscribe Error:', err);
            else console.log(`Subscribed Redis channel: ${config.redis.channels.support}`);
        });

        redisSub.on('message', (channel, message) => {
            if (channel === config.redis.channels.chat) {
                void this.handleChatLaravelMessage(message);
            }
            if (channel === config.redis.channels.support) {
                void this.handleSupportLaravelMessage(message);
            }
        });
    }

    protected async handleChatLaravelMessage(rawMessage: string) {
        try {
            const parsed = JSON.parse(rawMessage);
            const type = parsed?.type;
            if (type === _ChatConstant.CHAT_MESSAGE_NEW && parsed?.payload) {
                const payload: PayloadNewMessage = parsed.payload;
                if (!payload.room_id) return;

                const roomName = this.getConversationRoom(payload.room_id);
                this.io.to(roomName).emit(_ChatConstant.CHAT_MESSAGE_NEW, payload);

                if (payload.receiver_id) {
                    this.io
                        .to(this.getPrivateUserRoom(payload.receiver_id))
                        .emit(_ChatConstant.CHAT_CONVERSATION_UPDATE, payload);
                }
            }
        } catch (error) {
            console.error('ChatService@handleChatLaravelMessage error', error);
        }
    }

    protected async handleSupportLaravelMessage(rawMessage: string) {
        try {
            const parsed = JSON.parse(rawMessage);
            const type = parsed?.type;
            const payload = parsed?.payload;
            const ticket = payload?.ticket;
            if (!type || !ticket) return;

            const roomName = this.getConversationRoom(ticket.room_id ?? `support-ticket:${ticket.id}`);

            // Lấy danh sách socket đang ở trong conversation room
            const socketsInRoom = await this.io.in(roomName).fetchSockets();
            const socketIdsInRoom = new Set(socketsInRoom.map((s) => s.id));

            // Broadcast vào conversation room — mọi thành viên đang join đều nhận
            this.io.to(roomName).emit(type, payload);

            // Chỉ emit private khi customer KHÔNG đang ở trong room (tránh duplicate)
            if (ticket.customer?.id) {
                const customerPrivateRoom = this.getPrivateUserRoom(ticket.customer.id);
                const customerSockets = await this.io.in(customerPrivateRoom).fetchSockets();
                const customerInRoom = customerSockets.some((s) => socketIdsInRoom.has(s.id));
                if (!customerInRoom) {
                    this.io.to(customerPrivateRoom).emit(type, payload);
                }
            }

            // Staff: luôn emit tới private room (sale portal không join conversation room)
            if (ticket.assigned_staff?.id) {
                this.io
                    .to(this.getPrivateAdminRoom(ticket.assigned_staff.id))
                    .emit(type, payload);
            }
        } catch (error) {
            console.error('ChatService@handleSupportLaravelMessage error', error);
        }
    }

    protected handleConnection(socket: Socket) {
        const session = socket.data.session as SocketSession;
        if (!session?.id) {
            socket.disconnect(true);
            return;
        }

        socket.join(this.getPrivateRoom(session));
        this.updateUserOnlineStatus(session.id, true, session.kind);

        socket.on('disconnect', () => this.handleDisconnect(socket));

        socket.on(
            'join',
            (
                { roomId },
                callback?: (data: { status: 'ok' | 'error'; message?: string }) => void,
            ) => {
                const ack = typeof callback === 'function' ? callback : () => {};
                try {
                    if (!roomId) {
                        ack({ status: 'error', message: 'Missing roomId' });
                        return;
                    }

                    socket.join(this.getConversationRoom(roomId));
                    ack({ status: 'ok' });
                } catch (error) {
                    console.error('Join Error:', error);
                    ack({ status: 'error', message: 'Internal Server Error' });
                }
            },
        );

        socket.on(
            'leave',
            (
                { roomId },
                callback?: (data: { status: 'ok' | 'error'; message?: string }) => void,
            ) => {
                const ack = typeof callback === 'function' ? callback : () => {};
                try {
                    if (!roomId) {
                        ack({ status: 'error', message: 'Missing roomId' });
                        return;
                    }

                    socket.leave(this.getConversationRoom(roomId));
                    ack({ status: 'ok' });
                } catch (error) {
                    console.error('Leave Error:', error);
                    ack({ status: 'error', message: 'Internal Server Error' });
                }
            },
        );
    }

    protected handleDisconnect(socket: Socket) {
        const session = socket.data.session as SocketSession;
        if (!session?.id) return;

        socket.leave(this.getPrivateRoom(session));
        this.updateUserOnlineStatus(session.id, false, session.kind);
    }

    protected getConversationRoom(roomId: string): string {
        return `conversation:${roomId}`;
    }

    protected getPrivateUserRoom(userId: string): string {
        return `user:${userId}`;
    }

    protected getPrivateAdminRoom(adminId: string): string {
        return `admin:${adminId}`;
    }

    protected getPrivateRoom(session: SocketSession): string {
        return session.kind === 'admin'
            ? this.getPrivateAdminRoom(session.id)
            : this.getPrivateUserRoom(session.id);
    }

    protected async validateTokenFormat(token: string): Promise<{
        session: SocketSession;
        token: string;
    }> {
        if (!token || typeof token !== 'string') {
            throw new Error('Authentication error: Invalid token format');
        }

        if (token.includes('|')) {
            const [tokenId, plainToken] = token.split('|');
            const hashedToken = crypto.createHash('sha256').update(plainToken).digest('hex');

            const query = `
                SELECT u.id, u.name
                FROM users u
                JOIN personal_access_tokens t ON u.id = t.tokenable_id
                WHERE t.id = $1
                  AND t.token = $2
                  AND t.tokenable_type = 'App\\Models\\User'
                  AND (t.expires_at IS NULL OR t.expires_at > NOW())
                LIMIT 1
            `;

            const result = await safeQuery(query, [tokenId, hashedToken]);
            if (!result || result.rows.length === 0) {
                throw new Error('Authentication error: Session expired or invalid');
            }

            return {
                session: {
                    ...result.rows[0],
                    kind: 'user' as SessionKind,
                } as SocketSession,
                token,
            };
        }

        if (token.startsWith('admin.')) {
            const parts = token.split('.');
            if (parts.length !== 5) {
                throw new Error('Authentication error: Invalid admin token format');
            }

            const [, adminId, expiresAt, nonce, signature] = parts;
            const expiresAtNumber = Number(expiresAt);
            if (!adminId || !expiresAtNumber || Number.isNaN(expiresAtNumber)) {
                throw new Error('Authentication error: Invalid admin token payload');
            }

            if (expiresAtNumber * 1000 < Date.now()) {
                throw new Error('Authentication error: Session expired or invalid');
            }

            const payload = `admin.${adminId}.${expiresAt}.${nonce}`;
            const expectedSignature = crypto
                .createHmac('sha256', config.redis.secrets.adminSocket)
                .update(payload)
                .digest('hex');

            if (expectedSignature !== signature) {
                throw new Error('Authentication error: Invalid admin token signature');
            }

            const query = `
                SELECT id, name
                FROM admin_users
                WHERE id = $1
                  AND is_active = true
                LIMIT 1
            `;
            const result = await safeQuery(query, [adminId]);
            if (!result || result.rows.length === 0) {
                throw new Error('Authentication error: Admin not found');
            }

            return {
                session: {
                    ...result.rows[0],
                    kind: 'admin' as SessionKind,
                } as SocketSession,
                token,
            };
        }

        throw new Error('Authentication error: Invalid token format');
    }

    protected middleware() {
        this.io.use(async (socket, next) => {
            try {
                const token = socket.handshake.auth.token;
                const { session } = await this.validateTokenFormat(token);
                socket.data.session = session;
                socket.data.user = session;
                socket.data.token = token;
                next();
            } catch (error) {
                console.error('Middleware Error:', error);
                return next(new Error('Authentication error: Internal Error'));
            }
        });
    }

    protected async updateUserOnlineStatus(
        userId: string,
        isOnline: boolean,
        kind: SessionKind = 'user',
    ) {
        const statusKey =
            kind === 'admin'
                ? `${config.redis.prefix}admin_online_status:${userId}`
                : `${config.redis.prefix}user_online_status:${userId}`;

        if (isOnline) {
            await redisPub.setex(statusKey, 60, 'online');
        } else {
            await redisPub.del(statusKey);
        }

        this.io.emit(kind === 'admin' ? 'admin_presence_change' : 'user_presence_change', {
            userId,
            status: isOnline ? 'online' : 'offline',
        });
    }
}
