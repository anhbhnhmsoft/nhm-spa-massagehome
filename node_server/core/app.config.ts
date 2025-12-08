import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

// Setup __dirname cho ESM
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Load .env từ thư mục gốc Laravel (nhảy ra ngoài 3 cấp: src -> node_server -> root)
dotenv.config({ path: path.resolve(__dirname, '../../../.env') });

export const config = {
    redis: {
        host: process.env.REDIS_HOST || '127.0.0.1',
        port: Number(process.env.REDIS_PORT) || 6379,
        password: process.env.REDIS_PASSWORD || undefined,
        channels: {
            notification: process.env.REDIS_CHANNEL_NOTIFICATION || 'expo_notifications',
        }
    },
    app: {
        port: Number(process.env.SOCKET_PORT) || 3000, // Port cho Socket server sau này
    }
};
