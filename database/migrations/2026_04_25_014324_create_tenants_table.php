<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tax_id')->nullable()->comment('CNPJ for Brazil');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_master')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->onDelete('cascade');
            
            $table->string('phone_number')->nullable()->after('email');
            $table->enum('user_type', ['admin', 'operator', 'requester'])
                ->default('requester')
                ->after('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'phone_number', 'user_type']);
        });        

        Schema::dropIfExists('tenants');
    }
};
