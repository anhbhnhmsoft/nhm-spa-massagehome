module.exports = {
    apps: [
        {
            name: "laravel-node-server",
            script: "./dist/index.js",
            cwd: "./", // Đảm bảo PM2 đứng ở gốc Laravel để process.cwd() lấy đúng file .env
            instances: 1,
            autorestart: true,
            watch: false,
            env: {
                NODE_ENV: "production"
            }
        }
    ]
};
