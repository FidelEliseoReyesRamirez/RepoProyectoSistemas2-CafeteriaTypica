<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_InsertarUsuario`(
    IN p_nombre VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_contrasena_hash VARCHAR(255),
    IN p_id_rol INT
)
BEGIN
    INSERT INTO Usuario (nombre, correo, contrasena_hash, id_rol)
    VALUES (p_nombre, p_correo, p_contrasena_hash, p_id_rol);
    SELECT LAST_INSERT_ID() AS id_nuevo_usuario;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_InsertarUsuario");
    }
};
