<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
    }

    public function down()
    {
        DB::statement('DROP EXTENSION IF EXISTS unaccent');
    }
};
