import express from 'express';
import { createServer } from 'http';
import cors from 'cors';
import { Server } from 'socket.io';
import { config } from '@/core/app.config';
import { NotificationService } from '@/services/notification.service';
import { ChatService } from '@/services/chat/chat.service';


const bootstrap = async () => {
    // 1. Khởi tạo Express & HTTP Server
    const app = express();
    const httpServer = createServer(app);

    // Cấu hình Middleware cơ bản
    app.use(cors()); // Cho phép mọi nguồn (hoặc config cụ thể sau)
    app.use(express.json());

    // Khởi tạo Socket.IO server
    const io = new Server(httpServer, {
        cors: {
            origin: '*',
            methods: ['GET', 'POST'],
        },
    });

    // Notification Service
    const notificationService = new NotificationService();
    notificationService.init();

    // Chat Service
    const chatService = new ChatService(io);
    chatService.init();

    // 3. Mở Port lắng nghe (Start Server)
    const PORT = config.app.port;
    const HOST = config.app.host;

    httpServer.listen(PORT, HOST, () => {
        console.log(`🚀 Node Server running at http://${HOST}:${PORT}`);
    });

    // 4. Xử lý tín hiệu ngắt (graceful shutdown)
    process.on('SIGTERM', () => {
        console.log('SIGTERM received. Closing server...');
        httpServer.close(() => process.exit(0));
    });
}

bootstrap();
