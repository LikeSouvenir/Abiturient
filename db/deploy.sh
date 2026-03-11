#!/bin/bash

# =============================================
# MASTER DEPLOYMENT SCRIPT
# Database: abiturent_v2
# Created: 2026-03-09
# =============================================

# Конфигурация
CONTAINER_NAME="mysql-container"  # или название вашего контейнера с БД
DB_NAME="abiturent_v2"
DB_USER="root"
DB_PASSWORD="my-secret-pw"  # замените на ваш пароль
SQL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"  # директория скрипта

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для логирования
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# Проверка наличия Docker
if ! command -v docker &> /dev/null; then
    error "Docker не установлен!"
fi

# Проверка запущен ли контейнер
if ! docker ps | grep -q $CONTAINER_NAME; then
    error "Контейнер $CONTAINER_NAME не запущен!"
fi

log "🚀 Начинаем развертывание базы данных $DB_NAME"

# Создание базы данных, если не существует
log "📦 Проверка существования базы данных..."
docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
if [ $? -ne 0 ]; then
    error "Не удалось создать базу данных!"
fi

# =============================================
# SETTINGS
# =============================================
log "⚙️ Применение настроек..."
if [ -f "$SQL_DIR/000_settings.sql" ]; then
    docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME < "$SQL_DIR/000_settings.sql"
else
    warning "Файл 000_settings.sql не найден, пропускаем..."
fi

# =============================================
# TABLES
# =============================================
log "📊 Создание таблиц..."

TABLES=(
    "001_tables_admin.sql"
    "002_tables_attributes.sql"
    "003_tables_clusters.sql"
    "004_tables_directions.sql"
    "005_tables_establishments.sql"
    "006_tables_programs.sql"
    "007_tables_bundles.sql"
    "008_tables_metrics.sql"
    "009_tables_addresses.sql"
    "010_tables_phones.sql"
)

for table in "${TABLES[@]}"; do
    if [ -f "$SQL_DIR/tables/$table" ]; then
        log "  - Загрузка $table"
        docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME < "$SQL_DIR/tables/$table"
        if [ $? -ne 0 ]; then
            error "Ошибка при загрузке $table"
        fi
    else
        warning "  - Файл tables/$table не найден, пропускаем..."
    fi
done

# =============================================
# DATAS
# =============================================
log "📝 Загрузка данных..."

DATAS=(
    "001_datas_admins.sql"
    "002_datas_atributes.sql"
    "003_datas_bundles.sql"
    "004_datas_clusters.sql"
    "005_datas_directions.sql"
    # "006_datas_establishments.sql"  # закомментирован
    "007_datas_metrics.sql"
    "008_datas_programs.sql"
    "new_006_datas_establishments.sql"
    "new_009_datas_addresses.sql"
    "new_010_datas_phones.sql"
)

for data in "${DATAS[@]}"; do
    if [ -f "$SQL_DIR/datas/$data" ]; then
        log "  - Загрузка $data"
        docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME < "$SQL_DIR/datas/$data"
        if [ $? -ne 0 ]; then
            error "Ошибка при загрузке $data"
        fi
    else
        warning "  - Файл datas/$data не найден, пропускаем..."
    fi
done

# =============================================
# INDEXES
# =============================================
log "🔧 Создание индексов и ограничений..."

INDEXES=(
  "000_indexes_constraints.sql"
  "001_auto_increment.sql"
  "002_foreign_keys.sql"
)

for index in "${INDEXES[@]}"; do
    if [ -f "$SQL_DIR/constraints/$index" ]; then
        log "  - Загрузка $index"
        docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME < "$SQL_DIR/constraints/$index"
        if [ $? -ne 0 ]; then
            error "Ошибка при загрузке $index"
        fi
    else
        warning "  - Файл constraints/$index не найден, пропускаем..."
    fi
done

# =============================================
# FINALS
# =============================================
log "🎯 Завершающие операции..."
if [ -f "$SQL_DIR/999_finalize.sql" ]; then
    docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME < "$SQL_DIR/999_finalize.sql"
else
    warning "Файл 999_finalize.sql не найден, пропускаем..."
fi

# =============================================
# STATISTICS
# =============================================
log "📊 Статистика развертывания:"

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}DEPLOYMENT COMPLETED SUCCESSFULLY!${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Получение статистики
echo "Tables count:"
docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) AS 'Tables count' FROM information_schema.tables WHERE table_schema = DATABASE();" 2>/dev/null

echo -e "\nTotal records in bundles:"
docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) AS 'Records in bundles' FROM bundles;" 2>/dev/null

# Дополнительная статистика
echo -e "\nTotal records in programs:"
docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) AS 'Records in programs' FROM programs;" 2>/dev/null

echo -e "\nTotal records in directions:"
docker exec -i $CONTAINER_NAME mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME -e "SELECT COUNT(*) AS 'Records in directions' FROM directions;" 2>/dev/null

log "✅ Развертывание завершено!"

# Сделать скрипт исполняемым:
# chmod +x deploy.sh
