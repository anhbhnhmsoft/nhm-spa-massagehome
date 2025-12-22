import { Expo, ExpoPushMessage } from 'expo-server-sdk';
import { redisSub } from '#/core/app.redis.js';
import { config } from '#/core/app.config.js';

interface NotificationPayload {
    tokens: string[];
    title: string;
    body: string;
    data?: any;
}

export class NotificationService {
    private expo: Expo;

    constructor() {
        this.expo = new Expo();
    }

    public init() {
        console.log(`🔔 Notification Service: Listening on channel "${config.redis.channels.notification}"`);

        // Đăng ký kênh
        redisSub.subscribe(config.redis.channels.notification);

        // Lắng nghe message
        redisSub.on('message', (channel, message) => {
            if (channel === config.redis.channels.notification) {
                this.handleMessage(message);
            }
        });
    }

    private async handleMessage(rawMessage: string) {
        try {
            const payload = JSON.parse(rawMessage) as NotificationPayload;
            const { tokens, title, body, data } = payload;

            if (!tokens || tokens.length === 0) return;

            console.log(`📨 Sending notification to ${tokens.length} devices: ${title}`);

            const messages: ExpoPushMessage[] = [];
            for (const token of tokens) {
                if (!Expo.isExpoPushToken(token)) continue;
                messages.push({
                    to: token,
                    sound: 'default',
                    title,
                    body,
                    data,
                });
            }

            await this.sendChunks(messages);

        } catch (error) {
            console.error('❌ Notification Error:', error);
        }
    }

    private async sendChunks(messages: ExpoPushMessage[]) {
        const chunks = this.expo.chunkPushNotifications(messages);
        for (const chunk of chunks) {
            try {
                await this.expo.sendPushNotificationsAsync(chunk);
            } catch (error) {
                console.error('❌ Error sending chunk:', error);
            }
        }
    }
}
