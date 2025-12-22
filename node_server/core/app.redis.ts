import Redis from 'ioredis';
import {config} from '@/core/app.config'
// Client để Publish (Gửi đi)
export const redisPub = new Redis({
    host: config.redis.host,
    port: config.redis.port,
    password: config.redis.password,
});

// Client để Subscribe (Lắng nghe)
// Redis yêu cầu 1 connection riêng biệt cho việc subscribe
export const redisSub = new Redis({
    host: config.redis.host,
    port: config.redis.port,
    password: config.redis.password,
});

console.log('Redis Core Initialized');
