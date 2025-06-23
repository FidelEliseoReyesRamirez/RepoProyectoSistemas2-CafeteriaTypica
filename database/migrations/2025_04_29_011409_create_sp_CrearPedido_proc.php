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
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CrearPedido`(
    IN p_id_usuario_mesero INT,
    IN p_estado_actual INT
)
BEGIN
    INSERT INTO Pedido (id_usuario_mesero, estado_actual)
    VALUES (p_id_usuario_mesero, p_estado_actual);
    SELECT LAST_INSERT_ID() AS id_nuevo_pedido;
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_CrearPedido");
    }
};
