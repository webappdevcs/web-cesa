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
        Schema::create('rekrutmen_request_man_powers', function (Blueprint $table) {
            $table->id();
            $table->string('email_address')->nullable();
            $table->string('nama_pengaju');
            $table->string('posisi_pengaju');
            $table->date('tanggal_pengajuan');
            $table->string('posisi_dibutuhkan');
            $table->string('lokasi_penempatan');
            $table->string('status_kebutuhan')->default('New Hiring');
            $table->string('divisi');
            $table->string('level_pekerjaan');
            $table->string('nama_karyawan_replacement')->nullable()->comment('Nama karyawan yang akan digantikan untuk kebutuhan replacement');
            $table->string('badan_usaha');
            $table->integer('jumlah_karyawan_dibutuhkan')->default(1);
            $table->date('estimasi_tanggal_join');
            $table->text('requirements_kualifikasi');
            $table->text('job_description');
            $table->text('keterangan')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email_address');
            $table->index('tanggal_pengajuan');
            $table->index('status_kebutuhan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekrutmen_request_man_powers');
    }
};
