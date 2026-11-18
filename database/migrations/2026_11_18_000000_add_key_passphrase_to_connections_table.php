<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            // Passphrase of a password-protected private key (e.g. a PuTTYgen .ppk).
            // Stored encrypted (model cast), like the SSH password.
            $table->text('key_passphrase')->nullable()->after('ssh_key_path');
        });
    }

    public function down(): void
    {
        Schema::table('connections', function (Blueprint $table) {
            $table->dropColumn('key_passphrase');
        });
    }
};
