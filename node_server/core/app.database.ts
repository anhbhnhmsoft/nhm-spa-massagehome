import { Pool } from 'pg';
import { config } from '#/core/app.config';


export const dbPool = new Pool({
    user: config.database.user,
    host: config.database.host,
    database: config.database.database,
    password: config.database.password,
    port: config.database.port,
    max: 10,
    idleTimeoutMillis: 30000,
    connectionTimeoutMillis: 2000,
});

dbPool.on('connect', () => {
    console.log('✅ Connected to Laravel Database');
});

dbPool.on('error', (err) => {
    console.error('❌ Unexpected error on idle client', err);
});

export async function safeQuery(
    text: string,
    params: any[],
    retries = 3,
): Promise<any> {
    for (let i = 0; i < retries; i++) {
        try {
            return await dbPool.query(text, params);
        } catch (err: any) {
            const isConnectionError =
                ['ECONNREFUSED', '57P01', '57P02', '57P03'].includes(
                    err.code,
                ) || err.message.includes('terminated');

            if (isConnectionError && i < retries - 1) {
                const delay = Math.pow(2, i) * 1000; // Exponential backoff: 1s, 2s, 4s...
                console.warn(
                    `🔄 DB Connection lost. Retrying in ${delay}ms... (Attempt ${i + 1}/${retries})`,
                );
                await new Promise((res) => setTimeout(res, delay));
                continue;
            }
            throw err;
        }
    }
}

