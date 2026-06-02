<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF to_regclass('public.api_clients') IS NOT NULL
                    AND to_regclass('authentication.api_clients') IS NULL THEN
                    ALTER TABLE public.api_clients SET SCHEMA authentication;
                END IF;

                IF to_regclass('public.api_tokens') IS NOT NULL
                    AND to_regclass('authentication.api_tokens') IS NULL THEN
                    ALTER TABLE public.api_tokens SET SCHEMA authentication;
                END IF;
            END
            $$;
        SQL);
    }

    public function down(): void
    {
        //
    }
};
