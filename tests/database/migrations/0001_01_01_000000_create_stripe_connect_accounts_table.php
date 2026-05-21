<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('stripe_connect.account.table', 'stripe_connect_accounts');
        $morphName = config('stripe_connect.account.morph_name', 'connectable');

        Schema::create($tableName, function (Blueprint $table) use ($morphName) {
            $table->id();
            $table->morphs($morphName);
            $table->string('stripe_account_id')->nullable();
            $table->boolean('stripe_account_active')->default(false);
            $table->timestamps();

            $table->unique(["{$morphName}_type", "{$morphName}_id"]);
            $table->index('stripe_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('stripe_connect.account.table', 'stripe_connect_accounts'));
    }
};
