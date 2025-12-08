import express from 'express';
import { createServer } from 'http';
import cors from 'cors';
import { config } from './core/app.config.ts';
import { NotificationService } from './services/notification.service.ts';
const bootstrap = async () => {
    // 1. Khởi tạo Express & HTTP Server
    const app = express();
    const httpServer = createServer(app);

    // Cấu hình Middleware cơ bản
    app.use(cors()); // Cho phép mọi nguồn (hoặc config cụ thể sau)
    app.use(express.json());

    console.log('🔄 Initializing Services...');

    // Notification Service (Vẫn lắng nghe Redis như cũ)
    const notificationService = new NotificationService();
    notificationService.init();

    // 3. Mở Port lắng nghe (Start Server)
    const PORT = config.app.port;

    httpServer.listen(PORT, () => {
        console.log(`🚀 Node Server running at http://localhost:${PORT}`);
    });

    // Graceful Shutdown
    process.on('SIGTERM', () => {
        console.log('SIGTERM received. Closing server...');
        httpServer.close(() => process.exit(0));
    });
}

bootstrap();
