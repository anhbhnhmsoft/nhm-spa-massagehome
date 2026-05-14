import dotenv from 'dotenv';
import fs from 'fs';
import { fileURLToPath } from 'url';
import path from 'path';

const currentDir = path.dirname(fileURLToPath(import.meta.url));
const envCandidates = [
    path.resolve(currentDir, '.env'),
    path.resolve(currentDir, '..', '.env'),
    path.resolve(currentDir, '..', '..', '.env'),
];

for (const envPath of envCandidates) {
    if (fs.existsSync(envPath)) {
        dotenv.config({ path: envPath, override: true });
        break;
    }
}

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
            support: `${prefix}${process.env.REDIS_CHANNEL_SUPPORT || 'support_messages'}`,
        },
        secrets: {
            adminSocket: process.env.ADMIN_SOCKET_SECRET || process.env.APP_KEY || '',
        }
    },
    app: {
        host: process.env.NODE_HOST || '0.0.0.0',
        port: Number(process.env.NODE_PORT) || 3000,
    },
    database: {
        user: process.env.DB_USERNAME,
        host: process.env.DB_HOST,
        database: process.env.DB_DATABASE,
        password: process.env.DB_PASSWORD,
        port: parseInt(process.env.DB_PORT || '5432'),
    }
};
