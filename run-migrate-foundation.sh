#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 Fixing Database Schema & Running Professional Foundation...${NC}"

# 1. Remover migrações antigas de usuários e tenants para evitar conflitos
# Queremos que o Laravel crie a tabela de usuários já com UUID
rm -f database/migrations/*_create_users_table.php
rm -f database/migrations/*_create_tenants*.php
rm -f database/migrations/*_create_contacts*.php

# 2. Nova Migration Unificada: Users, Tenants e Contacts (All UUID)
# Criamos os tenants primeiro para que a FK em users funcione na criação
CAT_DATE=$(date +%Y_%m_%d_%H%M%S)
cat <<EOF > database/migrations/${CAT_DATE}_base_saas_schema.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tenants Table
        Schema::create('tenants', function (Blueprint \$table) {
            \$table->uuid('id')->primary();
            \$table->string('name');
            \$table->string('slug')->unique();
            \$table->string('tax_id')->nullable();
            \$table->boolean('is_active')->default(true);
            \$table->boolean('is_master')->default(false);
            \$table->timestamps();
            \$table->softDeletes();
        });

        // 2. Users Table (Re-created as UUID)
        Schema::create('users', function (Blueprint \$table) {
            \$table->uuid('id')->primary();
            \$table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            \$table->string('name');
            \$table->string('email')->unique();
            \$table->timestamp('email_verified_at')->nullable();
            \$table->string('password');
            \$table->string('phone_number')->nullable();
            \$table->enum('user_type', ['admin', 'operator', 'requester'])->default('requester');
            \$table->rememberToken();
            \$table->timestamps();
        });

        // 3. Contacts Table
        Schema::create('contacts', function (Blueprint \$table) {
            \$table->uuid('id')->primary();
            \$table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            \$table->string('name');
            \$table->string('phone_number')->unique();
            \$table->enum('status', ['authorized', 'pending', 'blocked'])->default('pending');
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
};
EOF

# 3. Executar limpeza total do banco de dados
echo -e "${BLUE}⚙️ Cleaning and Migrating...${NC}"
# No SQLite, as vezes é melhor apagar o arquivo e recriar
if [ -f database/database.sqlite ]; then
    rm database/database.sqlite
    touch database/database.sqlite
fi

php artisan migrate:fresh

# 4. Executar o Seeder
echo -e "${BLUE}🌱 Seeding Foundation Data...${NC}"
php artisan db:seed --class=FoundationSeeder

echo -e "${GREEN}✅ System Rebuilt Successfully with UUID compliance!${NC}"