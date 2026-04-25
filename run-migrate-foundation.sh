#!/bin/bash

# Cores para feedback
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🚀 Reiniciando Fundação Service-Desk (Versão Definitiva)...${NC}"

# 1. Limpeza Radical de Migrations Antigas
# Removemos tudo para garantir que o esquema UUID e as tabelas de sistema fiquem em ordem
rm -f database/migrations/*.php

# 2. Migration 01: Tabelas de Sistema do Laravel (Inglês)
# Recriamos as tabelas que o framework exige para sessões e filas
echo -e "${BLUE}📦 Gerando tabelas de sistema (sessions, jobs)...${NC}"
php artisan session:table
php artisan queue:table
php artisan queue:failed-table

# 3. Migration 02: Esquema SaaS Unificado (UUID)
# Criado com sleep para garantir que o timestamp seja posterior às tabelas de sistema
sleep 1
CAT_DATE=$(date +%Y_%m_%d_%H%M%S)
cat <<EOF > database/migrations/${CAT_DATE}_create_saas_core_tables.php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tenants Table
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

        // Users Table
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

        // Contacts Table (White-list)
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

# 4. Limpeza e Migração do Banco
echo -e "${BLUE}⚙️ Resetando banco de dados SQLite...${NC}"
if [ -f database/database.sqlite ]; then
    rm database/database.sqlite
fi
touch database/database.sqlite

php artisan migrate --force

# 5. Execução do Seeder
echo -e "${BLUE}🌱 Semeando dados da NetLogin Brasil...${NC}"
php artisan db:seed --class=FoundationSeeder

# 6. Criação dos Resources do Filament (Se ainda não existirem)
echo -e "${BLUE}🎨 Verificando Resources do Filament...${NC}"
php artisan make:filament-resource Tenant --simple --quiet
php artisan make:filament-resource Contact --quiet

echo -e "${GREEN}✅ Tudo pronto! Sessões, Filas e SaaS Core configurados.${NC}"
echo -e "${BLUE}👉 Acesse o painel e use: admin@netlogin.com.br / password${NC}"