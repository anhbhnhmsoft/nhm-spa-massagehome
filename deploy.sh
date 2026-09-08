#!/usr/bin/env bash

# ==============================================================================
# MasaHome Backend Automated Deployment Script
# Usage: bash deploy.sh
# ==============================================================================

set -e

# Màu sắc thông báo
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

START_TIME=$(date +%s)

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}🚀 BẮT ĐẦU QUY TRÌNH DEPLOY BACKEND MASAHOME...${NC}"
echo -e "${BLUE}======================================================${NC}"

# 1. Kéo code mới từ Git
echo -e "\n${YELLOW}📥 [1/7] Đang kéo code mới từ Git...${NC}"
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "master")
echo -e "Đang pull branch: ${GREEN}${BRANCH}${NC}"
git pull origin "${BRANCH}"

# 2. Cài đặt dependencies (Composer)
if [ -f "composer.json" ]; then
    echo -e "\n${YELLOW}📦 [2/7] Tối ưu hóa Composer Dependencies...${NC}"
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet
fi

# 3. Chạy Migration database
echo -e "\n${YELLOW}🗄️ [3/7] Chạy Database Migrations...${NC}"
php artisan migrate --force

# 4. Tối ưu hóa & Cache hệ thống
echo -e "\n${YELLOW}⚡ [4/7] Dọn dẹp và làm mới Cache hệ thống...${NC}"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Cache Filament nếu có
if php artisan list | grep -q "filament:optimize"; then
    php artisan filament:optimize || true
fi

# 5. Khởi động lại Queue Workers
echo -e "\n${YELLOW}🔄 [5/7] Khởi động lại Queue Workers...${NC}"
php artisan queue:restart

# 6. Khởi động lại Socket Server (PM2) & PHP-FPM
echo -e "\n${YELLOW}🌐 [6/7] Khởi động lại Socket Server & PHP-FPM...${NC}"
if command -v pm2 &> /dev/null; then
    pm2 reload ecosystem.config.cjs || pm2 restart all || true
fi

# Reload PHP-FPM (tự động thử các phiên bản phổ biến)
if command -v systemctl &> /dev/null; then
    sudo systemctl reload php8.3-fpm 2>/dev/null || \
    sudo systemctl reload php8.2-fpm 2>/dev/null || \
    sudo systemctl reload php-fpm 2>/dev/null || true
fi

# 7. Phân quyền thư mục storage & logs
echo -e "\n${YELLOW}🔒 [7/7] Cấp lại quyền cho storage & logs...${NC}"
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod -R 777 storage/logs 2>/dev/null || true

END_TIME=$(date +%s)
EXECUTION_TIME=$((END_TIME - START_TIME))

echo -e "\n${GREEN}======================================================${NC}"
echo -e "${GREEN}✨ DEPLOY THÀNH CÔNG HOÀN TẤT TRONG ${EXECUTION_TIME} GIÂY! ✨${NC}"
echo -e "${GREEN}======================================================${NC}"
