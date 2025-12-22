import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

dotenv.config({ path: path.resolve(__dirname, '../../.env') });

const prefix = process.env.REDIS_PREFIX || 'massagehome-redis-';
export const config = {
    redis: {
        host: process.env.REDIS_HOST || '127.0.0.1',
        port: Number(process.env.REDIS_PORT) || 6379,
        password: process.env.REDIS_PASSWORD || undefined,
        prefix: prefix,
        channels: {
            // Nối luôn prefix vào đây
            notification: `${prefix}${process.env.REDIS_CHANNEL_NOTIFICATION || 'expo_notifications'}`,
            chat: `${prefix}${process.env.REDIS_CHANNEL_CHAT || 'chat_messages'}`,
            chat_auth: `${prefix}${process.env.REDIS_CHANNEL_CHAT_AUTH || 'chat_auth'}`,
        }
    },
    app: {
        host: process.env.NODE_HOST || '0.0.0.0',
        port: Number(process.env.NODE_PORT) || 3000,
    }
};
